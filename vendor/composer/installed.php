<?php return array(
    'root' => array(
        'name' => 'kkigomi/kg_passkeys',
        'pretty_version' => '0.1.0',
        'version' => '0.1.0.0',
        'reference' => NULL,
        'type' => 'gnuboard-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'kkigomi/kg_passkeys' => array(
            'pretty_version' => '0.1.0',
            'version' => '0.1.0.0',
            'reference' => NULL,
            'type' => 'gnuboard-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'lbuchs/webauthn' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '4780c7b017ccc74a023c6ae05b5847e478f5b97d',
            'type' => 'library',
            'install_path' => __DIR__ . '/../lbuchs/webauthn',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
