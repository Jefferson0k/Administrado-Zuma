@php
    $appName = $appName ?? 'ZUMA';
    $brandPrimary = $brandPrimary ?? '#fd4a2a';
    $brandButton = $brandButton ?? '#3B82F6';
    $title = $title ?? 'Retiro observado';
    $userName = $notifiable->name ?? 'Usuario';
    $ctaUrl = $ctaUrl ?? env('CLIENT_APP_URL', 'https://zuma.com.pe');
    $companyAddr = $companyAddr ?? 'Av. Faustino Sánchez Carrión 417, Magdalena del Mar, Lima – Perú';
    $prefsUrl = $prefsUrl ?? '#';
    $depositAmount = $depositAmount ?? 'S/. 0.00';
    $depositDate = $depositDate ?? '';
    // $whatsappUrl = $whatsappUrl ?? '#';
    $supportPhone = $supportPhone ?? '+51 986 351 267';
    $moneda = $moneda ?? 'S/.';
    
    
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
                            <p style="color:#374151;font-size:15px;text-align:justify;max-width:520px;margin:0 auto 12px;">
                                Hola <strong>{{ $userName }}</strong>,<br><br>
                                Tu retiro se ha observado.
                            </p>

                            <p style="color:#374151;font-size:15px;text-align:justify;max-width:520px;margin:0 auto 12px;">
                             <strong>Monto: </strong>  {{ $monto }} </p>
                            
                             <p style="color:#374151;font-size:15px;text-align:justify;max-width:520px;margin:0 auto 12px;">
                                <strong>Fecha del retiro: </strong>{{ $fecha }}
                             </p>
                           


                            <p style="text-align:center;margin:18px 0 22px 0;">
                                <a href="{{ $ctaUrl }}" class="btn"
                                    style="display:inline-block;padding:12px 18px;border-radius:28px;font-weight:700;color:#FFFFFF;background:{{ $brandButton }};box-shadow:0 2px 0 rgba(0,0,0,.12);">
                                    Ir a ZUMA
                                </a>
                            </p>




                            <p style="color:#111827;font-size:13px;margin-top:18px;">Gracias por usar nuestros servicios. <br>El equipo de
                                {{ $appName }}
                            </p>

                            <hr style="border:none;border-top:1px solid #D1D5DB;margin:22px 0;">

                            <!-- WhatsApp ayuda -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin:0 0 6px 0;">
                                <tr>
                                    <td width="28" valign="top" style="padding:0 8px 0 0;">
                                        <span
                                            style="display:inline-block;width:24px;height:24px;border-radius:999px;background:#111111;color:#FFFFFF;text-align:center;line-height:24px;font:700 14px/24px system-ui;">W</span>
                                    </td>
                                    <td class="darkmode-text"
                                        style="font:500 13px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#111111;">
                                        ¿Necesitas ayuda? Escríbenos a nuestro <strong>WhatsApp
                                            {{ $supportPhone }}</strong>

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
                            <p
                                style="margin:0;font:500 11px/16px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#A3A7AD;">
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