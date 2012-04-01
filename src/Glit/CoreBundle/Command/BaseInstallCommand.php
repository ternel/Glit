<?php
namespace Glit\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Process\Process;

abstract class BaseInstallCommand extends ContainerAwareCommand {

    /**
     * @param $command
     * @return \Symfony\Component\Process\Process
     * @throws RuntimeException
     */
    protected function execProcess($command) {
        $this->log('Execute : ' . $command);
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        $this->log('Result : ' . $process->getOutput());

        return $process;
    }

    protected function checkPackageInstalled($package) {
        $process = $this->execProcess("dpkg-query -W -f='\${Status}\n' $package");
        return strpos($process->getOutput(), 'install ok installed') !== false;
    }

    protected abstract function log($text);
}