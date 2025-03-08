<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet">
    <title>Shopping Cart</title>
    <style>
    </style>
</head>
<body>
<div class="container">
    <h2>Shopping Cart</h2>

    @if (session('message'))
        <div class="message">
            {{ session('message') }}
        </div>
    @endif

    <table>
        <thead>
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($cartItems as $item)
            <tr>
                <td>{{ $item['product_id'] }}</td>
                <td>${{ number_format($item['price'], 2) }}</td>
                <td>
                    <form method="POST" action="{{ route('cart.update', $item['id']) }}" style="display: inline;">
                        @csrf
                        <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" style="width: 60px; padding: 5px;">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                    <a href="{{ route('cart.remove', $item['product_id']) }}" class="btn btn-danger">Remove</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <h3>Order Summary</h3>
        <p>Subtotal: ${{ number_format($subtotal, 2) }}</p>
        <p>GST (5%): ${{ number_format($gst, 2) }}</p>
        <p>QST (9.975%): ${{ number_format($qst, 2) }}</p>
        <p><strong>Grand Total: ${{ number_format($grandTotal, 2) }}</strong></p>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="{{ route('cart.addTestItems') }}" class="btn btn-success">Add Test Items</a>
        @if(!Auth::check())
            <a href="{{ route('cart.simulateLogin') }}" class="btn btn-warning">Simulate Login & Sync Cart</a>
        @endif
        @if(Auth::check())
            <a href="{{ route('cart.simulateLogout') }}" class="btn btn-warning">Simulate Logout</a>
        @endif
    </div>
</div>
</body>
</html>
