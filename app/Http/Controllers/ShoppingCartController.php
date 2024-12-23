<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use App\Models\Product;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;

class ShoppingCartController
{

    public function add(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|string',
            'product_id' => 'required|string',
            'quantity' => 'required|integer'
        ]);

        $shoppingCart = ShoppingCart::where('user_id', $data['user_id'])
            ->where('product_id', $data['product_id'])
            ->first();

        if ($shoppingCart) {
            $shoppingCart->quantity += $data['quantity'];
            $shoppingCart->save();

            return response()->json($shoppingCart, 200);
        }

        $shoppingCart = ShoppingCart::create($data);

        return response()->json($shoppingCart, 201);
    }

    public function get($id)
    {
        $shoppingCart = ShoppingCart::where('user_id', $id)
            ->with('product')
            ->get();

        return response()->json($shoppingCart);
    }

    public function update(Request $request)
    {

        $id = $request->input('product_id');
        $quantity = $request->input('quantity');
        $user = $request->input('user_id');

        $product = ShoppingCart::where('product_id', $id)
            ->where('user_id', $user)
            ->first();

        $product->update(['quantity' => $quantity]);

        return response()->json($product);
    }


    public function delete($user, $id)
    {
        $product = ShoppingCart::where('user_id', $user)
            ->where('product_id', $id)
            ->first();

        $product->delete();

        return response()->json($product);
    }

    public function confirm(Request $request)
    {
        $id = $request->input('id');
        $userId = $request->input('user_id');
        $userName = $request->input('user_name');
        $date = $request->input('date');
        $comment = $request->input('comment');
        $address = $request->input('address');

        $cartItems = ShoppingCart::where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío'], 400);
        }

        $order = Order::create([
            'client' => $userId,
            'client_name' => $userName,
            'status' => 'pending',
            'total_amount' => 0,
            'date' => $date,
            'comment' => $comment,
            'id' => $id,
            'address' => json_encode($address)
        ]);

        foreach ($cartItems as $item) {
            $product = Product::find($item->product_id);

            if (!$product) {
                return response()->json(['message' => "El producto con ID {$item->product_id} no existe"], 404);
            }


            if ($product->discount) {
                $discount = $product->discount;
                $subtotal = $item->quantity * ($product->price - ($product->price * $discount) / 100);
            } else {
                $discount = 0;
                $subtotal = $item->quantity * $product->price;
            }


            OrderProducts::create([
                'order_id' => $id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $product->price,
                'discount' => $discount,
                'name' => $product->name,
                'picture' => $product->images,
                'subtotal' => $subtotal,
            ]);

            $order->update(['total_amount' => OrderProducts::where('order_id', $id)->sum('subtotal')]);
        }

        ShoppingCart::where('user_id', $userId)->delete();

        return response()->json(['message' => 'Pedido confirmado', 'order_id' => $order->id]);
    }

}