<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $userId = $request->input('user_id');
        $orders = Order::query();

        $orders->when($userId, function ($query) use ($userId) {
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $orders->get()
        ]);
    }

    public function create(Request $request)
    {
        // mendapatkan body value user
        $user = $request->input('user');
        $course = $request->input('course');

        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id'],
        ]);

        // 1
        $transactionsDetails = [
            'order_id' => $order->id . '-' . Str::random(5),
            'gross_amount' => $course['price'],
        ];

        $itemDetails = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name'],
                'brand' => 'Aponsa',
                'category' => 'Online Course',
            ]
        ];

        $customerDetails = [
            'first_name' => $user['name'],
            'email' => $user['email'],
        ];
        // 2

        // 1 untuk mendapatkan snapUrl
        $midtransParams = [
            'transaction_details' => $transactionsDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
        ];

        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);
        // 1 

        $order->snap_url = $midtransSnapUrl;

        $order->metadata = [
            'couse_id' => $course['id'],
            'course_price' => $course['price'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_level' => $course['level'],
        ];

        $order->save();

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);

        // return response()->json($order);
        // return $midtransSnapUrl;
    }

    private function getMidtransSnapUrl($params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');

        // get snap url dari midtrans
        $snapUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;
        // udah dapat?
        return $snapUrl;
    }
}
