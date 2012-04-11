<?php
namespace Glit\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Glit\UserBundle\Entity as Entity;
use Symfony\Component\Console\Input\InputOption;

class InitCommand extends ContainerAwareCommand {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('glit:users:initialize')
            ->addOption('username', '', InputOption::VALUE_REQUIRED, 'Username')
            ->addOption('password', '', InputOption::VALUE_REQUIRED, 'Password')
            ->addOption('firstname', '', InputOption::VALUE_REQUIRED, 'Firstname')
            ->addOption('lastname', '', InputOption::VALUE_REQUIRED, 'Lastname')
            ->addOption('email', '', InputOption::VALUE_REQUIRED, 'Email');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var $doctrine \Symfony\Bundle\DoctrineBundle\Registry */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var $em \Doctrine\Common\Persistence\ObjectManager */
        $em = $doctrine->getEntityManager();

        $user = new Entity\User();

        $user->setUsername($input->getOption('username'));

        $encoder = $this->getEncoderFactory()->getEncoder($user);
        $user->setPassword($encoder->encodePassword($input->getOption('password'), $user->getSalt()));

        $user->setEmail($input->getOption('email'));
        $user->setFirstname($input->getOption('firstname'));
        $user->setLastname($input->getOption('lastname'));

        $em->persist($user);
        $em->flush();

        $output->write('<info>

The user was successfully created !

</info>');

        return 0;
    }

    /**
     * @return \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    protected function getEncoderFactory() {
        return $this->getContainer()->get('security.encoder_factory');
    }

}