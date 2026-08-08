<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>New Diagnosis Assigned</title>
</head>

<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #222;">

    <h2 style="font-weight: normal; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
        MediAI
    </h2>

    <p>{{ $session->doctor?->user?->full_name ?? 'Dr.' }},</p>

    <p>A new AI diagnosis has been assigned to you for review. Please log in to the dashboard to review the case and provide your medical notes.</p>

    @if($patient)
        <h3 style="border-bottom: 1px solid #e0e0e0; padding-bottom: 6px;">Patient</h3>
        <table style="border-collapse: collapse; width: 100%;">
            @if(!empty($patient['age']))
                <tr><td style="padding: 4px 0; width: 140px; color: #555;">Age:</td><td>{{ $patient['age'] }}</td></tr>
            @endif
            @if(!empty($patient['gender']))
                <tr><td style="padding: 4px 0; width: 140px; color: #555;">Gender:</td><td>{{ $patient['gender'] }}</td></tr>
            @endif
            @if(array_key_exists('smoker', $patient))
                <tr><td style="padding: 4px 0; width: 140px; color: #555;">Smoker:</td><td>{{ $patient['smoker'] ? 'Yes' : 'No' }}</td></tr>
            @endif
        </table>
    @endif

    @if(count($symptoms ?? []) > 0)
        <h3 style="border-bottom: 1px solid #e0e0e0; padding-bottom: 6px;">Symptoms</h3>
        <ul>
            @foreach($symptoms as $symptom)
                <li>{{ $symptom }}</li>
            @endforeach
        </ul>
    @endif

    @if(count($aiResult ?? []) > 0)
        <h3 style="border-bottom: 1px solid #e0e0e0; padding-bottom: 6px;">AI Diagnostic Results</h3>
        <table style="border-collapse: collapse; width: 100%;">
            @foreach($aiResult as $diagnosis)
                <tr>
                    <td style="padding: 6px 0; border-bottom: 1px solid #f0f0f0;">{{ $diagnosis['disease_name_local'] ?? '' }}</td>
                    <td style="padding: 6px 0; border-bottom: 1px solid #f0f0f0; text-align: right;">
                        {{ isset($diagnosis['probability']) ? $diagnosis['probability'] . '%' : '—' }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if(count($tips ?? []) > 0)
        <h3 style="border-bottom: 1px solid #e0e0e0; padding-bottom: 6px;">Recommended Tips</h3>
        <ul>
            @foreach($tips as $tip)
                <li>{{ $tip }}</li>
            @endforeach
        </ul>
    @endif

    <p style="margin-top: 24px;">
        <a href="http://localhost:5173/login"
            style="display: inline-block; background: #222; color: #fff; padding: 10px 30px; text-decoration: none;">
            Review Diagnosis
        </a>
    </p>

    <p>For inquiries, contact <a href="mailto:support@mediai.com">support@mediai.com</a>.</p>

    <p>
        Yours sincerely,<br>
        <strong>MediAI Team</strong>
    </p>

    <hr style="border: none; border-top: 1px solid #e0e0e0; margin-top: 30px;">

    <p style="font-size: 12px; color: #888; text-align: center;">
        &copy; {{ date('Y') }} MediAI. All rights reserved.
    </p>

</body>

</html>
