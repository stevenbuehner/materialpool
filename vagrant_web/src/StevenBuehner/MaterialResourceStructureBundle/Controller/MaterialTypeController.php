<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Controller;


use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use StevenBuehner\MaterialResourceStructureBundle\Entity\MaterialType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MaterialTypeController extends FOSRestController {
	const REPOSITORY_NAME = 'MaterialResourceStructureBundle:MaterialType';

	/**
	 * @Rest\Get("/materialtype")
	 */
	public function getAction() {
		$restresult = $this->getDoctrine()->getRepository(self::REPOSITORY_NAME)->findAll();
		if ($restresult === NULL) {
			return new View("there are no users exist", Response::HTTP_NOT_FOUND);
		}

		return $restresult;
	}

	/**
	 * @Rest\Get("/materialtype/{id}")
	 */
	public function idAction($id) {
		$singleresult = $this->getDoctrine()->getRepository(self::REPOSITORY_NAME)->find($id);
		if ($singleresult === NULL) {
			return new View("user not found", Response::HTTP_NOT_FOUND);
		}

		return $singleresult;
	}


	/**
	 * @Rest\Post("/materialtype/")
	 */
	public function postAction(Request $request) {
		$data        = new MaterialType();
		$title       = $request->get('title');
		$description = $request->get('description');

		if (empty($title) || empty($description)) {
			return new View("NULL VALUES ARE NOT ALLOWED", Response::HTTP_NOT_ACCEPTABLE);
		}
		$data->setTitle($title);
		$data->setDescription($description);

		/** @var NestedTreeRepository $repo */
		$repo = $this->getDoctrine()->getRepository(self::REPOSITORY_NAME);

		$data->setParent($repo->find(1));

		$em = $this->getDoctrine()->getManager();
		$em->persist($data);
		$em->flush();

		return new View("MaterialType Added Successfully", Response::HTTP_OK);
	}
}

