<!-- resources/views/app/updates/latest.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Latest Updates</title>

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

        .header .version-badge {
            background: #4a6cf7;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 4px 16px;
            border-radius: 30px;
            letter-spacing: 0.3px;
        }

        /* ===== Version Cards ===== */
        .version-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px 22px;
            margin-bottom: 18px;
            border-left: 4px solid #4a6cf7;
            transition: all 0.2s ease;
        }

        .version-card:last-child {
            margin-bottom: 0;
        }

        .version-card .version-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 6px;
        }

        .version-card .version-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1a2332;
        }

        .version-card .version-header .tag {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 12px;
            border-radius: 30px;
            letter-spacing: 0.5px;
        }

        .tag-new {
            background: #10b981;
            color: white;
        }

        .tag-updated {
            background: #f59e0b;
            color: white;
        }

        .tag-fixed {
            background: #ef4444;
            color: white;
        }

        .version-card .date {
            font-size: 13px;
            color: #718096;
            margin-bottom: 10px;
            display: block;
        }

        .version-card ul {
            padding-left: 24px;
            margin: 6px 0 0;
            list-style: none;
        }

        .version-card ul li {
            margin-bottom: 6px;
            color: #4a5568;
            font-size: 14.5px;
            position: relative;
            padding-left: 22px;
        }

        .version-card ul li::before {
            content: "▸";
            color: #4a6cf7;
            font-weight: bold;
            position: absolute;
            left: 0;
            top: 0;
        }

        /* ===== Footer ===== */
        .footer {
            margin-top: 32px;
            padding-top: 18px;
            border-top: 2px solid #eef2f7;
            text-align: center;
            font-size: 13px;
            color: #a0aec0;
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

            .header .version-badge {
                font-size: 11px;
                margin-top: 4px;
                padding: 3px 12px;
            }

            .version-card {
                padding: 16px 16px;
            }

            .version-card .version-header h3 {
                font-size: 16px;
            }

            .version-card ul li {
                font-size: 14px;
            }
        }

        @media (max-width: 400px) {
            .container {
                padding: 16px 12px 22px;
            }

            .header h1 {
                font-size: 18px;
            }

            .version-card {
                padding: 14px 14px;
            }

            .version-card .version-header h3 {
                font-size: 15px;
            }

            .version-card ul li {
                font-size: 13.5px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                 Latest Updates
            </h1>
            <span class="version-badge">v1.0.0</span>
        </div>

        <!-- Version 1.0.0 - Initial Release -->
        <div class="version-card" style="border-left-color: #10b981;">
            <div class="version-header">
                <h3>Version 1.0.0</h3>
                <span class="tag tag-new">Initial Release</span>
            </div>
            <span class="date">📅 [Release Date]</span>
            <p style="color: #4a5568; margin: 8px 0 12px; font-weight: 500;">
                The first version of MediAI is now available!
            </p>
            <ul>
                <li> AI-powered symptom diagnosis</li>
                <li> Medical history tracking</li>
                <li> Patient-doctor communication</li>
                <li> Health advice and recommendations</li>
                <li> Medical report generation</li>
                <li> OTP verification for secure login</li>
                <li> notifications</li>
                <li> User-friendly interface</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
             Thank you for choosing MediAI!
        </div>
    </div>

</body>

</html>