<?php

namespace Glit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class AccountController extends Controller
{
    /**
     * @Route("/{uniqueName}")
     */
    public function viewAction($uniqueName) {
        $account = $this->getDoctrine()->getRepository('GlitCoreBundle:Account')->findOneByUniqueName($uniqueName);

        switch($account->getType()) {
            case 'user':
                return $this->forward('GlitUserBundle:Default:view', array('user' => $account));
                break;
            case 'organization':

                break;
        }
        //return $this->forward()
    }
}
