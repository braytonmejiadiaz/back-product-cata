<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'PT Sans', Arial, sans-serif;
            background-color: #f4ecfa;
            color: #282828;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            background-color: #009975;
            text-align: center;
            padding: 20px;
        }
        .header img {
            max-width: 150px;
        }
        .main-content {
            padding: 30px 50px;
            text-align: center;
        }
        .main-content h1 {
            font-size: 28px;
            color: #009975;
        }
        .main-content p {
            font-size: 16px;
            line-height: 1.5;
            color: #6e6e6e;
        }
        .button-container {
            margin-top: 20px;
        }
        .button-container a {
            display: inline-block;
            padding: 15px 35px;
            background-color: #009975;
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: 25px;
        }
        .footer {
            background-color: #949196;
            color: #ffffff;
            text-align: center;
            padding: 20px 50px;
        }
        .footer p {
            font-size: 14px;
            line-height: 1.5;
        }
        .footer a {
            color: #ffffff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <img src="https://tucartaya.com/wp-content/uploads/2025/03/treggio-logo.png" alt="Logo">
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h1>Verifica tu cuenta de correo</h1>
            <p>Por favor, verifica tu dirección de correo electrónico para completar la configuración de tu cuenta. Esto nos ayuda a mantener tu cuenta segura.</p>

            <!-- Button -->
            <div class="button-container">
                <a href="{{ env('URL_TIENDA') . 'ingresar?code=' . $user->uniqd }}" target="_blank">Verificar Ahora</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Dirección ficticia, Ciudad, Estado, País<br>Teléfono 1 - Teléfono 2<br><a href="mailto:info@website.com">info@website.com</a> - <a href="https://www.website.com">www.website.com</a></p>

            <!-- Social Media Links -->
            <p>Síguenos en nuestras redes sociales:</p>
                <a href="#"><img src="../images/ico_facebook.png" alt="Facebook" width="30"></a>
                <a href="#"><img src="../images/ico_instagram.png" alt="Instagram" width="30"></a>
                <a href="#"><img src="../images/ico_twitter.png" alt="Twitter" width="30"></a>
                <a href="#"><img src="../images/ico_pinterest.png" alt="Pinterest" width="30"></a>

        </div>
    </div>
</body>
</html>
