<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

$directories = [
    'bootstrap/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
];

foreach ($directories as $relativeDirectory) {
    $directory = $projectRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

    if (is_dir($directory)) {
        continue;
    }

    if (! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        fwrite(STDERR, "Gagal membuat direktori runtime: {$directory}".PHP_EOL);
        exit(1);
    }
}
