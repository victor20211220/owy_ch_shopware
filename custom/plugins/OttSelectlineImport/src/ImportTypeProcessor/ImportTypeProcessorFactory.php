<?php declare(strict_types=1);

namespace Ott\SelectlineImport\ImportTypeProcessor;

use Symfony\Component\DependencyInjection\Container;

class ImportTypeProcessorFactory
{
    /**
     * @var ImportTypeProcessorInterface[]
     */
    private array $processors;

    public function __construct(Container $container, array $processors)
    {
        foreach ($processors as $processor) {
            $this->processors[] = $container->get($processor);
        }
    }

    public function getProcessor(string $type): ImportTypeProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->getType() === $type) {
                return $processor;
            }
        }

        throw new \Exception('Could not find processor for type ' . $type);
    }
}
