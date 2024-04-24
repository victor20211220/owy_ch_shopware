<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HashExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('hash', [$this, 'hash']),
        ];
    }

    public function hash(string $data, string $algo, bool $binary = false): string
    {
        return hash($algo, $data, $binary);
    }
}
