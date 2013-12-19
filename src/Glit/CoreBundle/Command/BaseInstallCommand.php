<?php
namespace Glit\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Process\Process;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;

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

    protected function checkSshKeyExists($user) {
        $process = $this->execProcess(sprintf("sudo ls /home/%s/.ssh/id_rsa", $user));
        return strpos($process->getOutput(), 'No such file or directory') === false;
    }

    protected function removeSshKey($user) {
        $process = $this->execProcess(sprintf("sudo rm -rf /home/%s/.ssh/id_rsa", $user));
        $process = $this->execProcess(sprintf("sudo rm -rf /home/%s/.ssh/id_rsa.pub", $user));
    }

    protected abstract function log($text);

    protected function getDialogHelper() {
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog || get_class($dialog) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper') {
            $this->getHelperSet()->set($dialog = new DialogHelper());
        }

        return $dialog;
    }
}