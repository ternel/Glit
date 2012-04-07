<?php
namespace Glit\UserBundle\Mailer;

class EmailsMailer extends \Glit\CoreBundle\Mailer\BaseMailer {

    public function sendActivation(\Glit\UserBundle\Entity\Email $email) {
        $message = \Swift_Message::newInstance()
            ->setSubject('Activate your Email')
            ->setFrom($this->getSenderEmail(), 'Glity')
            ->setTo($email->getAddress())
            ->setBody($this->renderView(
            'GlitUserBundle:Emails:Mailer/activate.txt.twig',
            array(
                 'link' => $this->generateUrl('glit_user_emails_activate', array('id'           => $email->getId(),
                                                                                'activationKey' => $email->getActivationKey()), true)
            )));

        $this->send($message);
    }

}