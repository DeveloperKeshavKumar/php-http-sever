<?php

require __DIR__ . '/../../vendor/autoload.php';

use PhpHttpServer\Template\Grind;

$temp = new Grind(__DIR__);

echo $temp->render('example.grd', [
    'users' => [
        ['name' => 'John Doe', 'email' => 'john@example.com'],
        ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
    ],
]);