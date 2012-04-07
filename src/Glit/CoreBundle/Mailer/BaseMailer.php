<?php
namespace Glit\CoreBundle\Mailer;

class BaseMailer {

    protected $mailer;
    protected $container;

    public function __construct(\Symfony\Component\DependencyInjection\Container $container) {
        $this->container = $container;
    }

    protected function getMailer() {
        return $this->container->get('mailer');
    }

    protected function getRouter() {
        return $this->container->get('router');
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string  $route      The name of the route
     * @param mixed   $parameters An array of parameters
     * @param Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    protected function generateUrl($route, $parameters = array(), $absolute = false) {
        return $this->getRouter()->generate($route, $parameters, $absolute);
    }

    protected function getSenderEmail() {
        return $this->container->getParameter('glit.email.from');
    }

    protected function send($message) {
        $this->container->get('mailer')->send($message);
    }

    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The renderer view
     */
    public function renderView($view, array $parameters = array()) {
        return $this->container->get('templating')->render($view, $parameters);
    }

}