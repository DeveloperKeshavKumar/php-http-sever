<?php

require __DIR__ . '/../../vendor/autoload.php';

use PhpHttpServer\Template\Grind;

$temp = new Grind(__DIR__);

echo $temp->render('example.grd', [
    'score' => 55,
]);