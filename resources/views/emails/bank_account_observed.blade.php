@component('mail::message')
<p style="text-align:center;margin:0 0 16px;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block;">
        <img src="{{ $logoUrl }}" alt="{{ $logoUrl }}" height="40" style="height:40px;display:block;margin:0 auto;">
    </a>
</p>

# Hola {{ $name }},

Tu cuenta bancaria con alias **"{{ $alias }}"** ha sido **observada**.

{{ $custom }}

@component('mail::button', ['url' => $dashboardUrl])
Ir al Dashboard
@endcomponent

Si tienes dudas, contacta a soporte.  
¡Gracias por usar ZUMA!
@endcomponent
