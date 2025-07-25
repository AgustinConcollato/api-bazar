<!DOCTYPE html>
<html>

<head>
    <title>Pedido de {{$client['name']}} - {{$code}} </title>
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
        /* border-bottom: 1px solid #000; */
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

    .name {
        width: 10cm;
    }

    .price {
        text-align: right;
    }

    .detail {
        border-top: 1px solid #000;
        margin-top: 20px;
        display: flex;
        text-align: right;
        padding-top: 10px;
    }

    .total {
        padding-top: 10px;
        display: flex;
        text-align: right;
    }
</style>

<body>
    @php
    function formatPrice($precio) {
        if (fmod($precio, 1) == 0.0) {
            return number_format($precio, 0, '', '.');
        } else {
            $formateado = number_format($precio, 2, ',', '.');
            $formateado = rtrim($formateado, '0');
            $formateado = rtrim($formateado, ',');
            return $formateado;
        }
    }
    @endphp
    <header>
        <div>
            <p>Pedido para: {{ $client['name'] }}</p>
            <p>Fecha: {{ $date }}</p>
        </div>
    </header>
    <table cellspacing="0">
        <thead>
            <tr>
                <th>CANTIDAD</th>
                <th class="name">PRODUCTO</th>
                <th class="price">P/UNIDAD</th>
                <th class="price">DESC(%)</th>
                <th class="price">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
            <tr>
                <td>{{ $product['quantity'] }}</td>
                <td class="name">{{ $product['name'] }}</td>
                <td class="price">{{ formatPrice($product['price']) }}</td>
                <td class="price">{{ $product['discount'] ? $product['discount'] : 0 }}</td>
                <td class="price">{{ formatPrice($product['subtotal']) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="detail">
        <p>{{$discount ? 'Subtotal:  $' . formatPrice($total) : ''}} </p>
        <p>{{$discount ? 'Descuento:  ' . $discount . '%' : ''}} </p>
        <p class="total">PRECIO TOTAL: $ {{$discount ? formatPrice($total - ($discount * $total) / 100) : formatPrice($total)}}</p>
    </div>
</body>

</html>