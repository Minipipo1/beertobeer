<?php

namespace BeerToBeer\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use BeerToBeer\CoreBundle\Entity\Business;

class ApiController extends Controller
{
	public function businessAction() {
		$request = $this->getRequest();

		if ($request->query->get('latitude') != null && $request->query->get('longitude') != null)
			return $this->searchFromGpsAction($request->query->get('latitude'), $request->query->get('longitude'));

		throw new HttpException(404, "Page introuvable.");
	}

    public function searchFromGpsAction($latitude, $longitude)
    {
    	if (!is_numeric($latitude) || !is_numeric($longitude)) {
    		// Throw 400 BAD REQUEST puisqu'il manque des informations ou qu'elles sont mal données
    		throw new HttpException(400, "Vos coordonnées GPS sont absentes ou mal données.");
    	}

    	$repo = $this->getDoctrine()->getManager()->getRepository('BeerToBeerCoreBundle:Business');

    	$results = $repo->getClosestBusinesses(floatval($latitude), floatval($longitude));

        $response = new JsonResponse();
		return $response->setData($results);
    }

    public function getBusinessFromIdAction($id) {
        $repoBusiness = $this->getDoctrine()->getManager()->getRepository('BeerToBeerCoreBundle:Business');

        $result = $repoBusiness->getBusiness(intval($id));

        $response = new JsonResponse();
        return $response->setData($result);
    }
}
