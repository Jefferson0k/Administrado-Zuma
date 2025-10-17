<!DOCTYPE html>
<html>
<head>
    <title>Nuevo Contacto</title>
</head>
<body>
    <img src="{{url('imagenes/logo-zuma.svg')}}" alt="alt"/>
    <h2>Nuevo mensaje desde el formulario de contacto</h2>
    <p><strong>Nombre:</strong> {{ $data['full_name'] ?? $data['email'] }}</p>
    <p><strong>Producto de interés:</strong> {{ $data['interested_product'] ?? 'No especificado' }}</p>
    <p><strong>Teléfono:</strong> {{ $data['phone'] }}</p>
</body>
</html>