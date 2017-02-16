<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('MaterialResourceStructureBundle:Default:index.html.twig');
    }
}
