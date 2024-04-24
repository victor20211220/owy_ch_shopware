<?php declare(strict_types=1);

namespace Ott\SelectlineImport\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImportProcessorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $processors = [];
        foreach ($container->findTaggedServiceIds('ott.selectline_import_processor') as $id => $options) {
            if ($container->hasAlias($id)) {
                continue;
            }

            $processors[] = $id;
        }

        $container->setParameter('ott.selectline_import_processors', $processors);
    }
}
