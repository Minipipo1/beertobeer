<?php

namespace BeerToBeer\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use BeerToBeer\CoreBundle\Entity\BeerBusiness;

class BeerBusinesses implements FixtureInterface, OrderedFixtureInterface
{
  // Dans l'argument de la méthode load, l'objet $manager est l'EntityManager
  public function load(ObjectManager $manager)
  {
    $items = array(
        array(
          "business" => "Dirty Dick",
          "beer" => "Stella Artois",
          "prixNormal" => 2,
          "prixHappyHour" => 2,
          "volume" => 50
        ),
        array(
          "business" => "Le Cabaret Michou",
          "beer" => "Stella Artois",
          "prixNormal" => 8,
          "prixHappyHour" => 8,
          "volume" => 50
        ),
        array(
          "business" => "Le Timbre Poste",
          "beer" => "Stella Artois",
          "prixNormal" => 4.5,
          "prixHappyHour" => 4.5,
          "volume" => 50
        ),
        array(
          "business" => "James Hetfeeld's Pub",
          "beer" => "Stella Artois",
          "prixNormal" => 7,
          "prixHappyHour" => 3,
          "volume" => 50
        ),
        array(
          "business" => "Les tonneaux parisiens",
          "beer" => "Stella Artois",
          "prixNormal" => 5,
          "prixHappyHour" => 2,
          "volume" => 50
        ),

      );

    foreach($items as $i => $item)
    {
      $liste_items[$i] = new BeerBusiness();

      $beer = $manager->getRepository('BeerToBeerCoreBundle:Beer')->findBy(array("name" => $item["beer"]));
      $beer = $beer[0];
      $liste_items[$i]->setBeer($beer);

      $business = $manager->getRepository('BeerToBeerCoreBundle:Business')->findBy(array("nom" => $item["business"]));
      $business = $business[0];
      $liste_items[$i]->setBusiness($business);

      $liste_items[$i]->setPrixNormal($item["prixNormal"]);
      $liste_items[$i]->setPrixHappyHour($item["prixHappyHour"]);
      $liste_items[$i]->setVolume($item["volume"]);

      $manager->persist($liste_items[$i]);
    }

    // On déclenche l'enregistrement
    $manager->flush();
  }
  public function getOrder()
  {
      return 3;
  }
}