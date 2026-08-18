<!-- resources/views/legal/privacy-policy.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Privacy Policy</title>

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
        }

        /* ===== Last Updated ===== */
        .update-date {
            background: #f7fafc;
            padding: 12px 16px;
            border-radius: 12px;
            border-left: 4px solid #10b981;
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
            border-left: 4px solid #10b981;
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
            color: #10b981;
            font-size: 20px;
            font-weight: bold;
            position: absolute;
            left: 0;
            top: -2px;
        }

        .content ul li strong {
            color: #1a2332;
        }

        .highlight-box {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
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
            color: #10b981;
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
                <span class="icon">🔒</span> Privacy Policy
            </h1>
            <span class="badge">Effective: Jan 2025</span>
        </div>

        <!-- Last Updated -->
        <div class="update-date">
            <span>🕒</span>
            <span><strong>Last Updated:</strong> January 15, 2025</span>
        </div>

        <!-- Content -->
        <div class="content">

            <h2>1. Information We Collect</h2>
            <p>
                We collect the following types of information to provide and improve our services:
            </p>
            <ul>
                <li><strong>Personal Information:</strong> Name, email address, phone number</li>
                <li><strong>Medical Data:</strong> Symptoms, diagnosis history, medical reports</li>
                <li><strong>Usage Data:</strong> How you interact with the App</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>
                Your information is used for the following purposes:
            </p>
            <ul>
                <li>Providing diagnostic services and medical information</li>
                <li>Improving and personalizing your experience</li>
                <li>Sending updates, notifications, and important information</li>
                <li>Complying with legal and regulatory requirements</li>
            </ul>

            <h2>3. Data Sharing</h2>
            <p>
                We do not share your personal information with third parties except in the following cases:
            </p>
            <ul>
                <li>With your explicit consent</li>
                <li>When required by law or legal process</li>
                <li>With service providers who assist in operating the App</li>
                <li>To protect the rights, property, or safety of our users</li>
            </ul>

            <div class="highlight-box">
                <p>
                    <strong>Your Data Is Safe:</strong> We implement industry-standard security measures
                    to protect your data, including end-to-end encryption and secure servers.
                </p>
            </div>

            <h2>4. Data Retention</h2>
            <p>
                We retain your data only as long as necessary to provide the services or as required by law.
                You may request deletion of your account and associated data at any time.
            </p>

            <h2>5. Your Rights</h2>
            <ul>
                <li><strong>Access:</strong> You can view your personal data at any time</li>
                <li><strong>Correction:</strong> You can update or correct inaccurate data</li>
                <li><strong>Deletion:</strong> You can request permanent deletion of your data</li>
            </ul>

            <h2>6. Contact Us</h2>
            <p>
                If you have any questions about this Privacy Policy, please contact us:
            </p>
            <ul>
                <li>📧 <strong>privacy@example.com</strong></li>
                <li>📞 <strong>+966 50 000 0000</strong></li>
            </ul>

        </div>

        <!-- Footer -->
        <div class="footer">
            © 2025 All Rights Reserved
            <br>
            <a href="/legal/terms-of-use">Terms & Conditions</a>
        </div>
    </div>

</body>

</html>