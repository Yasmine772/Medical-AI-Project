<!-- resources/views/legal/terms-of-use.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Terms & Conditions</title>

    <style>
        /* ===== Reset & Base ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #1a2332;
            padding: 16px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* ===== Container ===== */
        .container {
            max-width: 820px;
            width: 100%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 32px 28px 40px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== Header ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding-bottom: 16px;
            border-bottom: 2px solid #eef2f7;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #1a2332;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1 .icon {
            font-size: 30px;
        }

        .header .badge {
            background: #eef2f7;
            color: #4a5568;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 30px;
            letter-spacing: 0.3px;
        }

        /* ===== Last Updated ===== */
        .update-date {
            background: #f7fafc;
            padding: 12px 16px;
            border-radius: 12px;
            border-left: 4px solid #4a6cf7;
            margin-bottom: 28px;
            font-size: 14px;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .update-date strong {
            color: #1a2332;
        }

        /* ===== Content ===== */
        .content {
            line-height: 1.8;
            font-size: 15.5px;
            color: #2d3748;
        }

        .content h2 {
            font-size: 19px;
            font-weight: 700;
            color: #1a2332;
            margin-top: 28px;
            margin-bottom: 10px;
            padding-left: 14px;
            border-left: 4px solid #4a6cf7;
        }

        .content p {
            margin-bottom: 14px;
            color: #4a5568;
        }

        .content ul {
            padding-left: 28px;
            margin-bottom: 18px;
            list-style: none;
        }

        .content ul li {
            margin-bottom: 8px;
            color: #4a5568;
            position: relative;
            padding-left: 22px;
        }

        .content ul li::before {
            content: "•";
            color: #4a6cf7;
            font-size: 20px;
            font-weight: bold;
            position: absolute;
            left: 0;
            top: -2px;
        }

        .content ul li strong {
            color: #1a2332;
        }

        /* ===== Highlight Box ===== */
        .highlight-box {
            background: #f0f4ff;
            border-left: 4px solid #4a6cf7;
            padding: 16px 20px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .highlight-box p {
            margin: 0;
            color: #2d3748;
            font-weight: 500;
        }

        /* ===== Footer ===== */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eef2f7;
            text-align: center;
            font-size: 13px;
            color: #a0aec0;
        }

        .footer a {
            color: #4a6cf7;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* ===== Responsive ===== */
        @media (max-width: 640px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 20px 16px 28px;
                border-radius: 16px;
            }

            .header h1 {
                font-size: 20px;
            }

            .header h1 .icon {
                font-size: 24px;
            }

            .header .badge {
                font-size: 11px;
                margin-top: 4px;
            }

            .content {
                font-size: 14.5px;
            }

            .content h2 {
                font-size: 17px;
            }

            .update-date {
                font-size: 13px;
                padding: 10px 14px;
            }
        }

        @media (max-width: 400px) {
            .container {
                padding: 16px 12px 22px;
            }

            .header h1 {
                font-size: 18px;
            }

            .content {
                font-size: 14px;
            }

            .content h2 {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                Terms & Conditions
            </h1>
            <span class="badge">Effective: Jan 2025</span>
        </div>

        <!-- Last Updated -->
        <div class="update-date">
            <span><strong>Last Updated:</strong> January 15, 2025</span>
        </div>

        <!-- Content -->
        <div class="content">

            <h2>1. Acceptance of Terms</h2>
            <p>
                By downloading, accessing, or using this mobile application ("App"), you agree to be bound by
                these Terms & Conditions. If you do not agree to these terms, please do not use the App.
            </p>

            <h2>2. Eligibility</h2>
            <ul>
                <li><strong>Age Requirement:</strong> You must be at least 18 years old to create an account.</li>
                <li><strong>Accuracy:</strong> You agree to provide accurate and complete information during registration.</li>
                <li><strong>Account Security:</strong> You are responsible for maintaining the confidentiality of your account credentials.</li>
            </ul>

            <h2>3. Use of the App</h2>
            <p>
                The App is intended for medical and diagnostic purposes only. You agree not to use the App:
            </p>
            <ul>
                <li>For any unlawful or illegal activity</li>
                <li>To impersonate another person or entity</li>
                <li>To transmit any harmful or malicious content</li>
                <li>In any way that could damage, disable, or impair the App</li>
            </ul>

            <div class="highlight-box">
                <p>
                    ⚠️ <strong>Medical Disclaimer:</strong> This App provides preliminary medical information only.
                    It is not a substitute for professional medical advice, diagnosis, or treatment.
                    Always consult a qualified healthcare provider.
                </p>
            </div>

            <h2>4. Medical Data & Privacy</h2>
            <ul>
                <li><strong>Confidentiality:</strong> All medical data entered is encrypted and kept confidential.</li>
                <li><strong>Usage:</strong> Your data is used solely to provide and improve our services.</li>
                <li><strong>Sharing:</strong> We do not share your medical data with third parties without your explicit consent.</li>
            </ul>

            <h2>5. Limitation of Liability</h2>
            <p>
                To the fullest extent permitted by law, we shall not be liable for any indirect, incidental,
                special, consequential, or punitive damages arising from your use of the App.
            </p>

            <h2>6. Changes to Terms</h2>
            <p>
                We reserve the right to update these Terms & Conditions at any time. You will be notified of
                significant changes via email or through the App. Continued use of the App constitutes acceptance
                of the updated terms.
            </p>

            <h2>7. Contact Us</h2>
            <p>
                If you have any questions about these Terms & Conditions, please reach out to us:
            </p>
            <ul>
                <li>📧 <strong>support@example.com</strong></li>
                <li>📞 <strong>+966 50 000 0000</strong></li>
            </ul>

        </div>

        <!-- Footer -->
        <div class="footer">
            © 2025 All Rights Reserved
            <br>
            <a href="/legal/privacy-policy">Privacy Policy</a>
        </div>
    </div>

</body>

</html>