<?php
namespace Glit\CoreBundle\Controller;

use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\TemplateReference;

class ExceptionController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller {

    /**
     * Converts an Exception to a Response.
     *
     * @param FlattenException     $exception A FlattenException instance
     * @param DebugLoggerInterface $logger    A DebugLoggerInterface instance
     * @param string               $format    The format to use for rendering (html, xml, ...)
     *
     * @throws \InvalidArgumentException When the exception template does not exist
     */
    public function showAction(FlattenException $exception, DebugLoggerInterface $logger = null, $format = 'html') {
        die('pouet');

        $this->container->get('request')->setRequestFormat($format);

        $currentContent = $this->getAndCleanOutputBuffering();

        $templating = $this->container->get('templating');
        $code       = $exception->getStatusCode();

        $response = $templating->renderResponse(
            $this->findTemplate($templating, $format, $code, $this->container->get('kernel')->isDebug()),
            array(
                 'status_code'    => $code,
                 'status_text'    => Response::$statusTexts[$code],
                 'exception'      => $exception,
                 'logger'         => $logger,
                 'currentContent' => $currentContent,
            )
        );

        $response->setStatusCode($code);
        $response->headers->replace($exception->getHeaders());

        return $response;
    }

    protected function getAndCleanOutputBuffering() {
        // the count variable avoids an infinite loop on
        // some Windows configurations where ob_get_level()
        // never reaches 0
        $count          = 100;
        $startObLevel   = $this->container->get('request')->headers->get('X-Php-Ob-Level', -1);
        $currentContent = '';
        while (ob_get_level() > $startObLevel && --$count) {
            $currentContent .= ob_get_clean();
        }

        return $currentContent;
    }

    protected function findTemplate($templating, $format, $code, $debug) {
        $name = $debug ? 'exception' : 'error';
        if ($debug && 'html' == $format) {
            $name = 'exception_full';
        }

        // when not in debug, try to find a template for the specific HTTP status code and format
        if (!$debug) {
            $template = new TemplateReference('GlitCoreBundle', 'Exception', $name . $code, $format, 'twig');
            if ($templating->exists($template)) {
                return $template;
            }
        }

        // try to find a template for the given format
        $template = new TemplateReference('GlitCoreBundle', 'Exception', $name, $format, 'twig');
        if ($templating->exists($template)) {
            return $template;
        }

        // default to a generic HTML exception
        $this->container->get('request')->setRequestFormat('html');

        return new TemplateReference('GlitCoreBundle', 'Exception', $name, 'html', 'twig');
    }

}
