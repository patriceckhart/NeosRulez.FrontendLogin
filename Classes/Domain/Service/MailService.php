<?php

namespace NeosRulez\FrontendLogin\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @Flow\Scope("singleton")
 */
class MailService
{

    /**
     * @param array $variables
     * @param string $sender
     * @param string $recipient
     * @param string $subject
     * @param string $templatePathAndFilename
     * @return void
     */
    public function sendMail(array $variables, string $sender, string $recipient, string $subject, string $templatePathAndFilename):void
    {
        $view = new \Neos\FluidAdaptor\View\StandaloneView();
        $view->setTemplatePathAndFilename($templatePathAndFilename);
        $view->assignMultiple($variables);
        $mail = new \Neos\SwiftMailer\Message();
        $mail
            ->setFrom($sender)
            ->setTo($recipient)
            ->setSubject($subject);
        $mail->setBody($view->render(), 'text/html');
        $mail->send();
    }

}
