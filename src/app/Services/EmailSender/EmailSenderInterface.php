<?php

declare(strict_types=1);

namespace App\Services\EmailSender;


interface EmailSenderInterface
{
    /**
     * @throws EmailSenderException
     */
    public function sendEmail(string $email, string $subject, string $text): EmailSendResult;
}
