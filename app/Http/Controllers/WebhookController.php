<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        // Data midtrans -> webhook
        $data = $request->all();
        // mengambil signature dari body
        $signatureKey = $data['signature_key'];

        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($signatureKey !== $mySignatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature'
            ], 400);
        }
        // cek order ID yang dikirimkan dari midtrans ada atau tidak di db kita, mengambil return pertama [0]
        $realOrderId = explode('-', $orderId);
        $order = Order::find($realOrderId[0]);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order id not found'
            ], 404);
        }

        if ($order->status === 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'Operation no permitted'
            ], 405);
        }
        // cek dan ubah status
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->status = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement') {
            $order->status = 'settlement';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            $order->status = 'failure';
        } else if ($transactionStatus == 'pending') {
            $order->status = 'pending';
        }

        // Simpan di LOG PAGYMENT
        $logData = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $realOrderId[0],
            'payment_type' => $type
        ];

        PaymentLog::create($logData);
        // save perubahan status
        $order->save();

        // jika status success maka berikan akses kelas ke user tersebut
        if ($order->status === 'success') {
            // memberikan akses premium -> service course
            createPremiumAccess([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id
            ]);
        }

        return response()->json('Ok');
    }
}
