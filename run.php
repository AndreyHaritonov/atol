<?php

require __DIR__ . '/vendor/autoload.php';

use Andrey\Atol\MemCached;

$client = new MemCached();
$client->debugMode = true;
$client->connect('tcp://192.168.10.20:11211');
try {
    $client->delete('key');
    echo "Deleted\n";
    $value = $client->get('key');
    echo "Value: {$value}\n";
    $client->set('key', 'xyz', 3600);
    echo "Set\n";
    $value = $client->get('key');
    echo "Value: {$value}\n";
} finally {
    $client->close();
}
