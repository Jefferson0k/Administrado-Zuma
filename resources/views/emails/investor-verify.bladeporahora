@php
  $appName   = 'ZUMA';
  $userName  = isset($investor?->name) && trim($investor->name) !== '' ? $investor->name : 'Usuario';
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gracias por tu interés</title>
  <style>
    @media (max-width:600px){
      .container{width:100%!important;padding:0 18px!important}
      h1{font-size:22px!important;line-height:28px!important}
    }
    a{text-decoration:none}
  </style>
</head>
<body style="margin:0;padding:0;background:#F7F7F5;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
      <td align="center" style="padding:24px 0;">
        <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="width:640px;max-width:640px;background:#FFFFFF;margin:0 auto;border-radius:8px">
          <tr>
            <td style="text-align:center;padding:28px 24px 6px 24px">
              <!-- “logo” como texto para compatibilidad total -->
              <div style="font:800 44px/1 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111;">zuma</div>
              <h1 style="margin:18px 0 6px 0;font:700 26px/32px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111;">
                Gracias por tu interés
              </h1>
            </td>
          </tr>

          <tr>
            <td style="padding:0 34px 6px 34px;text-align:center;color:#111;font:400 15px/24px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
              <p style="margin:0 0 14px 0;">Hola <strong>{{ $userName }}</strong>,</p>

              <p style="margin:0 0 12px 0;">
                ¡Gracias por tu interés en <strong>{{ $appName }}</strong>!<br>
                Nos alegra mucho que te hayas registrado en nuestra plataforma.
              </p>

              <p style="margin:0 0 12px 0;">
                Queremos contarte que por ahora la web se encuentra en una fase de
                <strong>pruebas internas</strong>, por lo que todavía no estamos
                habilitando el acceso a usuarios.
                <strong>Muy pronto te comunicaremos la fecha de lanzamiento oficial.</strong>
              </p>

              <p style="margin:0 0 18px 0;">
                Mientras tanto, mantendremos tu registro en pausa y te avisaremos en cuanto
                todo esté listo para que seas de los primeros en acceder a la experiencia completa.
              </p>

              <!-- Icono simple y seguro-->
              {{-- <img src="{{ asset('imagenes/personitareloj.png') }}" width="64" height="80" alt="Aviso" style="display:block;margin:0 auto 10px auto;">
 --}}

              <p style="margin:0 0 6px 0;">
                Gracias nuevamente por confiar en nosotros,<br>
                <strong>El equipo de {{ $appName }}</strong>
              </p>
            </td>
          </tr>

          <tr>
            <td style="height:24px"></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
