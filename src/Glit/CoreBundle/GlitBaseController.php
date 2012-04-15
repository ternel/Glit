<?php
namespace Glit\CoreBundle;

class GlitBaseController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller {

    /**
     * @return \Glit\UserBundle\Entity\User
     */
    protected
    function getCurrentUser() {
        return $this->get('security.context')->getToken()->getUser();
    }

    /**
     * Set session flash
     * @param $action
     * @param $value
     */
    protected
    function setFlash($action, $value) {
        $this->container->get('session')->setFlash($action, $value);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected
    function getDefaultEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }

}