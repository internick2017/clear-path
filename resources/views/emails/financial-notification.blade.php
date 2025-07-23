<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Financial Notification' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .notification-card {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .notification-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .notification-message {
            color: #4a5568;
            margin-bottom: 15px;
        }
        .details {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: 500;
            color: #4a5568;
        }
        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .alert-warning {
            border-left-color: #f59e0b;
        }
        .alert-success {
            border-left-color: #10b981;
        }
        .alert-danger {
            border-left-color: #ef4444;
        }
        .alert-info {
            border-left-color: #3b82f6;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                box-shadow: none;
            }
            .content {
                padding: 20px 15px;
            }
            .header {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ClearPath Financial</h1>
            <p>Your Financial Management Companion</p>
        </div>

        <div class="content">
            <div class="notification-card {{ $alertClass ?? 'alert-info' }}">
                <div class="notification-title">{{ $title ?? 'Financial Notification' }}</div>
                <div class="notification-message">{{ $message ?? 'You have a new financial notification.' }}</div>

                @if(isset($details) && count($details) > 0)
                    <div class="details">
                        @foreach($details as $label => $value)
                            <div class="detail-row">
                                <span class="detail-label">{{ $label }}:</span>
                                <span class="detail-value">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(isset($actionUrl) && isset($actionText))
                    <a href="{{ $actionUrl }}" class="cta-button">{{ $actionText }}</a>
                @endif
            </div>

            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                This notification was sent from your ClearPath Financial account.
                If you have any questions, please contact our support team.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} ClearPath Financial. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}/profile">Manage Notifications</a> |
                <a href="{{ config('app.url') }}">Visit Dashboard</a>
            </p>
        </div>
    </div>
</body>
</html>