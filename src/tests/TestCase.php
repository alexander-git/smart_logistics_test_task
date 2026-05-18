<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const RECEIVER_TABLE = 'receiver';
    protected const NOTIFICATION_TABLE = 'notification';
    protected const RECEIVER_NOTIFICATION_TABLE = 'receiver_notification';
    protected const HISTORY_TABLE = 'history';
    protected const OUTBOX_TABLE = 'outbox';
}
