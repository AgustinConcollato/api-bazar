<!DOCTYPE html>
<html>

<head>
    <title>Restablecer constraseña</title>
</head>

<body>
    <h1>Restablecer contraseña</h1>
    <p>Hacé clic en el siguiente enlace para crear una nueva contraseña:</p>
    <!-- <a href="{{ url('http://localhost:5173/reset-password/' . $token . '/' . $email) }}">Restablecer contraseña</a> -->
    <a href="{{ url('https://api.bazarrshop/api/reset-password/' . $token . '/' . $email) }}">Restablecer contraseña</a>
    <p>Si no solicitaste este cambio, podés ignorar este mensaje. Tu contraseña actual seguirá siendo válida.</p>
</body>

</html>