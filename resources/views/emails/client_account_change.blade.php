<!DOCTYPE html>
<html>

<head>
    <title>Solicitud para el cambio de cuenta</title>
</head>

<body>
    <p>Cliente: {{ $name }}</p>
    <p>id: {{ $id }}</p>
    <p>email: {{ $email }}</p>
    <p>tipo de cuenta a cambiar: {{ $type == "reseller" ? 'Revendedor / Negocio' : 'Consumidor final' }}</p>
    <p>Motivo: {{ $reason }}</p>
</body>

</html>