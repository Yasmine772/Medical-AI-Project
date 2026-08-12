<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DiagnosisSession;

$s = DiagnosisSession::with(['user.profile', 'doctor.user'])
    ->where('session_hash', 'test-dm-6a76d283d247e')
    ->first();

$out = [
    'session_hash'  => $s->session_hash,
    'phase'         => $s->phase,
    'patient_data'  => $s->user?->profile ? [
        'age' => $s->user->profile->birth_date ? \Carbon\Carbon::parse($s->user->profile->birth_date)->age : null,
        'gender' => $s->user->profile->gender,
        'smoker' => $s->user->profile->is_smoker,
        'diabetes' => $s->user->profile->has_diabetes,
        'hypertension' => $s->user->profile->has_hypertension,
        'pregnant' => $s->user->profile->is_pregnant,
        'activity_level' => $s->user->profile->activity_level,
    ] : null,
    'symptoms'      => $s->symptoms,
    'ai_result'     => $s->ai_result,
    'doctor_notes'  => $s->doctor_notes,
    'doctor'        => $s->doctor ? [
        'full_name'      => $s->doctor->user->full_name ?? null,
        'specialization' => $s->doctor->specialization ?? null,
        'phone'          => $s->doctor->phone ?? null,
    ] : null,
    'profile'       => $s->user?->profile ? [
        'blood_type' => $s->user->profile->blood_type,
        'occupation' => $s->user->profile->occupation,
        'drinks_alcohol' => $s->user->profile->drinks_alcohol,
    ] : null,
];

$json = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
file_put_contents(__DIR__ . '/dump_session.json', $json);
echo "WROTE dump_session.json\n";
echo "doctor_notes = {$s->doctor_notes}\n";
