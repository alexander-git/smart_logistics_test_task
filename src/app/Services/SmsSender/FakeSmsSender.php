<?php

declare(strict_types=1);

namespace App\Services\SmsSender;

class FakeSmsSender implements SmsSenderInterface
{
    public function sendSms(string $phone, string $text): SmsSendResult
    {
        return match (rand(1, 4)) {
            1 => SmsSendResult::Success,
            2 => SmsSendResult::TemporaryUnavailable,
            3 =>  SmsSendResult::NotCorrectPhone,
            4 => throw new SmsSenderException(),
        };
    }
}
