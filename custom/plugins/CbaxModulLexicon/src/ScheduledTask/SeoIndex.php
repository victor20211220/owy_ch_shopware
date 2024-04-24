<?php declare(strict_types=1);

namespace Cbax\ModulLexicon\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class SeoIndex extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'cbax.lexikon_seo_index';
    }

    public static function getDefaultInterval(): int
    {
        return 86400;
    }
}
