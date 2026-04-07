<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ScamStatusRecord;

$records = ScamStatusRecord::where('status_type', 'drafting')->whereNull('status_id')->orderBy('id', 'desc')->limit(10)->get();
echo "Recent Drafting Without Status records:\n";
foreach ($records as $r) {
    echo "ID: {$r->id}, Scam ID: {$r->scam_id}, Created At: {$r->created_at}\n";
}

$records = ScamStatusRecord::where('status_type', 'sales')->whereNull('status_id')->orderBy('id', 'desc')->limit(10)->get();
echo "\nRecent Sales Without Status records:\n";
foreach ($records as $r) {
    echo "ID: {$r->id}, Scam ID: {$r->scam_id}, Created At: {$r->created_at}\n";
}
