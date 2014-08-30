<?php

namespace BeerToBeer\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use BeerToBeer\CoreBundle\Entity\BeerBusiness;

/**
 * BusinessRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessRepository extends EntityRepository
{
	/**
	 * S'utilise pour trouver les établissements les plus proches d'une position en donnant une limite
	 * @param  float  $latitude : la latitude de l'utilisateur
	 * @param  float  $longitude : la longitude de l'utilisateur
	 * @param  integer $offset : par où on commence à lire les résultats, à utiliser lors du lazy loading
	 * @param  integer $limit : le nombre d'établissements à afficher
	 * @return array
	 */
	public function getClosestBusinessesForApi($latitude, $longitude, $offset = 0, $limit = 10) {

		$query = $this->_em->createQuery('
			SELECT bu, bb, h, be,
			GEO_DISTANCE(:latitude, :longitude, bu.latitude, bu.longitude) AS distance 
			FROM BeerToBeerCoreBundle:Business bu
			JOIN bu.beerBusinesses bb
			JOIN bb.beer be
			LEFT JOIN bu.horaires h
			ORDER BY distance, bb.prixHappyHour
			')
			->setParameter('latitude', $latitude)
		    ->setParameter('longitude', $longitude)
			->setFirstResult($offset)
			//->setMaxResults($limit)
		;

		$results =  $query->getArrayResult();

		// Render the results as the API wants it
		$businessesForApi = array();
		$i = 0;
		foreach ($results as $key => $result) {
			$businessesForApi[$i] = $this->parseOneBusinessForApi($result[0]);
			$businessesForApi[$i]["distance"] = round($result["distance"]*1000, -1); // On ajoute la distance en mètres arrondi aux dizaines
			
			$i++;
		}

		return $businessesForApi;
	}

	/**
	 * Récupère un Business par son id et le renvoie sous format API
	 * @param  integer $id
	 * @return array
	 */
	public function getBusinessForApi($id) {
		$query = $this->_em->createQuery('
			SELECT bu, h, bb, be
			FROM BeerToBeerCoreBundle:Business bu
			JOIN bu.beerBusinesses bb
			JOIN bb.beer be
			LEFT JOIN bu.horaires h
			WHERE bu.id = :id
			ORDER BY bb.prixHappyHour
			')
			->setParameter('id', $id)
		;

		$result =  $query->getArrayResult();

		if (!isset($result[0]))
			throw new HttpException(404, "Business inexistant.");

		return $this->parseOneBusinessForApi($result[0]);
	}

	/**
	 * S'utilise pour modifier l'array d'un business donné par Doctrine pour l'adapter à l'API
	 * @param  array $result
	 * @return array
	 */
	private function parseOneBusinessForApi($result) {

		// Il faut prendre le prix de la "pinte" la moins chère, donc on vérifie que le volume est de 50cl
		$stop = false;
		for ($i=0; $stop === false ; $i++) { 
			if ($result["beerBusinesses"][$i]["volume"] == 50) {
				$result["prixNormal"] = $result["beerBusinesses"][$i]["prixNormal"];
				$result["prixHappyHour"] = $result["beerBusinesses"][$i]["prixHappyHour"];
				$stop = true;
			} else if (!isset($result["beerBusinesses"][$i]["volume"])) {
				$result["prixNormal"] = $result["beerBusinesses"][0]["prixNormal"];
				$result["prixHappyHour"] = $result["beerBusinesses"][0]["prixHappyHour"];
				$stop = true;
			}
		}
		
		foreach ($result["beerBusinesses"] as $keyBb => $beerBusiness) {
			if ($beerBusiness["pression"])
				$id = "p".$beerBusiness["beer"]["id"];
			else
				$id = $beerBusiness["beer"]["id"];
			if (!isset($result["beers"][$id])) {
				$result["beers"][$id]["id"] = $id;
				$result["beers"][$id]["name"] = $beerBusiness["beer"]["name"];
				$result["beers"][$id]["degree"] = $beerBusiness["beer"]["degree"];
				$result["beers"][$id]["pression"] = $beerBusiness["pression"];
				$result["beers"][$id]["prix"] = array();
			}
			$result["beers"][$id]["prix"][] = array(
				"id" => $beerBusiness["id"],
				"volume" => $beerBusiness["volume"],
				"prixHappyHour" => $beerBusiness["prixHappyHour"],
				"prixNormal" => $beerBusiness["prixNormal"]
			);
		}
		
		unset($result["beerBusinesses"]);

		foreach ($result["horaires"] as $key => $value) {
			$result["horaires"][$key]["ouverture"] = $result["horaires"][$key]["ouverture"]->getTimeStamp();
		}

		return $result;
	}

	public function updateBusinessFromApi($businessFromApi) {
		// Pour l'instant on ne modifie que les bières
		foreach ($businessFromApi["beers"] as $idBeerFromApi => $beerFromApi) {
			foreach ($beerFromApi["prix"] as $prixFromApi) {
				if (array_key_exists("id", $prixFromApi))
					$beerBusiness = $this->_em->getRepository("BeerToBeerCoreBundle:BeerBusiness")->find($prixFromApi["id"]);
				else {
					$beerBusiness = new BeerBusiness();
					$beer = $this->_em->getRepository("BeerToBeerCoreBundle:Beer")->find(str_replace("p", "", $idBeerFromApi));
					$beerBusiness->setBeer($beer);
					$business = $this->_em->getRepository("BeerToBeerCoreBundle:Business")->find($businessFromApi["id"]);
					$beerBusiness->setBusiness($business);
					$beerBusiness->setPression($beerFromApi["pression"]);
				}
				$beerBusiness->setVolume($prixFromApi["volume"]);
				$beerBusiness->setPrixNormal($prixFromApi["prixNormal"]);

				// Si le prix existe déjà et qu'il est spécifié qu'il doit être supprimé
				if (isset($prixFromApi["toRemove"]) && $prixFromApi["toRemove"] === true && array_key_exists("id", $prixFromApi))
					$this->_em->remove($beerBusiness);
				else {
					// Vérification de la logique des prix
					if (isset($prixFromApi["prixHappyHour"])) {
						if ($prixFromApi["prixHappyHour"] <= $prixFromApi["prixNormal"])
							$beerBusiness->setPrixHappyHour($prixFromApi["prixHappyHour"]);
						else
							return "Le prix en happy-hour est supérieur au prix normal.";
					}
					$this->_em->persist($beerBusiness);
				}
			}
		}

		$this->_em->flush();

		return true;
	}
}
