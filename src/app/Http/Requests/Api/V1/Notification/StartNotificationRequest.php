<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => [
                'required',
                'string',
            ],
            'channel' => [
                'required',
                Rule::Enum(NotificationChannel::class),
            ],
            'type' => [
                'required',
                Rule::Enum(NotificationType::class),
            ],
            'receiverIds' => [
                'required',
                'array',
                'min:1',
            ],
            'receiverIds.*' => [
                'integer',
                'distinct',
                'min:1',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('channel')) {
                    return;
                }

                $channel = NotificationChannel::from($this->input('channel'));
                $maxLength = match($channel) {
                    NotificationChannel::Sms => 160,
                    NotificationChannel::Email => 10000,
                };

                $text = $this->input('text');
                if (mb_strlen($text) > $maxLength) {
                    $validator->errors()->add(
                        'text',
                        "The text field must not be greater than $maxLength characters."
                    );
                }
            }
        ];
    }
}
