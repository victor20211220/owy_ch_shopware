<?php declare(strict_types=1);

namespace Ott\SelectlineImport;

use Ott\Base\Bootstrap\CustomFieldService;
use Ott\SelectlineImport\DependencyInjection\ImportProcessorCompilerPass;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SelectlineImport extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ImportProcessorCompilerPass());
        parent::build($container);
    }

    public function install(InstallContext $context): void
    {
        $this->createCustomField($context);
    }

    public function activate(ActivateContext $context): void
    {
    }

    public function deactivate(DeactivateContext $context): void
    {
    }

    private function createCustomField($context): void
    {
        $customFieldService = $this->container->get(CustomFieldService::class);
        $customFieldService->addFieldSet(
            'customer',
            [
                'de-DE' => 'Kundengruppen Spezifikationen',
                'en-GB' => 'customergroup spetifications',
            ],
            [
                [
                    'entityName' => 'Customer',
                ],
            ]
        );

        $customFieldService->addField('customer', [
            'name'   => 'discountgroup',
            'type'   => CustomFieldTypes::TEXT,
            'config' => [
                'label' => [
                    'de-DE' => 'Rabattgruppe',
                    'en-GB' => 'discount group',
                ],
                'componentName'   => 'sw-text-field',
                'customFieldType' => 'textEditor',
            ],
        ]);

        $customFieldService->update($context);
    }
}
