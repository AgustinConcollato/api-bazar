<!DOCTYPE html>
<html>

<head>
    <title>Pedido de {{$client['name']}} - {{$client['id']}} </title>
</head>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    body {
        margin: .5cm;
    }

    header {
        border-bottom: 1px solid #000;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }

    header p {
        margin-bottom: 5px;
    }

    header div p {
        width: 49%;
        display: inline-block;
    }

    header div p:last-child {
        text-align: right;
    }

    table {
        width: 100%;
    }

    th {
        border-bottom: 1px solid #000;
        text-align: left;
        text-decoration: none;
        padding: 5px 0;
    }

    td {
        padding: 3px 0;
    }

    tbody tr:first-child td {
        padding-top: 10px;
    }

    .price {
        text-align: right;
    }

    .total {
        margin-top: 20px;
        padding-top: 10px;
        display: flex;
        border-top: 1px solid #000;
        text-align: right;
    }

    .total span {
        padding: 10px 15px;
        display: inline-block;
        border: 1px solid #000;
        position: relative;
        top: 15px;
    }
</style>

<body>
    <header>
        <div>
            <p>Pedido para: {{ $client['name'] }}</p>
            <p>Fecha: {{ $date }}</p>
        </div>
        <p>Código del pedido: {{ $code }}</p>
    </header>
    <table cellspacing="0">
        <thead>
            <tr>
                <th>CANTIDAD</th>
                <th>PRODUCTO</th>
                <th class="price">P/UNIDAD</th>
                <th class="price">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product['quantity'] }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td class="price">$ {{ $product['price'] }}</td>
                    <td class="price">$ {{ $product['subtotal'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total"><span>PRECIO TOTAL: $ {{$total}} </span></p>
</body>

</html>