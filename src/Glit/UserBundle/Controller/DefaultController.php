<?php
namespace Glit\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Glit\UserBundle\Form\Type as Type;
use Glit\UserBundle\Form\Model as FormModel;

class DefaultController extends Controller {
    public function viewAction($user) {
        if ($this->get('security.context')->getToken()->getUser()->getId() == $user->getId()) {
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
        /** @var $user \Glit\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();

        $formProfile = $this->createForm(new Type\ProfileType(), $user);
        $formPassword = $this->createForm(new Type\ChangePasswordType(), new FormModel\ChangePassword($user));

        if ($this->getRequest()->getMethod() == 'POST') {

            if (null !== $this->getRequest()->get('user_change_password', null)) {
                $formPassword->bindRequest($this->getRequest());
                if($formPassword->isValid()) {
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($formPassword->getData()->new, $user->getSalt()));
                    $this->getDoctrine()->getEntityManager()->flush();

                    $this->setFlash('success', 'Your password was successfully changed.');
                    return $this->redirect($this->generateUrl('glit_user_default_edit'));
                }
            }
            elseif (null !== $this->getRequest()->get('user_profile', null)) {
                $formProfile->bindRequest($this->getRequest());
                if($formProfile->isValid()) {
                    $this->getDoctrine()->getEntityManager()->flush();

                    $this->setFlash('success', 'Your data was successfully saved.');
                    return $this->redirect($this->generateUrl('glit_user_default_edit'));
                }
            }
        }

        return array('formProfile' => $formProfile->createView(), 'formPassword' => $formPassword->createView());
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

    protected function setFlash($action, $value)
    {
        $this->container->get('session')->setFlash($action, $value);
    }
}
