<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Doctor Application Status - MediAI</title>
</head>

<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #222;">

    <h2 style="font-weight: normal; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
        MediAI
    </h2>

    <p>Dr. {{ $doctorName }},</p>

    <p>Thank you for your interest in joining MediAI as a doctor.</p>

    <p>After careful review of your application, we regret to inform you that your request to register as a doctor on our platform has been <strong style="color: #d9534f;">declined</strong>.</p>

    <div style="background-color: #f8f9fa; border-left: 4px solid #d9534f; padding: 15px 20px; margin: 20px 0; border-radius: 4px;">
        <p style="margin: 0; font-weight: bold; color: #d9534f;">Reason for rejection:</p>
        <p style="margin: 5px 0 0 0; color: #333;">{{ $rejectionReason }}</p>
    </div>

    <p>We encourage you to review the feedback provided above and consider reapplying in the future once the necessary adjustments have been made.</p>

    <p>We appreciate your understanding and wish you the best in your professional endeavors.</p>

    <p>If you have any questions, please don't hesitate to contact us at <a href="mailto:support@mediai.com">support@mediai.com</a>.</p>

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