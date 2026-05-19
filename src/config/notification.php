<?php

return [
    'max_retries' => (int) env('NOTIFICATION_MAX_RETRIES', 3),
    'retry_after_minutes' => (int) env('NOTIFICATION_RETRY_AFTER_MINUTES', 5),
];
