<?php

declare(strict_types=1);

namespace App\Services\SmsSender;

interface SmsSenderInterface
{
    /**
     * @throws SmsSenderException
     */
    public function sendSms(string $phone, string $text): SmsSendResult;
}
