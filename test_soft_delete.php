<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JobListing;

$job = JobListing::factory()->create();
echo 'Before delete: '.($job->deleted_at ?? 'null').PHP_EOL;
$job->delete();
$job->refresh();
echo 'After delete: '.($job->deleted_at ?? 'null').PHP_EOL;
