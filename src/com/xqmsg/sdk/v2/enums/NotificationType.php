<?php namespace com\xqmsg\sdk\v2\enums;

/**
 * Class NotificationType
 * @package com\xqmsg\sdk\v2\enums
 */
class NotificationType {
    public const None = 0;
    public const ReceiveUsageReport   = 1;
    public const RecieveTutorials    = 2;
    public const All  = 1 | 2;
}