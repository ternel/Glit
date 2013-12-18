<?php
namespace Glit\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Glit\UserBundle\Form as Type;

/**
 * @Route("/ssh")
 */
class SshKeysController extends Controller {

    /**
     * @Route("/")
     * @Template()
     */
    public function viewAction() {
        $items = $this->getRepository()->findByUser($this->getCurrentUser()->getId());
        return array('items' => $items);
    }

    /**
     * @Route("/new")
     */
    public function newAction() {
        return $this->forward('GlitUserBundle:SshKeys:edit', array('id' => null));
    }

    /**
     * @Route("/{id}/edit")
     * @Template()
     */
    public function editAction($id) {
        $key = null;
        if (null === $id) {
            $key = new \Glit\UserBundle\Entity\SshKey($this->getCurrentUser());
        }
        else {
            $key = $this->getRepository()->find($id);
            if ($key->getUser()->getId() != $this->getCurrentUser()->getId()) {
                // User can only edit owns ssh keys
                $key = null;
            }
        }

        if (null === $key) {
            throw $this->createNotFoundException('The key does not exist');
        }

        $form = $this->createForm(new Type\SshKeyType(), $key);

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->bindRequest($this->getRequest());
            if ($form->isValid()) {
                /** @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getDefaultEntityManager();
                if (!$em->contains($key)) {
                    $em->persist($key);
                }

                /** @var $gitoliteAdmin \Glit\GitoliteBundle\Admin\Gitolite */
                $gitoliteAdmin = $this->getGitoliteAdmin();
                $gitoliteAdmin->saveUserKey($key->getKeyIdentifier(), $key->getPublicKey());

                $em->flush();

                $this->setFlash('success', 'Your SSH public key was successfully saved.');
                return $this->redirect($this->generateUrl('glit_user_sshkeys_view'));
            }
            else {
                $this->setFlash('error', 'Unable to save your SSH public key. Please correct your inputs.');
            }
        }

        return array('form' => $form->createView(),
                     'key'  => $key);
    }

    /**
     * @Route("/{id}/delete")
     */
    public function deleteAction($id) {
        /** @var $key \Glit\UserBundle\Entity\SshKey */
        $key = $this->getRepository()->find($id);
        if ($key->getUser()->getId() != $this->getCurrentUser()->getId()) {
            // User can only edit owns ssh keys
            $key = null;
        }

        if (!is_null($key)) {
            $this->getDefaultEntityManager()->remove($key);

            /** @var $gitoliteAdmin \Glit\GitoliteBundle\Admin\Gitolite */
            $gitoliteAdmin = $this->getGitoliteAdmin();
            $gitoliteAdmin->deleteUserKey($key->getKeyIdentifier());

            $this->getDefaultEntityManager()->flush();

            $this->setFlash('success', 'Your SSH public key was deleted.');
        }
        else {
            $this->setFlash('error', 'Unable to delete : unknow SSH key.');
        }

        return $this->redirect($this->generateUrl('glit_user_sshkeys_view'));
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository() {
        return $this->getDoctrine()->getRepository('GlitUserBundle:SshKey');
    }

    /**
     * @return \Glit\UserBundle\Entity\User
     */
    protected function getCurrentUser() {
        /** @var $securityContext \Symfony\Component\Security\Core\SecurityContextInterface */
        $securityContext = $this->get('security.context');
        return $securityContext->getToken()->getUser();
    }

    /**
     * Set session flash
     * @param $action
     * @param $value
     */
    protected function setFlash($action, $value) {
        /** @var $session \Symfony\Component\HttpFoundation\Session */
        $session = $this->container->get('session');
        $session->setFlash($action, $value);
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