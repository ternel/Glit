<?php
namespace Glit\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Glit\UserBundle\Entity as Entity;

class InitCommand extends ContainerAwareCommand {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this->setName('glit:users:initialize');
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

        $admin = new Entity\User();
        $admin->setUsername('admin');
        $encoder = $this->getEncoderFactory()->getEncoder($admin);
        $admin->setPassword($encoder->encodePassword('password', $admin->getSalt()));
        $admin->setEmail('admin@localhost');
        $admin->setFirstname('Admin');
        $admin->setLastname('Istrator');

        $em->persist($admin);
        $em->flush();

        return 0;
    }

    /**
     * @return \Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface
     */
    protected function getEncoderFactory() {
        return $this->getContainer()->get('security.encoder_factory');
    }

}