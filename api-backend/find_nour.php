<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$u = App\Models\User::where('email', 'nour@test.com')->first();
if ($u) {
    echo "Found: " . $u->full_name . " (id=" . $u->id . ")\n";
} else {
    echo "NOT FOUND\n";
}
