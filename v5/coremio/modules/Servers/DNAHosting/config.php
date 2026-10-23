<?php
    return [
        'name'                 => 'DNA Reseller Hosting',
        'meta'                 => [
            'name'    => 'DNA Reseller Hosting',
            'version' => '2.0.0',
            'author'  => 'DomainNameAPI',
        ],
        'type'                 => 'hosting',
        // The token goes into the Password field, which WISECP stores encrypted.
        // The Access Hash column is plain text, so the field is not offered.
        'use-access-hash'      => false,
        'use-test-connection'  => true,
        'use-port'             => true,
        'not-secure-port'      => 2086,
        'secure-port'          => 2087,
        'fields'               => [],
        'help'                 => 'https://github.com/domainreseller/reseller-hosting-wisecp',
        'supported'            => [
            'cards' => [
                'gauges'       => ['disk_space', 'bandwidth'],
                'account_info' => ['server', 'domain', 'username', 'home_dir', 'status'],
                'quick_links'  => ['panel_login'],
            ],
        ],
        'metrics'              => ['disk_space', 'bandwidth'],
        'service-relationship' => 'domain',
    ];
