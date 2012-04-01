<?php

namespace Glit\ProjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller {
    /**
     * @Route("/projects/")
     * @Template()
     */
    public function indexAction() {
        return array();
    }

    /**
     *
     *
     */
    public function viewAction(\Glit\ProjectsBundle\Entity\Project $project) {
        return array();
    }

    /**
     * @Route("{uniqueName}/projects/new")
     * @Template()
     */
    public function newAction($uniqueName) {
        $account = $this->getDoctrine()->getRepository('GlitCoreBundle:Account')->findOneByUniqueName($uniqueName);

        // Check if current user can create project for this account
        if ($account != null && $uniqueName != $this->getCurrentUser()->getUniqueName()) {
            // user create project for another.
            // TODO : Check rights
        }

        if (null === $account) {
            throw $this->createNotFoundException('Account not found to create project.');
        }

        $project = new \Glit\ProjectsBundle\Entity\Project($account);

        $form = $this->createForm(new \Glit\ProjectsBundle\Form\ProjectType(), $project);

        return array('form' => $form->createView());
    }

    /**
     * @return \Glit\UserBundle\Entity\User
     */
    protected function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }

    /**
     * Set session flash
     * @param $action
     * @param $value
     */
    protected function setFlash($action, $value) {
        $this->container->get('session')->setFlash($action, $value);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getDefaultEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }
}
