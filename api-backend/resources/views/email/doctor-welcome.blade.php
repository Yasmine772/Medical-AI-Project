<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Welcome to MediAI</title>
</head>

<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #222;">

    <h2 style="font-weight: normal; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
        MediAI
    </h2>

    <p>Dr. {{ $user->full_name }},</p>

    <p>We are pleased to inform you that your doctor account has been approved.</p>

    <p>Your account is now active. You may begin reviewing patient reports and providing medical advice.</p>

    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Specialization:</strong> {{ $doctorRequest->specialization }}</p>

    <p>
        <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/doctor/login"
            style="display: inline-block; background: #222; color: #fff; padding: 10px 30px; text-decoration: none;">
            Login to Dashboard
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