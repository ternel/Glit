<?php
namespace Glit\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Glit\UserBundle\Form as Type;
use Glit\UserBundle\Form\Model as FormModel;

class DefaultController extends Controller {

    public function viewAction(\Glit\UserBundle\Entity\User $user) {
        $projects = $this->getDoctrine()->getRepository('GlitProjectsBundle:Project')->findByOwner($user->getId());

        if ($this->getCurrentUser()->getId() == $user->getId()) {
            // Own page
            return $this->render('GlitUserBundle:Default:index-own.html.twig', array('user'        => $user,
                                                                                    'own_projects' => $projects));
        }
        else {
            // Other Page
            return $this->render('GlitUserBundle:Default:index-others.html.twig', array('user'        => $user,
                                                                                       'own_projects' => $projects));
        }
    }

    /**
     * @Route("/edit/")
     * @Template()
     */
    public function editAction() {
        /** @var $user \Glit\UserBundle\Entity\User */
        $user = $this->getCurrentUser();

        $formProfile  = $this->createForm(new Type\ProfileType(), $user);
        $formPassword = $this->createForm(new Type\ChangePasswordType(), new FormModel\ChangePassword($user));

        if ($this->getRequest()->getMethod() == 'POST') {

            if (null !== $this->getRequest()->get('user_change_password', null)) {
                $formPassword->bindRequest($this->getRequest());
                if ($formPassword->isValid()) {
                    $encoder = $this->get('security.encoder_factory')->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($formPassword->getData()->new, $user->getSalt()));
                    $this->getDefaultEntityManager()->flush();

                    $this->setFlash('success', 'Your password was successfully changed.');
                    return $this->redirect($this->generateUrl('glit_user_default_edit'));
                }
            }
            elseif (null !== $this->getRequest()->get('user_profile', null)) {
                $formProfile->bindRequest($this->getRequest());
                if ($formProfile->isValid()) {
                    $this->getDefaultEntityManager()->flush();

                    $this->setFlash('success', 'Your data was successfully saved.');
                    return $this->redirect($this->generateUrl('glit_user_default_edit'));
                }
            }
        }

        return array('formProfile'  => $formProfile->createView(),
                     'formPassword' => $formPassword->createView());
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

    /**
     * @return \Glit\GitoliteBundle\Admin\Gitolite
     */
    protected function getGitoliteAdmin() {
        return $this->get('glit_gitolite.admin');
    }
}
