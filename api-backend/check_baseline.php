<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$s = App\Models\DiagnosisSession::where('session_hash', 'ae6319d5-13c3-4ad9-bd5d-7149e24c6c2c')->first();
$baseline = json_decode($s->candidates ?? "{}", true)["baseline"] ?? [];
echo "Baseline keys: " . implode(", ", array_keys($baseline)) . PHP_EOL;
print_r($baseline);
