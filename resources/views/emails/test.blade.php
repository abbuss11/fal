<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Mail - FAL PMS</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            padding: 32px 40px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .email-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin: 8px 0 0;
        }
        .email-body {
            padding: 40px;
        }
        .email-body p {
            font-size: 16px;
            line-height: 1.7;
            color: #4a5568;
            margin: 0 0 16px;
        }
        .success-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #059669;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            font-size: 13px;
            color: #9ca3af;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>{{ config('app.name') }}</h1>
            <p>Système de gestion de projets</p>
        </div>
        <div class="email-body">
            <div class="success-badge">✅ Configuration email fonctionnelle</div>
            <p>Bonjour,</p>
            <p>{{ $messageBody }}</p>
            <p>Si vous recevez cet email, cela signifie que la configuration SMTP de votre application est correctement paramétrée.</p>
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
