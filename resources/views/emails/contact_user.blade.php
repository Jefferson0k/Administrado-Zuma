<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmación de Consulta - Zuma</title>
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
            background-color: #6790FF;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .highlight {
            background-color: #FF4929;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .info-box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #6790FF;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Gracias por contactarnos!</h1>
        <p>Zuma - Democratizando las inversiones</p>
    </div>
    
    <div class="content">
        <p>Hola <strong>{{ $contactData['full_name'] }}</strong>,</p>
        
        <p>Hemos recibido tu consulta sobre <strong>{{ $contactData['interested_product'] }}</strong> y queremos agradecerte por tu interés en nuestros servicios.</p>
        
        <div class="highlight">
            <strong>Tu consulta ha sido registrada exitosamente</strong>
        </div>
        
        <div class="info-box">
            <h3>¿Qué sigue ahora?</h3>
            <ul>
                <li>Nuestro equipo de asesores revisará tu consulta</li>
                <li>Te contactaremos en un plazo máximo de 24-48 horas</li>
                <li>Te brindaremos información personalizada sobre el producto de tu interés</li>
                <li>Resolveremos todas tus dudas sin compromiso</li>
            </ul>
        </div>
        
        @if(!empty($contactData['message']))
        <div class="info-box">
            <strong>Tu mensaje:</strong><br>
            {{ $contactData['message'] }}
        </div>
        @endif
        
        <p>Mientras tanto, te invitamos a conocer más sobre nuestros productos de inversión en nuestra plataforma.</p>
        
        <p><strong>¿Tienes alguna pregunta urgente?</strong><br>
        No dudes en contactarnos directamente:</p>
        
        <ul>
            <li>📧 Email: adminzuma@zuma.com.pe</li>
            <li>📱 Teléfono: {{ $contactData['phone'] }}</li>
        </ul>
        
        <div class="highlight">
            <p><strong>¡Invertir no solo es para unos pocos. Es para ti!</strong></p>
        </div>
    </div>
    
    <div class="footer">
        <p>Equipo Zuma | Conectar, educar y crear riqueza</p>
        <p>Este es un email automático, por favor no respondas a este mensaje.</p>
    </div>
</body>
</html>