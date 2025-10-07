@php
  // Marca
  $appName = $appName ?? 'ZUMA';
  $brandPrimary = $brandPrimary ?? '#fd4a2a';
  $brandButton = $brandButton ?? '#2563eb';
  $logoUrl = $logoUrl ?? '';

  // Contenido
  $title = $title ?? 'Completa el formato PEP';
  $userName = $userName ?? 'Usuario';
  $pepFormUrl = $pepFormUrl ?? '#';

  // Soporte & footer
  $companyAddr = $companyAddr ?? '';
  $prefsUrl = $prefsUrl ?? '#';
  $whatsappUrl = $whatsappUrl ?? '#';
  $supportPhone = $supportPhone ?? '';
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
        padding: 0 16px !important;
      }

      .hero-title {
        font-size: 20px !important;
        line-height: 26px !important;
      }
    }

    a {
      text-decoration: none;
    }

    .btn {
      display: inline-block;
      padding: 12px 18px;
      border-radius: 28px;
      font-weight: 700;
      color: #fff;
    }

    .muted {
      color: #374151;
    }

    ol {
      padding-left: 18px;
      margin: 0 0 14px 0;
    }

    li {
      margin: 0 0 6px 0;
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

              <img src="{{ asset('imagenes/zuma-logo.png') }}" width="370" height="90" alt="Logo"
                style="display:block;margin:0 auto ;">

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

              {{-- Ícono simple tipo nota (opcional) --}}
              <img src="{{ asset('imagenes/personitadocumento.png') }}" width="64" height="80" alt="Aviso"
                style="display:block;margin:0 auto 10px auto;">


              <h1 class="hero-title"
                style="margin:8px 0 8px 0;font:800 22px/28px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; color:#111827; text-decoration: underline;">
                {{ $title }}
              </h1>
            </td>
          </tr>

          <tr>
            <td
              style="padding:0 32px 6px 32px; color:#111827; font:400 14px/22px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
              <p style="margin:0 0 14px 0;">Hola <strong>{{ $userName }}</strong>,</p>

              <p class="muted" style="margin:0 0 12px 0;">
                En tu registro indicaste que eres <strong>Persona Expuesta Políticamente (PEP)</strong> o que tienes
                relación con un familiar que lo es.
              </p>

              <p class="muted" style="margin:0 0 12px 0;">
                Para continuar con el proceso de validación de tu cuenta debes:
              </p>

              <ol>
                <li><strong>Descargar el Formato PEP.</strong></li>
                <li><strong>Completarlo y firmarlo.</strong></li>
                <li><strong>Reenviarlo como respuesta a este correo</strong> (foto, imagen o PDF).</li>
              </ol>

              <p style="text-align:center; margin:18px 0 22px 0;">
                <a href="{{ $pepFormUrl }}" class="btn"
                  style="background: {{ $brandButton }}; box-shadow:0 2px 0 rgba(0,0,0,0.12);">
                  Formato PEP
                </a>
              </p>

              <p style="margin:0 0 12px 0;" class="muted">
                Gracias por tu colaboración,<br>El equipo de {{ $appName }}
              </p>

              <hr style="border:none;border-top:1px solid #D1D5DB;margin:18px 0 14px 0;">
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

    <!-- Footer oscuro -->
    <tr>
      <td align="center" style="padding:0 0 24px 0;">
        <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
          style="width:640px;max-width:640px;background:#0B0C0E;color:#E5E7EB;">
          <tr>
            <td style="padding:20px 24px; text-align:center;">
              <p
                style="margin:0 0 10px 0;font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#E5E7EB;">
                {{ $companyAddr }}
              </p>
              <p style="margin:0 0 10px 0;">
                <a href="{{ $prefsUrl }}"
                  style="color:#A3A7AD; font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
                  Gestionar preferencias
                </a>
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