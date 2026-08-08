<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DiagnosisSession;

$s = DiagnosisSession::with(['user.profile', 'doctor.user'])
    ->where('session_hash', 'rev-urgent-ht')
    ->first();

if (!$s) {
    echo "Session not found\n";
} else {
    echo "Found: " . $s->session_hash . "\n";
    echo "User: " . ($s->user ? $s->user->full_name : 'NULL') . "\n";
    echo "Profile: " . ($s->user && $s->user->profile ? 'exists' : 'NULL') . "\n";
    if ($s->user && $s->user->profile) {
        print_r($s->user->profile->toArray());
    }
}
