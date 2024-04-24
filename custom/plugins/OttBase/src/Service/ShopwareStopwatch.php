<?php declare(strict_types=1);

namespace Ott\Base\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class ShopwareStopwatch extends Stopwatch
{
    private ?OutputInterface $output = null;
    private ?string $lastLap = null;
    private array $activeSections;

    public function setOutput(?OutputInterface $output = null): void
    {
        $this->output = $output;
    }

    public function lap(?string $name = null): StopwatchEvent
    {
        if (null !== $this->lastLap) {
            $this->stopAndOutput($this->lastLap);
        }

        if (null !== $name) {
            $this->start($name);
        }

        $this->lastLap = $name;

        return end($this->activeSections)->stopEvent($name)->start();
    }

    public function stopAndOutput(string $name): void
    {
        if (null !== $this->output) {
            $this->output->writeln((string) $this->stop($name));
        } else {
            echo $this->stop($name) . \PHP_EOL;
        }
    }
}
