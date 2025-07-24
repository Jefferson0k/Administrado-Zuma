<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nueva solicitud de contacto</title>
</head>
<body>
  <h2>📩 Nueva solicitud de contacto recibida</h2>
  <p><strong>Nombre:</strong> {{ $data['name'] }}</p>
  <p><strong>Email:</strong> {{ $data['email'] }}</p>
  <p><strong>Teléfono:</strong> {{ $data['phone'] }}</p>
  <p><strong>Mensaje:</strong> {{ $data['message'] }}</p>
  <hr>
  <p>Fecha de envío: {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
