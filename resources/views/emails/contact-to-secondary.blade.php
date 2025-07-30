<!DOCTYPE html>
<html>
<head>
    <title>Nueva Solicitud de Contacto</title>
</head>
<body>
    <h2>Nueva solicitud de contacto</h2>
    <p><strong>Nombre:</strong> {{ $data['full_name'] }}</p>
    <p><strong>Email:</strong> {{ $data['email'] }}</p>
    <p><strong>Teléfono:</strong> {{ $data['phone'] }}</p>
    <p><strong>Producto:</strong> {{ $data['interested_product'] }}</p>
</body>
</html>