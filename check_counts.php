<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ScamStatusRecord;

echo "Sales Without Status count: " . ScamStatusRecord::whereNull('status_id')->where('status_type', 'sales')->count() . "\n";
echo "Drafting Without Status count: " . ScamStatusRecord::whereNull('status_id')->where('status_type', 'drafting')->count() . "\n";
echo "Total records count: " . ScamStatusRecord::count() . "\n";
