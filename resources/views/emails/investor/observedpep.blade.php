@php
    // Marca
    $appName      = $appName      ?? 'ZUMA';
    $brandPrimary = $brandPrimary ?? '#ff4929';
    $brandButton  = $brandButton  ?? '#22c55e';
    $logoUrl      = $logoUrl      ?? '';

    // Contenido
    $title        = $title        ?? 'Necesitamos validar tu evidencia PEP';
    $userName     = $userName     ?? 'Usuario';
    $reasons      = $reasons      ?? [];

    // Soporte & footer
    $companyAddr  = $companyAddr  ?? '';
    $prefsUrl     = $prefsUrl     ?? '#';
    $whatsappUrl  = $whatsappUrl  ?? '#';
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
            .container { width: 100% !important; padding: 0 16px !important; }
            .hero-title { font-size: 20px !important; line-height: 26px !important; }
        }
        a { text-decoration: none; }
        .btn { display:inline-block; padding:12px 18px; border-radius:28px; font-weight:700; color:#fff; }
        .muted { color:#374151; }
        .list { margin:0 0 14px 20px; padding:0; color:#111827; }
    </style>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
    <!-- Barra superior -->
    <tr>
        <td style="background: {{ $brandPrimary }}; padding: 18px 0; text-align:center;">
            <span style="font:700 30px/1 sans-serif;color:#000;letter-spacing:0.5px;">{{ $appName }}</span>
        </td>
    </tr>

    <!-- Card -->
    <tr>
        <td align="center">
            <table class="container" role="presentation" width="640" cellspacing="0" cellpadding="0" border="0"
                   style="width:640px;max-width:640px;background:#FFFFFF;margin:0 auto;">
                <tr>
                    <td style="padding:28px 32px 8px 32px; text-align:center;">
                        <h1 class="hero-title"
                            style="margin:8px 0 8px 0;font:800 22px/28px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; color:#111827; text-decoration: underline;">
                            {{ $title }}
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 32px 6px 32px; color:#111827; font:400 14px/22px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;">
                        <p style="margin:0 0 14px 0;">Hola <strong>{{ $userName }}</strong>,</p>

                        <p class="muted" style="margin:0 0 12px 0;">
                            Revisamos la <strong>evidencia PEP</strong> que registraste y encontramos observaciones
                            que debemos corregir para continuar con la validación.
                        </p>

                        @if(!empty($reasons))
                            <p style="margin:0 0 8px 0;">Motivos detectados:</p>
                            <ul class="list">
                                @foreach($reasons as $reason)
                                    <li style="margin:0 0 6px 0;">{{ $reason }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <p style="margin:12px 0 8px 0;color:#111827;font-weight:700;">
                            ¿Qué puedes presentar como evidencia válida?
                        </p>
                        <ul class="list">
                            <li>Constancia o carta oficial que acredite el cargo/relación PEP.</li>
                            <li>Resolución o documento público (gaceta, diario oficial, web institucional).</li>
                            <li>Declaración jurada firmada con sustento verificable.</li>
                        </ul>

                        <p style="margin:12px 0 8px 0;color:#111827;font-weight:700;">
                            Recomendaciones para la imagen/documento:
                        </p>
                        <ul class="list">
                            <li>Foto/escaneo nítido (sin recortes, buena iluminación).</li>
                            <li>Documento completo, legible, con nombres y fechas visibles.</li>
                            <li>Si es enlace web institucional, incluye la URL visible en el documento o adjunta PDF.</li>
                        </ul>

                        <p class="muted" style="margin:12px 0 12px 0;">
                            Por favor <strong>contáctate con nuestro equipo</strong> para ayudarte a reenviar la evidencia correcta.
                        </p>

                        <p style="text-align:center; margin:18px 0 22px 0;">
                            <a href="{{ $whatsappUrl }}" class="btn"
                               style="background: {{ $brandButton }}; box-shadow:0 2px 0 rgba(0,0,0,0.12);">
                                Contactar por WhatsApp
                            </a>
                        </p>

                        <p class="muted" style="margin:0 0 12px 0;">
                            Este proceso es obligatorio por normativa y protege la seguridad de tu cuenta.
                        </p>

                        <p style="margin:0;color:#111827;">Gracias por tu colaboración,<br>El equipo de {{ $appName }}</p>

                        <hr style="border:none;border-top:1px solid #D1D5DB;margin:22px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 6px 0;">
                            <tr>
                                <td width="28" valign="top" style="padding:0 8px 0 0;">
                                    <span style="display:inline-block;width:24px;height:24px;border-radius:999px;background:#111827;color:#FFFFFF;text-align:center;line-height:24px;font:700 14px/24px system-ui;">W</span>
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
                        <p style="margin:0 0 10px 0;font:500 12px/18px system-ui,-apple-system,Segoe UI,Roboto,Ubuntu;color:#E5E7EB;">
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
