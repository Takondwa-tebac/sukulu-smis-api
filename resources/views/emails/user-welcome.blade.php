<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Sukulu SMIS</title>
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
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .credentials {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials p {
            margin: 8px 0;
        }
        .credentials strong {
            color: #1e40af;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Welcome to Sukulu SMIS</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $user->first_name ?? $user->name }}</strong>,</p>
            
            <p>Your account has been created on the Sukulu School Management Information System. You can now access the platform to manage school activities.</p>
            
            <div class="credentials">
                <p><strong>Email:</strong> {{ $user->email }}</p>
                @if($temporaryPassword)
                <p><strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
                @endif
            </div>
            
            @if($temporaryPassword)
            <div class="warning">
                ⚠️ <strong>Important:</strong> Please change your password after your first login for security purposes.
            </div>
            @endif
            
            <p style="text-align: center;">
                <a href="{{ $loginUrl }}" class="btn">Login to Your Account</a>
            </p>
            
            <p>If you have any questions or need assistance, please contact your school administrator.</p>
        </div>
        
        <div class="footer">
            <p>This email was sent by Sukulu SMIS.</p>
            <p>If you did not expect this email, please contact your school administrator.</p>
        </div>
    </div>
</body>
</html>
