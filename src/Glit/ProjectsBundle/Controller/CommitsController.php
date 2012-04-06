<?php
namespace Glit\ProjectsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CommitsController extends Controller {

    /**
     * @Route("{accountName}/{projectPath}/commits/{commitName}")
     * @Template()
     */
    public function view($accountName, $projectPath, $commitName) {
        return array();
    }

}