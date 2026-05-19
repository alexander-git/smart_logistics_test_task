<?php

return [
    'retention_days' => (int) env('OUTBOX_RETENTION_DAYS', 7),
];
