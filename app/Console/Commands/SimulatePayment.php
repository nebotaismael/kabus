<?php

namespace App\Console\Commands;

use App\Models\Orders;
use Illuminate\Console\Command;

class SimulatePayment extends Command
{
    protected $signature = 'payment:simulate {order_id}';
    protected $description = 'Simulate a successful payment for testing (sandbox only)';

    public function handle(): int
    {
        if (config('nowpayments.api_url') !== 'https://api-sandbox.nowpayments.io/v1/') {
            $this->error('This command only works in sandbox mode!');
            return 1;
        }

        $orderId = $this->argument('order_id');
        $order = Orders::find($orderId);

        if (!$order) {
            $this->error("Order not found: {$orderId}");
            return 1;
        }

        if ($order->status !== Orders::STATUS_WAITING_PAYMENT) {
            $this->warn("Order is not waiting for payment. Current status: {$order->status}");
            return 1;
        }

        $order->status = Orders::STATUS_PAYMENT_RECEIVED;
        $order->is_paid = true;
        $order->paid_at = now();
        $order->payment_completed_at = now();
        $order->total_received_xmr = $order->pay_amount ?? $order->required_xmr_amount;
        $order->save();

        $this->info("Payment simulated successfully for order: {$orderId}");
        $this->info("New status: " . Orders::STATUS_PAYMENT_RECEIVED);

        return 0;
    }
}
