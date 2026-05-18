<?php

declare(strict_types=1);

namespace App\Services\EmailSender;

class FakeEmailSender implements EmailSenderInterface
{
    public function sendEmail(string $email, string $subject, string $text): EmailSendResult
    {
        return match (rand(1, 4)) {
            1 => EmailSendResult::Success,
            2 => EmailSendResult::TemporaryUnavailable,
            3 => EmailSendResult::NotCorrectEmail,
            4 => throw new EmailSenderException(),
        };
    }
}
