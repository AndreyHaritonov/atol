<?php

require __DIR__ . '/vendor/autoload.php';

use Andrey\Atol\Memcached;

$client = new Memcached();
$client->debugMode = true;
$client->connect('tcp://192.168.10.20:11211');
try {
    $value = $client->get('key');
    if ($value !== '') {
        throw new RuntimeException("Unexpected value ({$value})");
    }
    $client->set('key', 'xyz', 3600);
    $value = $client->get('key');
    if ($value !== 'xyz') {
        throw new RuntimeException("Unexpected value ({$value})");
    }
} finally {
    $client->close();
}
