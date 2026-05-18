<?php

namespace App\Services\SmsSender;

Enum SmsSendResult
{
    case Success;
    case TemporaryUnavailable;
    case NotCorrectPhone;
}
