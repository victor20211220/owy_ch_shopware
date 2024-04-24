<?php declare(strict_types=1);

namespace Ott\Base\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HtmlEntityDecodeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('htmlEntityDecode', [$this, 'htmlEntityDecode']),
        ];
    }

    public function htmlEntityDecode(
        string $string,
        int $flags = \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML401,
        ?string $encoding = null
    ): string
    {
        return html_entity_decode($string, $flags, $encoding ?? 'UTF-8');
    }
}
