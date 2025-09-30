@php
  $appName      = $appName      ?? 'ZUMA';
  $brandPrimary = '#ff4929';   // barra roja
  $brandDark    = '#111827';   // CTA principal
  $brandGreen   = '#1ab256';   // CTA WhatsApp
  $title        = '¡Tu cuenta fue aprobada! 🎉';
  $userName     = $userName     ?? 'Usuario';
  $companyAddr  = 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú'; // opcional
  $prefsUrl     = '#'; // opcional
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
      <td style="background: {{ $brandPrimary }}; padding: 18px 0; text-align:center;">
        <span style="font:800 30px/1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#000;letter-spacing:.5px;">
          {{ strtolower($appName) }}
        </span>
      </td>
    </tr>

    <!-- Card -->
    <tr>
      <td align="center">
        <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
               style="width:640px;max-width:640px;background:#FFFFFF;margin:0 auto;">
          <tr>
            <td style="padding:28px 32px 8px 32px; text-align:center;">
              <div style="font-size:56px;line-height:1;margin:0 0 8px 0;">✅</div>
              <h1 class="hero-title"
                  style="margin:6px 0 4px 0;font:800 22px/28px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111;">
                {{ $title }}
              </h1>
            </td>
          </tr>

          <tr>
            <td style="padding:0 32px 6px 32px;color:#111;font:400 14px/22px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
              <p style="margin:0 0 14px 0;">¡Felicidades, <strong>{{ $userName }}</strong>!</p>

              <p style="margin:0 0 12px 0;color:#374151;">
                Tu cuenta ha sido <strong>validada y aprobada</strong>. Desde ahora
                puedes acceder a todas las funcionalidades de la plataforma:
              </p>

              <ul style="margin:0 0 14px 22px;padding:0;color:#111;">
                <li style="margin:0 0 6px 0;">Realizar inversiones</li>
                <li style="margin:0 0 6px 0;">Gestionar tu portafolio</li>
                <li style="margin:0 0 6px 0;">Realizar depósitos y retiros</li>
              </ul>

              <p style="text-align:center; margin:18px 0 8px 0;">
                <a href="{{ $dashboardUrl }}" class="btn" style="background: {{ $brandDark }};">
                  Ir a mi Dashboard
                </a>
              </p>

              @if(!empty($whatsappUrl) && $whatsappUrl !== '#')
              <p style="text-align:center; margin:6px 0 22px 0;">
                <a href="{{ $whatsappUrl }}" class="btn"
                   style="background: {{ $brandGreen }}; box-shadow:0 2px 0 rgba(0,0,0,.12);">
                  WhatsApp {{ $appName }}
                </a>
              </p>
              @endif

              <p style="margin:0 0 12px 0;color:#374151;">
                ¡Bienvenido(a) a {{ $appName }}! Estamos felices de tenerte en nuestra comunidad de inversionistas.
              </p>

              <hr style="border:none;border-top:1px solid #E5E7EB;margin:22px 0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px 0;">
                <tr>
                  <td width="28" valign="top" style="padding:0 8px 0 0;">
                    <span style="display:inline-block;width:24px;height:24px;border-radius:999px;background:#111827;color:#FFFFFF;text-align:center;line-height:24px;font:700 14px/24px system-ui;">W</span>
                  </td>
                  <td style="font:500 13px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111;">
                    ¿Dudas? Escríbenos a nuestro WhatsApp <strong>{{ $supportPhone }}</strong>
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
                {{ $companyAddr ?? '' }}
              </p>
              <p style="margin:0 0 10px 0;">
                <a href="{{ $prefsUrl ?? '#' }}" style="color:#A3A7AD; font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
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
