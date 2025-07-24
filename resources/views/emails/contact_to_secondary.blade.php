<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo contacto para Marketing</title>
</head>
<body>
  <h2>👥 Nuevo lead recibido</h2>
  <p><strong>Nombre:</strong> {{ $data['name'] }}</p>
  <p><strong>Email:</strong> {{ $data['email'] }}</p>
  <p><strong>Teléfono:</strong> {{ $data['phone'] }}</p>
  <p><strong>Mensaje:</strong> {{ $data['message'] }}</p>

  <p>Este contacto fue registrado desde el formulario de la web.</p>
</body>
</html>
