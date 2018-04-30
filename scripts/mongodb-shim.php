#!/usr/bin/env php
<?php
/**
 * Mongodb Shim for Doctrine for PHP 7.0 and above
 *
 * @author Tom H Anderson <tom.h.anderson@gmail.com>
 */

$version = explode('.', PHP_VERSION);
if ($version[0] <= 5) {
    return;
}

$config = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
$change = false;

if (! in_array('alcaeus/mongo-php-adapter', array_keys($config['require']))) {
    $config['require']['alcaeus/mongo-php-adapter'] = '^1.1';
    $change = true;
}

if (! isset($config['config'])
    || ! isset($config['config']['platform'])
    || ! isset($config['config']['platform']['ext-mongo'])
) {
    $config['config']['platform']['ext-mongo'] = '1.6.16';
    $change = true;
}

if ($change) {
    echo "Including MongoDB shim alcaeus/mongo-php-adapter\n";
    $contents = json_encode($config, JSON_PRETTY_PRINT);
    $contents = str_replace('\\/', '/', $contents);
    file_put_contents(__DIR__ . '/../composer.json', $contents);
}
