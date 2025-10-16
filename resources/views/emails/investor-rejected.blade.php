@php
  $appName      = $appName      ?? 'ZUMA';
  $brandPrimary = '#ff4929';   // barra roja
  $brandButton  = '#1ab256';   // botón WhatsApp
  $title        = 'No pudimos validar tu registro';
  $userName     = $userName     ?? 'Usuario';
  $companyAddr  = 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú'; // opcional
  $prefsUrl     = '#'; // opcional
  $supportPhone =  config('app.support_phone') ?? '+51 986 351 267';
  $url_zuma    = env('CLIENT_APP_URL', 'https://zuma.com.pe');
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  <style>
    @media (max-width:600px){
      .container{width:100%!important;padding:0 16px!important}
      .hero-title{font-size:20px!important;line-height:26px!important}
    }
    a{text-decoration:none}
    .btn{display:inline-block;padding:12px 18px;border-radius:28px;font-weight:700;color:#fff}
  </style>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;">
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <!-- Barra superior -->
     <tr>
            <td align="center" style="padding:0 0 0 0;">
                <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
                    style="width:640px;max-width:640px;background:#fd4a2a;color:#E5E7EB;">
                    <tr>
                        <td style="padding:5px 0; text-align:center;">

                            <img src="{{ asset('imagenes/zuma-logo.png') }}" width="300" height="40" alt="Logo"
                                style="display:block;margin:5px auto ;">

                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    <!-- Card -->
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">

        <!-- Barra superior (misma anchura que la tarjeta 640px) -->
       

          <tr>
            <td style="padding:0 32px 6px 32px;color:#111;font:400 14px/22px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
              <p style="margin:0 0 14px 0;">Hola <strong>{{ $userName }}</strong>,</p>

              <p style="margin:0 0 12px 0;color:#374151;">
                Tu cuenta fue <strong>rechazada</strong> durante la validación. Para continuar,
                por favor ingresa y vuelve a completar tus datos y <strong>sube las fotos de tu DNI</strong>
                correctamente.
              </p>

              <p style="margin:0 0 8px 0;color:#111;"><strong>Antes de reenviar, asegúrate de:</strong></p>
              <ul style="margin:0 0 14px 22px;padding:0;color:#111;">
                <li style="margin:0 0 6px 0;">Fotos del DNI <strong>claras y legibles</strong> (sin reflejos ni recortes).</li>
                <li style="margin:0 0 6px 0;">Datos <strong>completos y correctos</strong> (número y nombres coinciden).</li>
                <li style="margin:0 0 6px 0;">Formato de imagen <strong>JPG o PNG</strong>.</li>
              </ul>

              <p style="text-align:center; margin:18px 0 22px 0;">
                <a href="{{ $url_zuma }}" class="btn"
                   style="background:#111827;">Ir a zuma</a>
              </p>

              <p style="margin:0 0 12px 0;color:#374151;">
                ¿Necesitas ayuda? Escríbenos por WhatsApp y con gusto te asistimos. <strong>{{ $supportPhone }}</strong>
              </p>

              

              <p style="margin:0;color:#111;">Gracias por tu comprensión,<br>
                El equipo de {{ $appName }}</p>

              <hr style="border:none;border-top:1px solid #E5E7EB;margin:22px 0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px 0;">
                <tr>
                  <td width="28" valign="top" style="padding:0 8px 0 0;">
                    <span style="display:inline-block;width:24px;height:24px;border-radius:999px;background:#111827;color:#FFFFFF;text-align:center;line-height:24px;font:700 14px/24px system-ui;">W</span>
                  </td>
                  <td style="font:500 13px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111;">
                    WhatsApp soporte: <strong>{{ $supportPhone }}</strong>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Footer oscuro -->
    <tr>
      <td align="center" style="padding:0 0 24px 0;">
        <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
               style="width:640px;max-width:640px;background:#0B0C0E;color:#E5E7EB;">
          <tr>
            <td style="padding:20px 24px; text-align:center;">
              <p style="margin:0 0 10px 0;font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#E5E7EB;">
                {{ $companyAddr }}
              </p>
              
              <p style="margin:0; font:500 11px/16px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; color:#A3A7AD;">
                © {{ date('Y') }} {{ $appName }}. Todos los derechos reservados.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
