<?php
namespace Glit\GitoliteBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class TestCommand extends ContainerAwareCommand {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('gitolite:test');
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var $gitoliteAdmin \Glit\GitoliteBundle\Admin\Gitolite */
        //$gitoliteAdmin = $this->getContainer()->get('glit_gitolite.admin');

        $repo = new \Glit\GitoliteBundle\Git\Repository('/home/git/repositories/gitolite-admin.git', $this->getContainer()->get('logger'));

        return 0;
    }

}