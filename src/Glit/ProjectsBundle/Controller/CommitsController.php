<?php
namespace Glit\ProjectsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CommitsController extends BaseController {

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
        list($project, $repository) = $this->validateProject($accountName, $projectPath);

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