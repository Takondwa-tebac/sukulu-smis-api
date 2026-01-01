<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Sukulu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
        }
        h1 {
            color: #1e40af;
            margin-bottom: 20px;
        }
        .credentials {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials h3 {
            margin-top: 0;
            color: #0369a1;
        }
        .credential-item {
            margin: 10px 0;
        }
        .credential-label {
            font-weight: 600;
            color: #64748b;
        }
        .credential-value {
            font-family: monospace;
            background-color: #e0f2fe;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #92400e;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .features {
            margin: 20px 0;
        }
        .features li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Sukulu</div>
            <p>School Management Information System</p>
        </div>

        <h1>Welcome, {{ $adminUser->name }}!</h1>

        <p>Congratulations! Your school <strong>{{ $school->name }}</strong> has been successfully onboarded to the Sukulu School Management Information System.</p>

        <div class="credentials">
            <h3>Your Login Credentials</h3>
            <div class="credential-item">
                <span class="credential-label">Email:</span>
                <span class="credential-value">{{ $adminUser->email }}</span>
            </div>
            @if($temporaryPassword)
            <div class="credential-item">
                <span class="credential-label">Temporary Password:</span>
                <span class="credential-value">{{ $temporaryPassword }}</span>
            </div>
            @endif
        </div>

        @if($temporaryPassword)
        <div class="warning">
            <strong>Important:</strong> Please change your password immediately after your first login for security purposes.
        </div>
        @endif

        <h3>What's Next?</h3>
        <ul class="features">
            <li>Log in to your dashboard and explore the system</li>
            <li>Set up your academic structure (classes, streams, subjects)</li>
            <li>Configure your grading system</li>
            <li>Add staff members and assign roles</li>
            <li>Start enrolling students</li>
        </ul>

        <h3>Available Modules</h3>
        <ul class="features">
            <li><strong>Academic Structure</strong> - Manage classes, streams, and subjects</li>
            <li><strong>Student Management</strong> - Enroll and manage students</li>
            <li><strong>Exams & Grading</strong> - Create exams and record marks</li>
            <li><strong>Report Cards</strong> - Generate and publish report cards</li>
            <li><strong>Fees & Billing</strong> - Manage fee structures and payments</li>
            <li><strong>Timetables</strong> - Create and manage class schedules</li>
            <li><strong>Attendance</strong> - Track student attendance</li>
            <li><strong>Notifications</strong> - Send SMS and email notifications</li>
        </ul>

        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Sukulu SMIS. All rights reserved.</p>
            <p>This is an automated message. Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
