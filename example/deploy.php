<?php


require __DIR__.'/../vendor/autoload.php';



\Mengx\MysqlSchema\Schema::init(
    '127.0.0.1',
    'root',
    'root',
    'test'
);


\Mengx\MysqlSchema\Schema::deploy(__DIR__.'/tables');

