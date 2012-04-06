<?php

namespace Glit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/gitolite")
 */
class GitoliteController extends Controller {

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction() {
        /** @var $gitoliteAdmin \Glit\GitoliteBundle\Admin\Gitolite */
        $gitoliteAdmin = $this->getGitoliteAdmin();
        return array(
            'sshKeys' => $this->loadSshKeys(),
            'history' => array_reverse($gitoliteAdmin->getHistory())
        );
    }

    /**
     * @Route("/ssh/{keyId}/delete")
     * @Template()
     */
    public function sshDeleteAction($keyId) {
        if ($keyId != 'glit') {

            try
            {
                /** @var $gitoliteAdmin \Glit\GitoliteBundle\Admin\Gitolite */
                $gitoliteAdmin = $this->getGitoliteAdmin();
                $gitoliteAdmin->deleteUserKey($keyId);

                $this->setFlash('success', 'Your SSH public key was deleted.');
            }
            catch (Exception $ex) {
                $this->setFlash('error', 'Unable to delete : unknow SSH key.');
            }

        }

        return $this->redirect($this->generateUrl('glit_admin_gitolite_index'));
    }

    protected function loadSshKeys() {
        $doctrine = $this->getDoctrine();
        return array_map(function($key) use ($doctrine) {
            $keyIdentifier = substr($key, 0, -4);

            $data = array(
                'key'       => $keyIdentifier,
                'removable' => true,
            );

            if ($key == 'glit.pub') {
                $data['type']      = 'SystemKey';
                $data['removable'] = false;
            }
            else {
                $key = $doctrine->getRepository('GlitCoreBundle:SshKey')->findOneBy(array('keyIdentifier' => $keyIdentifier));
                if (isset($key)) {
                    if ($key instanceof \Glit\UserBundle\Entity\SshKey) {
                        $data['type'] = 'UserKey:' . $key->getUser();

                    }
                }
            }

            return $data;
        }, $this->getGitoliteAdmin()->getSshKeys());
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
     * @return \Glit\GitoliteBundle\Admin\Gitolite
     */
    protected function getGitoliteAdmin() {
        return $this->get('glit_gitolite.admin');
    }
}
