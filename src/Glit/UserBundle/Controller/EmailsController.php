<?php
namespace Glit\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Glit\UserBundle\Form as Type;

/**
 * @Route("/emails")
 */
class EmailsController extends Controller {

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
        return $this->forward('GlitUserBundle:Emails:edit', array('id' => null));
    }

    /**
     * @Route("/{id}/edit")
     * @Template()
     */
    public function editAction($id) {
        $item = null;
        /** @var $item \Glit\UserBundle\Entity\Email */
        if (null === $id) {
            $item = new \Glit\UserBundle\Entity\Email($this->getCurrentUser());
        }
        else {
            $item = $this->getRepository()->find($id);
            if ($item->getUser()->getId() != $this->getCurrentUser()->getId()) {
                // User can only edit owns items
                $key = null;
            }
        }

        if (null === $item) {
            throw $this->createNotFoundException('Oops! I\'m sorry but I cannot found your email address in my database.');
        }

        $form = $this->createForm(new Type\EmailType(), $item);

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->bindRequest($this->getRequest());
            if ($form->isValid()) {
                /** @var $em \Doctrine\ORM\EntityManager */
                $em = $this->getDefaultEntityManager();
                if (!$em->contains($item)) {
                    $em->persist($item);
                }

                $em->flush();

                $this->setFlash('success', 'I\'ve saved your email. Thanks !');
                return $this->redirect($this->generateUrl('glit_user_emails_view'));
            }
            else {
                $this->setFlash('error', 'Oops it seems I cannot understand some informations that you sent me. Please view my remarks below.');
            }
        }

        return array('form'  => $form->createView(),
                     'item'  => $item);
    }

    /**
     * @Route("/{id}/delete")
     * @Template()
     */
    public function deleteAction($id) {
        /** @var $item \Glit\UserBundle\Entity\Email */
        $item = $this->getRepository()->find($id);
        if ($item->getUser()->getId() != $this->getCurrentUser()->getId()) {
            // User can only edit owns emails
            $item = null;
        }

        if (!is_null($item)) {
            if (!$item->getIsDefault()) {
                $this->getDefaultEntityManager()->remove($item);
                $this->getDefaultEntityManager()->flush();

                $this->setFlash('success', 'Okay I forgot this address.');
            }
            else {
                $this->setFlash('warning', 'Hum I\'m sorry but I need to know at least one address email to contact you.');
            }
        }
        else {
            $this->setFlash('error', 'Oops! I\'m sorry but I cannot found your email address in my database.');
        }

        return $this->redirect($this->generateUrl('glit_user_emails_view'));
    }

    /**
     * @Route("/{id}/activate/{activationKey}")
     * @param $id
     * @param $activationKey
     */
    public function activateAction($id, $activationKey) {
        /** @var $item \Glit\UserBundle\Entity\Email */
        $item = $this->getRepository()->find($id);
        if ($item->getUser()->getId() != $this->getCurrentUser()->getId()) {
            // User can only edit owns emails
            $item = null;
        }

        if (!is_null($item)) {
            if ($item->getActivationKey() == $activationKey) {

                if (!$item->getIsActive()) {
                    $item->setIsActive(true);
                    $this->getDefaultEntityManager()->flush();

                    $this->setFlash('success', 'Yeah now I know your email to contact or recognize you. Thanks!');
                }
                else {
                    $this->setFlash('info', 'Did you know you only need to activate your email once or just forget you allready done that?');
                }
            }
            else {
                $this->setFlash('error', 'Oops! It seems that we do not recognize your activation key...');
            }
        }
        else {
            $this->setFlash('error', 'Oops! I\'m sorry but I cannot found your email address in my database.');
        }

        return $this->redirect($this->generateUrl('glit_user_emails_view'));
    }

    /**
     * @Route("/{id}/default")
     * @param $id
     * @param $activationKey
     */
    public function defaultAction($id) {
        /** @var $item \Glit\UserBundle\Entity\Email */
        $item = $this->getRepository()->find($id);
        if ($item->getUser()->getId() != $this->getCurrentUser()->getId()) {
            // User can only edit owns emails
            $item = null;
        }

        if (!is_null($item)) {
            if ($item->getIsActive()) {
                if (!$item->getIsDefault()) {
                    $item->setIsDefault(true);
                    $this->getDefaultEntityManager()->flush();

                    $this->setFlash('success', 'Allright now I will use this email to contact you !');
                }
                else {
                    $this->setFlash('info', 'Hum this is allready the email I use to contact you.');
                }
            }
            else {
                $this->setFlash('warning', 'Please activate this email before make it default.');
            }
        }
        else {
            $this->setFlash('error', 'Oops! I\'m sorry but I cannot found your email address in my database.');
        }

        return $this->redirect($this->generateUrl('glit_user_emails_view'));
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository() {
        return $this->getDoctrine()->getRepository('GlitUserBundle:Email');
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

}