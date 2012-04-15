<?php
namespace Glit\ProjectsBundle\Exception;

class ProjectNewException extends \Symfony\Component\HttpKernel\Exception\NotFoundHttpException {

    private $project;

    public function __construct($project, $previous = null) {
        $this->project = $project;

        parent::__construct(sprintf('Project %s not found', $project->getPath()), $previous);
    }

    public function getProject() {
        return $this->project;
    }

}