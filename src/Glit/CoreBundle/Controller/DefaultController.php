<?php

namespace Glit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="_welcome")
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('glit_core_account_view', array('uniqueName' => $this->get('security.context')->getToken()->getUsername())));
    }
}
