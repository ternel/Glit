<?php
namespace Glit\ProjectsBundle\Controller;

abstract class BaseController extends \Glit\CoreBundle\GlitBaseController {

    /**
     * Validate project data
     * @param $accountName
     * @param $projectPath
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function validateProject($accountName, $projectPath) {
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

        $repository = $this->getGitoliteAdmin()->getRepository($project->getFullPath() . '.git');

        if ($repository->isNew()) {
            throw new \Glit\ProjectsBundle\Exception\ProjectNewException($project);
        }

        return array($project, $repository);
    }

    /**
     * Search the git user represented by commitStamp in glit database
     * @param \Glit\GitoliteBundle\Git\CommitStamp $commitStamp
     * @return array
     */
    protected function findGitUser(\Glit\GitoliteBundle\Git\CommitStamp $commitStamp) {
        $glitUserEmail = $this->getDoctrine()->getRepository('GlitUserBundle:Email')->findOneByAddress($commitStamp->email);

        $found = !is_null($glitUserEmail) && $glitUserEmail->getIsActive();

        return array(
            'name'   => $found ? $glitUserEmail->getUser()->getUniqueName() : $commitStamp->name,
            'url'    => $found ? $this->generateAccountPageUrl($glitUserEmail->getUser()) : null,
            'avatar' => null,
        );
    }

    /**
     * Generate url for account page
     * @param $account
     * @return string
     */
    protected function generateAccountPageUrl($account) {
        return $this->generateUrl('glit_core_account_view', array('uniqueName' => $account->getUniqueName()));
    }

    /**
     * @return \Glit\GitoliteBundle\Admin\Gitolite
     */
    protected
    function getGitoliteAdmin() {
        return $this->get('glit_gitolite.admin');
    }

}