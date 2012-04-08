<?php
namespace Glit\ProjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CommitsController extends Controller {

    /**
     * @Route("{accountName}/{projectPath}/commit/{commitName}")
     * @Template()
     */
    public function viewAction($accountName, $projectPath, $commitName) {
        return array();
    }

    /**
     * @Route("{accountName}/{projectPath}/commits/{branchName}")
     * @Template()
     */
    public function historyAction($accountName, $projectPath, $branchName) {
        /** @var $account \Glit\CoreBundle\Entity\Account */
        $account = $this->getDoctrine()->getRepository('GlitCoreBundle:Account')->findOneByUniqueName($accountName);

        if (null == $account) {
            throw $this->createNotFoundException(sprintf('Account %s not found', $accountName));
        }

        /** @var $project \Glit\ProjectsBundle\Entity\Project */
        $project = $this->getDoctrine()->getRepository('GlitProjectsBundle:Project')->findOneBy(array('path' => $projectPath,
                                                                                                     'owner' => $account->getId()));

        if (null == $project) {
            throw $this->createNotFoundException(sprintf('Project %s not found', $projectPath));
        }

        // Load data from repository
        $repository = $this->getGitoliteAdmin()->getRepository($project->getFullPath() . '.git');

        if ($repository->isNew()) {
            return $this->redirect($this->generateUrl('glit_projects_default_view', array('accountName' => $accountName,
                                                                                         'projectPath'  => $projectPath)));
        }

        $history = array_reverse($repository->getBranch($branchName)->getHistory());

        // Search user in the list
        $emailRepository = $this->getDoctrine()->getRepository('GlitUserBundle:Email');
        $users           = array();
        foreach ($history as $commit) {
            if (isset($users[$commit->getAuthor()->email])) {
                continue;
            }

            /** @var $glitUserEmail \Glit\UserBundle\Entity\Email */
            $glitUserEmail                       = $emailRepository->findOneByAddress($commit->getAuthor()->email);
            $users[$glitUserEmail->getAddress()] = array(
                'name' => $glitUserEmail->getUser()->getUniqueName(),
                'link' => $this->generateUrl('glit_core_account_view', array('uniqueName' => $glitUserEmail->getUser()->getUniqueName()))
            );
        }

        return array('project'      => $project,
                     'branch'       => $branchName,
                     'history'      => $history,
                     'historyUsers' => $users);
    }

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

    /**
     * @return \Glit\GitoliteBundle\Admin\Gitolite
     */
    protected
    function getGitoliteAdmin() {
        return $this->get('glit_gitolite.admin');
    }

}