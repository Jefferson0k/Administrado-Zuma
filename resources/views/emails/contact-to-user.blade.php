<!DOCTYPE html>
<html>
<head>
    <title>Confirmación de Contacto</title>
</head>
<body>
    <img src="{{url('imagenes/logo-zuma.svg')}}" alt="alt"/>
    <h2>¡Gracias por contactarnos!</h2>
    <p>Hola {{ $data['full_name'] ?? $data['email'] }},</p>
    <p>Hemos recibido tu solicitud sobre {{ $data['interested_product'] }}.</p>
    <p>Te contactaremos pronto.</p>
</body>
</html>