<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.23',
    ],
    'firebase/app' => [
        'version' => '11.10.0',
    ],
    'firebase/auth' => [
        'version' => '11.10.0',
    ],
    '@firebase/app' => [
        'version' => '0.13.2',
    ],
    '@firebase/auth' => [
        'version' => '1.10.8',
    ],
    '@firebase/component' => [
        'version' => '0.6.18',
    ],
    '@firebase/logger' => [
        'version' => '0.4.4',
    ],
    '@firebase/util' => [
        'version' => '1.12.1',
    ],
    'idb' => [
        'version' => '7.1.1',
    ],
    'tslib' => [
        'version' => '2.8.1',
    ],
];
