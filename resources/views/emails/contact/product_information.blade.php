<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva Solicitud de Información</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: {{ $brandPrimary }}; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f9f9f9; }
        .info-card { background: white; padding: 25px; border-radius: 8px; margin: 20px 0; }
        .field { margin-bottom: 15px; }
        .field-label { font-weight: bold; color: #333; }
        .field-value { color: #666; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoUrl }}" alt="{{ $appName }}" style="max-height: 50px;">
        </div>
        
        <div class="content">
            <h2 style="color: {{ $brandPrimary }}; text-align: center;">Nueva Solicitud de Información</h2>
            
            <div class="info-card">
                <h3>Información del Cliente</h3>
                
                <div class="field">
                    <span class="field-label">Nombre completo:</span>
                    <span class="field-value">{{ $contactData['full_name'] }}</span>
                </div>
                
                <div class="field">
                    <span class="field-label">Teléfono:</span>
                    <span class="field-value">{{ $contactData['phone'] }}</span>
                </div>
                
                <div class="field">
                    <span class="field-label">Email:</span>
                    <span class="field-value">{{ $contactData['email'] }}</span>
                </div>
                
                <div class="field">
                    <span class="field-label">Producto de interés:</span>
                    <span class="field-value" style="color: {{ $brandPrimary }}; font-weight: bold;">
                        {{ $contactData['interested_product'] }}
                    </span>
                </div>
                
                @if(!empty($contactData['message']))
                <div class="field">
                    <span class="field-label">Mensaje adicional:</span>
                    <div class="field-value" style="margin-top: 10px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
                        {{ $contactData['message'] }}
                    </div>
                </div>
                @endif
                
                <div class="field">
                    <span class="field-label">Política aceptada:</span>
                    <span class="field-value">✅ Sí</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ $whatsappUrl }}" 
                   style="background: {{ $brandButton }}; color: white; padding: 12px 30px; 
                          text-decoration: none; border-radius: 5px; display: inline-block;">
                    Contactar al Cliente
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>{{ $companyAddr }}</p>
            <p>Teléfono: {{ $supportPhone }}</p>
            <p>© {{ $footerYear }} {{ $appName }}. Todos los derechos reservados.</p>
            <p><a href="{{ $prefsUrl }}" style="color: #666;">Preferencias de email</a></p>
        </div>
    </div>
</body>
</html>