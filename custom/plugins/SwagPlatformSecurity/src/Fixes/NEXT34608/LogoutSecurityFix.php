<?php

namespace Swag\Security\Fixes\NEXT34608;

use Swag\Security\Components\AbstractSecurityFix;

class LogoutSecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-34608';
    }

    public static function getMinVersion(): string
    {
        return '6.4.0.0';
    }

    public static function getMaxVersion(): string
    {
        return '6.5.8.7';
    }
}
