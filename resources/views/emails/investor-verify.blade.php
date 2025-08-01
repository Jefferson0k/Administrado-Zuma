<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu correo - ZUMA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #fff;
            padding: 30px;
            border: 1px solid #dee2e6;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ZUMA</h1>
    </div>
    
    <div class="content">
        <h2>¡Hola {{ $investor->name }}!</h2>
        
        <p>Gracias por registrarte en nuestra plataforma de inversiones.</p>
        
        <p>Para completar tu registro y acceder a todas las funcionalidades, por favor verifica tu dirección de email haciendo clic en el siguiente botón:</p>
        
        <div style="text-align: center;">
            <a href="{{ $url }}" class="button">Verificar Email</a>
        </div>
        
        <p>Si el botón no funciona, puedes copiar y pegar este enlace en tu navegador:</p>
        <p style="word-break: break-all; color: #007bff;">{{ $url }}</p>
        
        <p><strong>Este enlace de verificación expirará en {{ config('auth.verification.expire', 60) }} minutos.</strong></p>
        
        <p>Si no creaste esta cuenta, puedes ignorar este email.</p>
    </div>
    
    <div class="footer">
        <p>Saludos,<br>Equipo ZUMA</p>
    </div>
</body>
</html>