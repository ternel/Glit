<?php
namespace Glit\ProjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Glit\GitoliteBundle\Git\Repository;
use Glit\GitoliteBundle\Git\Commit;
use Glit\CoreBundle\Utils\StringObject;

class DefaultController extends BaseController {

    /**
     * @Route("/projects/")
     * @Template()
     */
    public function indexAction() {
        return array();
    }

    /**
     * @Route("{accountName}/{projectPath}")
     * @Template()
     */
    public function viewAction($accountName, $projectPath) {
        list($project, $repository) = $this->validateProject($accountName, $projectPath);

        $branch = $project->getDefaultBranch();

        /** @var $commit \Glit\GitoliteBundle\Git\Commit */
        $commit      = $repository->getBranch($branch)->getTip();
        $commit_user = $this->findGitUser($commit->getAuthor());

        $treeData = $commit->getTree()->getAllData($commit);

        return array('project'     => $project,
                     'commit_user' => $commit_user,
                     'branch'      => $branch,
                     'commit'      => $commit,
                     'tree'        => $treeData['nodes'],
                     'readme'      => $treeData['readme']);
    }

    /**
     * @Route("{uniqueName}/projects/new")
     * @Template()
     */
    public
    function newAction($uniqueName) {
        $account = $this->getDoctrine()->getRepository('GlitCoreBundle:Account')->findOneByUniqueName($uniqueName);
        $scope   = $uniqueName == $this->getCurrentUser()->getUniqueName() ? 'user' : 'organization';

        // Check if current user can create project for this account
        if ($account != null && $scope != 'user') {
            // user create project for another.
            // scope : organization

            // TODO : Check rights
        }

        if (null === $account) {
            throw $this->createNotFoundException('Account not found to create project.');
        }

        $project = new \Glit\ProjectsBundle\Entity\Project($account);
        $form    = $this->createForm(new \Glit\ProjectsBundle\Form\ProjectType(), $project);

        if ('POST' == $this->getRequest()->getMethod()) {
            $form->bindRequest($this->getRequest());
            if ($form->isValid()) {
                $this->getDefaultEntityManager()->persist($project);

                // Create repository
                $keys = array();

                switch ($scope) {
                    case 'user':
                        foreach ($this->getCurrentUser()->getSshKeys() as $k) {
                            /** @var $k \Glit\UserBundle\Entity\SshKey */
                            $keys[] = $k->getKeyIdentifier();
                        }
                        break;
                }

                $this->getGitoliteAdmin()->createRepository($project->getFullPath(), $keys);

                $this->getDefaultEntityManager()->flush();

                $this->setFlash('success', sprintf('Projet %s successfully created !', $project->getName()));
                return $this->redirect($this->generateUrl('_welcome'));
            }
        }

        return array('uniqueName' => $uniqueName,
                     'form'       => $form->createView());
    }

    /**
     * Display initialization page
     */
    public function emptyAction($project) {
        return $this->render('GlitProjectsBundle:Default:view-empty.html.twig', array('project' => $project,
                                                                                     'ssh'      => 'git@dev.glit.fr:' . $project->getFullPath() . '.git'));
    }
}
