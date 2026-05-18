<?php

namespace App\Services\EmailSender;

Enum EmailSendResult
{
    case Success;
    case TemporaryUnavailable;
    case NotCorrectEmail;
}
