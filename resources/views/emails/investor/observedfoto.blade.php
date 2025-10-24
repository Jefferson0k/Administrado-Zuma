@php
  $appName = $appName ?? 'zuma';
  $brandPrimary = '#fd4a2a';     // rojo barra/logo
  $ctaColor = '#6790ff';     // azul del botón principal
  $title = $title ?? 'Tu validación facial no pudo completarse';
  $userName = $userName ?? 'Nombre del usuario';
  $companyAddr = $companyAddr ?? 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú';
  $prefsUrl = $prefsUrl ?? '#';
  $supportPhone = $supportPhone ?? '+51 999 999 999';
  $ctaUrl = $ctaUrl ?? 'https://www.zuma.com.pe/login'; // <- URL para volver a subir la foto
@endphp
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>
  <style>
    @media (max-width: 600px) {
      .container {
        width: 100% !important;
        padding: 0 16px !important
      }

      .hero-title {
        font-size: 20px !important;
        line-height: 26px !important
      }
    }

    a {
      text-decoration: none
    }

    .btn {
      display: inline-block;
      padding: 12px 18px;
      border-radius: 28px;
      font-weight: 700;
      color: #fff
    }
  </style>
</head>

<body style="margin:0;padding:0;background:#F3F4F6;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">

    <!-- Barra superior (misma anchura que la tarjeta 640px) -->
    <tr>
      <td align="center" style="padding:0 0 0 0;">
        <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
          style="width:640px;max-width:640px;background:#fd4a2a;color:#E5E7EB;">
          <tr>
            <td style="padding:5px 0; text-align:center;">

              <img src="{{ asset('imagenes/zuma-logo.png') }}" width="275" height="35" alt="Logo"
                style="display:block;margin:7.5px auto ;">

            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Tarjeta (GRIS) -->
    <tr>
      <td align="center">
        <table class="container darkmode-bg" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
          bgcolor="#f7f7f7" style="width:640px;max-width:640px;background:#f7f7f7;margin:0 auto;">
          <tr>
            <td style="padding:8px 32px 8px 32px;text-align:center;">

             
              <h1 class="hero-title"
                style="margin:8px 0;font:800 22px/28px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111827;text-decoration:underline;">
                {{ $title }}
              </h1>
            </td>
          </tr>

          <tr>
            <td
              style="padding:0 32px 6px 32px;color:#111827;font:400 14px/22px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
              <p style="margin:0 0 14px 0;">Hola <strong>{{ $userName }}</strong>,</p>

              <p style="margin:0 0 12px 0;color:#374151;">
                Detectamos que la foto enviada para tu validación facial no cumple con los requisitos de calidad
                (borrosa, poco iluminada o con baja nitidez). Para proteger tu seguridad y completar el proceso de
                verificación, necesitamos que subas nuevamente tu foto.
              </p>

              <p style="margin:0 6px 8px 0;"><strong>Recomendaciones para tu nueva foto:</strong></p>
              <ul style="margin:0 0 14px 22px;padding:0;color:#111827;">
                <li style="margin:0 0 6px 0;">Usa buena iluminación (de preferencia luz natural).</li>
                <li style="margin:0 0 6px 0;">Asegúrate de que tu rostro esté enfocado y visible completamente.</li>
                <li style="margin:0 0 6px 0;">Evita sombras, accesorios o filtros.</li>
              </ul>

              <p style="margin:0 0 14px 0;color:#111827;font-weight:700;">
                Haz clic en el botón de abajo y carga tu foto nuevamente para continuar con tu validación.
              </p>

              <p style="text-align:center;margin:18px 0 22px 0;">
                <a href="{{ $ctaUrl }}" class="btn"
                  style="background: {{ $ctaColor }}; box-shadow:0 2px 0 rgba(0,0,0,.12);">
                  Subir nueva foto
                </a>
              </p>

              <p style="margin:0 0 12px 0;color:#374151;">
                Este proceso es 100% seguro y tiene como objetivo proteger tu información y garantizar que solo el
                titular tenga acceso a la cuenta.
              </p>

              <p style="margin:0;">Gracias por tu colaboración,<br>El equipo de {{ strtoupper($appName) }}</p>

              <hr style="border:none;border-top:1px solid #D1D5DB;margin:22px 0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px 0;">
                <tr>
                  <td width="28" valign="top" style="padding:0 8px 0 0;">
                    <span
                      style="display:inline-block;width:24px;height:24px;border-radius:999px;background:#111827;color:#FFFFFF;text-align:center;line-height:24px;font:700 14px/24px system-ui;">W</span>
                  </td>
                  <td style="font:500 13px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111827;">
                    ¿Necesitas ayuda? Escríbenos a nuestro <strong>WhatsApp {{ $supportPhone }}</strong>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td align="center" style="padding:0 0 24px 0;">
        <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
          style="width:640px;max-width:640px;background:#0B0C0E;color:#E5E7EB;">
          <tr>
            <td style="padding:20px 24px;text-align:center;">
              <p
                style="margin:0 0 10px 0;font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#E5E7EB;">
                {{ $companyAddr }}
              </p>
              <p style="margin:0 0 10px 0;">
                <a href="{{ $prefsUrl }}"
                  style="color:#A3A7AD;font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">Gestionar
                  preferencias</a>
              </p>
              <p style="margin:0;font:500 11px/16px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#A3A7AD;">
                © {{ date('Y') }} {{ strtoupper($appName) }}. Todos los derechos reservados.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>