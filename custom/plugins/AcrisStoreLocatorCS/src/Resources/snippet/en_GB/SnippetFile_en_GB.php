<?php declare(strict_types=1);

namespace Acris\StoreLocator\Resources\snippet\en_GB;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_en_GB implements SnippetFileInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'storefront.en-GB';
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return __DIR__ . '/storefront.en-GB.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getIso(): string
    {
        return 'en-GB';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return 'ACRIS Plugin Prototype';
    }

    /**
     * {@inheritdoc}
     */
    public function isBase(): bool
    {
        return false;
    }
}
