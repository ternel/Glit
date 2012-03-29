<?php
namespace Glit\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    public function viewAction($user)
    {
        if($this->get('security.context')->getToken()->getUser()->getId() == $user->getId()) {
            // Own page
            return $this->render('GlitUserBundle:Default:index-own.html.twig', array('user' => $user));
        }
        else {
            // Other Page
            return $this->render('GlitUserBundle:Default:index-others.html.twig', array('user' => $user));
        }
    }

    /**
     * @Route("/edit/")
     * @Template()
     */
    public function editAction() {
        return array();
    }

    /**
     * @Route("/ssh/")
     * @Template()
     */
    public function sshAction() {
        return array();
    }

    /**
     * @Route("/organizations/")
     * @Template()
     */
    public function organizationsAction() {
        return array();
    }

    /**
     * @Route("/notifications/")
     * @Template()
     */
    public function notificationsAction() {
        return array();
    }
}
