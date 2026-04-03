<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "App Timezone: " . config('app.timezone') . "\n";
echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
echo "Current Time: " . now()->toDateTimeString() . "\n";
echo "Database Connection Timezone: " . config('database.connections.mysql.timezone') . "\n";
