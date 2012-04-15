<?php
namespace Glit\ProjectsBundle\EventListener;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExceptionListener {

    /** @var LoggerInterface */
    private $logger;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container, LoggerInterface $logger = null) {
        $this->logger    = $logger;
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event) {

        if (!($event->getException() instanceof \Glit\ProjectsBundle\Exception\ProjectNewException)) {
            return;
        }

        /** @var $exception \Glit\ProjectsBundle\Exception\ProjectNewException */
        $exception = $event->getException();

        /** @var $project \Glit\ProjectsBundle\Entity\Project */
        $project = $exception->getProject();

        /** @var $user \Glit\UserBundle\Entity\User */
        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($project->getOwner()->getUniqueName() == $user->getUniqueName()) {
            $event->setResponse($this->container->get('http_kernel')->forward('GlitProjectsBundle:Default:empty', array('project' => $project), array()));
            return;
        }
    }
}