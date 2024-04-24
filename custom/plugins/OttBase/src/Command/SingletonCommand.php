<?php declare(strict_types=1);

namespace Ott\Base\Command;

use Symfony\Component\Console\Command\Command;

abstract class SingletonCommand extends Command
{
    protected const LOCK_DIR = __DIR__ . '/../Resources/';
    protected const ERROR_NOTIFICATION_DEFAULT_SUBJECT = 'Error age of lockfile to old';
    protected const ERROR_NOTIFICATION_LOCKFILE_INTERVAL = 'Lock file is older than %s days';
    protected string $lockFile = '';

    public function __construct()
    {
        parent::__construct(static::class);

        if (!is_dir(self::LOCK_DIR)) {
            mkdir(self::LOCK_DIR);
        }

        $classMap = explode('\\', static::class);
        $lockFile = self::LOCK_DIR . array_pop($classMap);
        $this->lockFile = $lockFile;
    }

    protected function lockProcess(): void
    {
        file_put_contents($this->lockFile, '');
        $lockFile = $this->lockFile;

        register_shutdown_function(function () use ($lockFile): void {
            unlink($lockFile);
        });

        pcntl_async_signals(true);

        pcntl_signal(\SIGINT, function (): void {
            exit;
        });
        pcntl_signal(\SIGTERM, function (): void {
            exit;
        });

        pcntl_signal_dispatch();
    }

    protected function releaseProcess(): void
    {
        if ($this->hasLockFile()) {
            unlink($this->lockFile);
        }
    }

    protected function hasLockFile(
        int $intervalLockFileTimeCheck = 172800,
        bool $throwException = true,
        ?array $mailConfig = null
    ): bool
    {
        if (!file_exists($this->lockFile)) {
            return false;
        }

        if (filemtime($this->lockFile) < time() - $intervalLockFileTimeCheck) {
            if ($throwException) {
                throw new \Exception(sprintf(static::ERROR_NOTIFICATION_LOCKFILE_INTERVAL, round($intervalLockFileTimeCheck / 86400, 2)));
            }
            if (null !== $mailConfig) {
                $mailConfig = array_merge($this->getDefaultMailConfig($intervalLockFileTimeCheck), $mailConfig);
                mail(
                    $mailConfig['recipient'],
                    $mailConfig['subject'],
                    $mailConfig['message'],
                    $mailConfig['header'],
                    $mailConfig['options'],
                );
            }
        }

        return true;
    }

    private function getDefaultMailConfig(int $intervalLockFileTimeCheck): array
    {
        return [
            'recipient' => 'monitoring@ottscho.de',
            'subject'   => static::ERROR_NOTIFICATION_DEFAULT_SUBJECT,
            'message'   => sprintf(
                static::ERROR_NOTIFICATION_LOCKFILE_INTERVAL,
                round($intervalLockFileTimeCheck / 86400, 2)
            ),
            'header'  => [],
            'options' => '',
        ];
    }
}
