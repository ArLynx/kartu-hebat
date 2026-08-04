<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

$obsoleteFiles = [
    'app/Http/Controllers/Student/ScholarshipRegistrationController.php',
    'app/Models/ScholarshipCategory.php',
    'app/Models/ScholarshipPeriod.php',
    'database/migrations/2026_07_20_000000_add_scholarship_registration.php',
    'database/migrations/2026_07_20_010000_repair_scholarship_registration_schema.php',
    'database/seeders/ScholarshipMasterDataSeeder.php',
    'resources/views/student/registration.blade.php',
];

foreach ($obsoleteFiles as $relativePath) {
    $path = $projectRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (is_file($path)) {
        unlink($path);
        echo "Menghapus integrasi lama: {$relativePath}".PHP_EOL;
    }
}
