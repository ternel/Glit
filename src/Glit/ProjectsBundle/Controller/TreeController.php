<?php
namespace Glit\ProjectsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class TreeController extends BaseController {

    /**
     * @Route("{accountName}/{projectPath}/tree/{branchName}/{path}", requirements={"path" = ".+"})
     */
    public function viewAction($accountName, $projectPath, $branchName, $path) {
        /** @var $repository \Glit\GitoliteBundle\Git\Repository */
        /** @var $project \Glit\ProjectsBundle\Entity\Project */
        list($project, $repository) = $this->validateProject($accountName, $projectPath);

        $commit = $repository->getBranch($branchName)->getTip();

        /** @var $object \Glit\GitoliteBundle\Git\TreeNode */
        $object = $repository->getBranch($branchName)->getTip()->find($path);

        $params = array(
            'project'    => $project,
            'branchName' => $branchName,
            'path'       => $path,
            'commit'     => $commit,
            'node'       => $object
        );

        if ($object->getIsDir()) {
            return $this->forward('GlitProjectsBundle:Tree:viewTree', $params);
        }
        else {
            return $this->forward('GlitProjectsBundle:Tree:viewBlob', $params);
        }
    }

    /**
     * @Template()
     */
    public function viewBlobAction($project, $branchName, $commit, $path, $node) {
        $breadcrumb = $this->generateBreadcrumb($project->getOwner()->getUniqueName(), $project->getPath(), $branchName, $path);

        /** @var $treeObject \Glit\GitoliteBundle\Git\Blob */
        $treeObject = $node->getObject();

        return array(
            'project' => $project,
            'branch'  => $branchName,
            'path'    => $breadcrumb,
            'data'    => $treeObject->data,
            'size'    => \Glit\CoreBundle\Utils\StringObject::staticStrBytes($treeObject->data)
        );
    }

    /**
     * @Template()
     */
    public function viewTreeAction($project, $branchName, $commit, $path, \Glit\GitoliteBundle\Git\TreeNode $node) {
        $breadcrumb = $this->generateBreadcrumb($project->getOwner()->getUniqueName(), $project->getPath(), $branchName, $path);

        /** @var $treeObject \Glit\GitoliteBundle\Git\Tree */
        $treeObject = $node->getObject();
        $treeObject->setPath(DS . $path);
        $data = $treeObject->getAllData($commit);

        return array(
            'project' => $project,
            'branch'  => $branchName,
            'path'    => $breadcrumb,
            'tree'    => $data
        );
    }

    protected function generateBreadcrumb($accountName, $projectPath, $branchName, $path) {
        $tempPath = '';
        $instance = $this;

        return array_map(function($item) use($instance, $accountName, $projectPath, $branchName, &$tempPath) {
            if (strlen($tempPath) > 0) {
                $tempPath .= DS;
            }
            $tempPath .= $item;

            return array(
                'text' => $item,
                'link' => $instance->generateUrl('glit_projects_tree_view', array(
                                                                                 'accountName' => $accountName,
                                                                                 'projectPath' => $projectPath,
                                                                                 'branchName'  => $branchName,
                                                                                 'path'        => $tempPath
                                                                            ))
            );
        }, explode('/', $path));
    }
}