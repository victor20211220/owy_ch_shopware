<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerKN0vMdU\Shopware_Core_KernelProdContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerKN0vMdU/Shopware_Core_KernelProdContainer.php') {
    touch(__DIR__.'/ContainerKN0vMdU.legacy');

    return;
}

if (!\class_exists(Shopware_Core_KernelProdContainer::class, false)) {
    \class_alias(\ContainerKN0vMdU\Shopware_Core_KernelProdContainer::class, Shopware_Core_KernelProdContainer::class, false);
}

return new \ContainerKN0vMdU\Shopware_Core_KernelProdContainer([
    'container.build_hash' => 'KN0vMdU',
    'container.build_id' => '436167bd',
    'container.build_time' => 1712164376,
], __DIR__.\DIRECTORY_SEPARATOR.'ContainerKN0vMdU');
