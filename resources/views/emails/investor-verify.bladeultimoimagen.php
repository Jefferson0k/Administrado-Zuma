<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu correo - ZUMA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            background-color: #fff;
            padding: 30px;
            border: 1px solid #dee2e6;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>

<body>


    <div class="content">

        <div class="content" style="padding:0; border:0;">
            <div style="position:relative; width:600px; height:900px; margin:0 auto;">
                <a href="{{ $url }}" style="display:block; line-height:0;">
                    <img src="{{ asset('imagenes/hasVerifiedEmail.png') }}" width="550" height="730" alt="ZUMA"
                        style="display:block; width:550px; height:730px; border:0; margin:0 auto;">
                </a>

                <!-- Texto sobre la imagen, 100px desde arriba -->
                <div style="position:absolute; top:180px; left:0; width:100%; text-align:center;">
                    <h2 style="margin:0; font-family:Arial, sans-serif; font-size:45px; color:#6192f3;">
                        {{ $investor->name }}
                    </h2>
                </div>
            </div>
        </div>






    </div>

    <div class="footer">
        <p>Saludos,<br>Equipo ZUMA</p>
    </div>
</body>

</html>