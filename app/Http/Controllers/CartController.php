<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Session;

class CartController extends Controller
{
    protected $redisCartKey;

    public function __construct()
    {

        $this->redisCartKey = 'cart:' . (Auth::check() ? 'user:' . Auth::id() : 'guest:' . Session::getId());
    }

    public function index()
    {
        $redisCartKey = $this->redisCartKey;

        if (Auth::check()) {
            // Retrieve cart items from database for authenticated users
            $cartItems = Cart::where('user_id', Auth::id())->get()->toArray();
        } else {
            // Retrieve cart items from Redis for guests
            $cartItems = Redis::get($redisCartKey);
            $cartItems = $cartItems ? json_decode($cartItems, true) : [];

            // Add Redis keys to cart items
            $cartItems = array_map(function ($key, $item) {
                $item['id'] = $key; // Add Redis key as 'id'
                return $item;
            }, array_keys($cartItems), $cartItems);
        }

        // Calculate total amounts
        $subtotal = collect($cartItems)->sum(fn($item) => $item['price'] * $item['quantity']);
        $gst = $subtotal * 0.05; // 5% GST
        $qst = $subtotal * 0.09975; // 9.975% QST
        $grandTotal = $subtotal + $gst + $qst;

        return view('cart', compact('cartItems', 'subtotal', 'gst', 'qst', 'grandTotal'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::check()) {
            // Update cart item in the database for authenticated users
            $cartItem = Cart::findOrFail($id);
            $cartItem->update(['quantity' => (int) $request->quantity]);
        } else {
            // Update cart item in Redis for guests
            $cartItems = $this->getCartItems();

            // Debug: Log the cart items before update
            Log::info('Cart items before update:', ['cartItems' => $cartItems]);

            if (isset($cartItems[$id])) {
                // Debug: Log the item being updated
                Log::info('Updating item:', ['id' => $id, 'newQuantity' => (int) $request->quantity]);

                // Update the quantity
                $cartItems[$id]['quantity'] = (int) $request->quantity;

                // Debug: Log the cart items after update
                Log::info('Cart items after update:', ['cartItems' => $cartItems]);

                // Save the updated cart items to Redis
                $this->saveCartItems($cartItems);
            } else {
                // Debug: Log if the item is not found
                Log::error('Item not found in cart:', ['id' => $id]);
            }
        }

        return redirect()->route('cart.index');
    }

    public function remove($productId)
    {
        if (Auth::check()) {
            // Remove cart item from the database for authenticated users
            Cart::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->delete();

            // Debug: Log the removed item
            Log::info('Removed item from database:', ['productId' => $productId]);
        } else {
            // Remove cart item from Redis for guests
            $cartItems = $this->getCartItems();
            $key = Session::getId() . '_' . $productId; // Correct key format

            // Debug: Log the Redis key
            Log::info('Redis key:', ['key' => $key]);

            if (isset($cartItems[$key])) {
                unset($cartItems[$key]);

                // Debug: Log the updated cart items
                Log::info('Updated cart items:', ['cartItems' => $cartItems]);

                // Save the updated cart items to Redis
                $this->saveCartItems($cartItems);
            } else {
                // Debug: Log if the item is not found
                Log::error('Item not found in cart:', ['key' => $key]);
            }
        }

        return redirect()->route('cart.index');
    }

    public function addTestItems()
    {
        $cartItems = $this->getCartItems();
        $sessionId = Session::getId();

        $testItems = [
            $sessionId . '_1' => ['product_id' => 1, 'price' => 999.99, 'quantity' => 1],
            $sessionId . '_2' => ['product_id' => 2, 'price' => 699.99, 'quantity' => 2],
            $sessionId . '_3' => ['product_id' => 3, 'price' => 25.99, 'quantity' => 1],
        ];

        $cartItems = array_merge($cartItems, $testItems);

        // Debug: Log the data being saved
        Log::info('Saving to Redis:', [
            'key' => $this->redisCartKey,
            'data' => $cartItems
        ]);

        $this->saveCartItems($cartItems);

        return redirect()->route('cart.index');
    }

    public function simulateLogin()
    {
        // Store the old session ID before login
        $oldSessionId = Session::getId();
        Session::put('old_session_id', $oldSessionId);

        // Simulate user login
        Auth::loginUsingId(1);

        // Merge guest cart into the database
        $this->persistCartToDatabase();

        return redirect()->route('cart.index')->with('message', 'User logged in, cart synced to database.');
    }

    public function simulateLogout()
    {
        Auth::logout();
        Session::invalidate();
        return redirect()->route('cart.index')->with('message', 'User logged out.');
    }

    private function getCartItems()
    {

        $redisCartKey = $this->redisCartKey;
        $cartItems = Redis::get($redisCartKey);
        return $cartItems ? json_decode($cartItems, true) : [];
    }

    private function saveCartItems(array $cartItems)
    {
        $redisCartKey = $this->redisCartKey;

        // Debug: Log the key and data being saved
        Log::info('Saving to Redis:', [
            'key' => $redisCartKey,
            'data' => $cartItems
        ]);

        Redis::set($redisCartKey, json_encode($cartItems));
    }

    public function persistCartToDatabase()
    {
        if (Auth::check()) {
            $userId = Auth::id();

            // Retrieve the old session ID from the session
            $oldSessionId = Session::get('old_session_id');
            $guestCartKey = 'cart:guest:' . $oldSessionId;

            // Debug: Log the guest cart key
            Log::info('Guest cart key:', ['key' => $guestCartKey]);

            // Retrieve guest cart items from Redis
            $cartItems = Redis::get($guestCartKey);
            $cartItems = $cartItems ? json_decode($cartItems, true) : [];

            // Debug: Log the guest cart items
            Log::info('Guest cart items:', ['cartItems' => $cartItems]);

            // Retrieve existing cart items from the database
            $existingCartItems = Cart::where('user_id', $userId)->get();

            // Debug: Log the existing database cart items
            Log::info('Existing database cart items:', ['existingCartItems' => $existingCartItems]);

            foreach ($cartItems as $key => $item) {
                // Extract product ID from Redis key
                $productId = str_replace($oldSessionId . '_', '', $key);

                // Debug: Log the product being processed
                Log::info('Processing product:', ['productId' => $productId, 'item' => $item]);

                // Check if the product already exists in the database cart
                $existingCartItem = $existingCartItems->firstWhere('product_id', $productId);

                if ($existingCartItem) {
                    // Update the quantity if the product exists
                    $existingCartItem->update([
                        'quantity' => $existingCartItem->quantity + $item['quantity'],
                    ]);

                    // Debug: Log the updated cart item
                    Log::info('Updated cart item:', ['productId' => $productId, 'newQuantity' => $existingCartItem->quantity]);
                } else {
                    // Create a new cart item if the product does not exist
                    Cart::create([
                        'user_id' => $userId,
                        'product_id' => $productId, // Ensure product_id is set
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                    ]);

                    // Debug: Log the created cart item
                    Log::info('Created cart item:', ['productId' => $productId, 'quantity' => $item['quantity']]);
                }
            }

            // Delete the guest cart from Redis after merging
            Redis::del($guestCartKey);

            // Debug: Log the deletion of the guest cart
            Log::info('Deleted guest cart from Redis:', ['key' => $guestCartKey]);

            // Remove the old session ID from the session
            Session::forget('old_session_id');
        }
    }
}
