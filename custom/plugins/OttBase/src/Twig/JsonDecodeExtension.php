<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class JsonDecodeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('json_decode', fn (string $json): array => $this->json_decode($json)),
        ];
    }

    public function json_decode(string $json): array
    {
        return json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
    }
}
