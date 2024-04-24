<?php return array(
    'root' => array(
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'postfinancecheckout/shopware-6',
        'dev' => true,
    ),
    'versions' => array(
        'postfinancecheckout/sdk' => array(
            'pretty_version' => '4.0.2',
            'version' => '4.0.2.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../postfinancecheckout/sdk',
            'aliases' => array(),
            'reference' => 'a0b679bd37519108ea71252a245d939ac23699f9',
            'dev_requirement' => false,
        ),
        'postfinancecheckout/shopware-6' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
