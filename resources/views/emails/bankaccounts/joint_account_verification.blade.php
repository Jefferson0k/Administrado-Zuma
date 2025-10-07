@php
  $appName = $appName ?? 'ZUMA';
  $brandPrimary = $brandPrimary ?? '#fd4a2a';
  $brandButton = $brandButton ?? '#3B82F6';
  $title = $title ?? 'Verificación de tu cuenta bancaria mancomunada';
  $userName = $userName ?? 'Usuario';
  $ctaUrl = $ctaUrl ?? env('CLIENT_APP_URL', 'https://zuma.com.pe');
  $companyAddr = $companyAddr ?? 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú';
  $prefsUrl = $prefsUrl ?? '#';
  $whatsappUrl = $whatsappUrl ?? '#';
  $supportPhone = $supportPhone ?? '+51 999 999 999';
@endphp
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }}</title>

  <!-- Tell clients we only support light colors (prevents auto-invert in many apps) -->
  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light">

  <style>
    @media (max-width:600px) {
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

    /* Dark-mode overrides for clients that still try to invert */
    @media (prefers-color-scheme: dark) {
      .darkmode-bg {
        background: #f7f7f7 !important;
      }

      .darkmode-text,
      .darkmode-text p,
      .darkmode-text span,
      .darkmode-text li {
        color: #111111 !important;
      }
    }

    /* Outlook.com/Windows Mail dark mode */
    [data-ogsc] .darkmode-bg {
      background: #f7f7f7 !important;
    }

    [data-ogsc] .darkmode-text {
      color: #111111 !important;
    }

    /* Gmail iOS often respects inline colors, but keep class anyway */
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
                            
                                <img src="{{ asset('imagenes/zuma-logo.png') }}" width="370" height="90"
                                    alt="Logo" style="display:block;margin:0 auto ;">
                            
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Tarjeta (GRIS) -->
        <tr>
            <td align="center">
                <table class="container darkmode-bg" role="presentation" width="640" cellspacing="0" cellpadding="0"
                    border="0" bgcolor="#f7f7f7" style="width:640px;max-width:640px;background:#f7f7f7;margin:0 auto;">
                    <tr>
                        <td style="padding:8px 32px 8px 32px;text-align:center;">

              <!-- Título -->
              <h1 class="hero-title darkmode-text"
                style="margin:8px 0;font:800 22px/28px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111111;text-decoration:underline;">
                {{ $title }}
              </h1>
            </td>
          </tr>

          <tr>
            <td class="darkmode-text"
              style="padding:0 32px 6px 32px;color:#111111;font:400 14px/22px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
              <p style="margin:0 0 14px 0;color:#111111;">Hola <strong>{{ $userName }}</strong>,</p>

               <p style="color:#374151;font-size:15px;text-align:left;max-width:520px;margin:0 auto 12px;">
                Hola <strong>{{ $userName }}</strong>,<br><br>
                Al validar los datos de tu cuenta bancaria, identificamos que la cuenta registrada es
                <strong>mancomunada</strong> (con más de un titular).
              </p>
              <p style="color:#374151;font-size:14px;text-align:left;max-width:520px;margin:0 auto 10px;">
                Te recordamos que en ZUMA solo podemos aceptar cuentas mancomunadas tipo <strong>“O”</strong>
                (cualquiera de los titulares puede operar), no las de tipo <strong>“Y”</strong>
                (requieren la autorización conjunta de ambos titulares).
              </p>

              <p style="color:#111827;font-size:15px;text-align:left;max-width:520px;margin:12px auto;font-weight:700;">
                Por favor responde a este correo adjuntando un certificado bancario en el que figure tu nombre completo
                como uno de los titulares de la cuenta.
              </p>

              <p style="color:#374151;font-size:14px;text-align:left;max-width:520px;margin:0 auto 14px;">
                Además, uno de nuestros asesores te contactará por WhatsApp para apoyarte en este proceso
                y resolver cualquier consulta que tengas.
              </p>

              <p style="color:#4B5563;font-size:13px;text-align:left;max-width:520px;margin:0 auto 14px;">
                Esta validación es necesaria para garantizar que tus operaciones se realicen de forma segura y conforme
                a la
                normativa vigente.
              </p>

              <!-- CTA -->
              <p style="text-align:center;margin:18px 0 22px 0;">
                <a href="{{ $ctaUrl }}" class="btn"
                  style="display:inline-block;padding:12px 18px;border-radius:28px;font-weight:700;color:#FFFFFF;background:{{ $brandButton }};box-shadow:0 2px 0 rgba(0,0,0,.12);">
                  Ir a ZUMA
                </a>
              </p>

              <p style="margin:0 0 12px 0;color:#111111;">
                Este proceso es necesario para garantizar que tus operaciones y transferencias se realicen de manera
                segura.
              </p>

              <p style="margin:0;color:#111111;">
                Gracias por tu colaboración,<br>El equipo de {{ $appName }}
              </p>

              <hr style="border:none;border-top:1px solid #D1D5DB;margin:22px 0;">

              <!-- WhatsApp ayuda -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px 0;">
                <tr>
                  <td width="28" valign="top" style="padding:0 8px 0 0;">
                    <span
                      style="display:inline-block;width:24px;height:24px;border-radius:999px;background:#111111;color:#FFFFFF;text-align:center;line-height:24px;font:700 14px/24px system-ui;">W</span>
                  </td>
                  <td class="darkmode-text"
                    style="font:500 13px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111111;">
                    ¿Necesitas ayuda? Escríbenos a nuestro <strong>WhatsApp {{ $supportPhone }}</strong>
                    @if(!empty($whatsappUrl) && $whatsappUrl !== '#')
                      &nbsp;<a href="{{ $whatsappUrl }}" style="color:#111111;text-decoration:underline;">Abrir
                        WhatsApp</a>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Pie oscuro -->
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
                  style="color:#A3A7AD;font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;text-decoration:underline;">
                  Gestionar preferencias
                </a>
              </p>
              <p style="margin:0;font:500 11px/16px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#A3A7AD;">
                © {{ $footerYear ?? date('Y') }} {{ $appName }}. Todos los derechos reservados.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

  </table>
</body>

</html>