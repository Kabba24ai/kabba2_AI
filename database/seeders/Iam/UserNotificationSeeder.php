<?php

namespace Database\Seeders\Iam;

use Illuminate\Database\Seeder;
use App\Models\Iam\Personnel\UserNotification;
use App\Models\Orders\Order;
use App\Models\Iam\Personnel\User;
use Illuminate\Support\Arr;

class UserNotificationSeeder extends Seeder
{
    public function run(): void
    {
        // Get 5 random orders
        $orders = Order::inRandomOrder()->limit(5)->get();

        // Get all users
        $users = User::all();

        if ($orders->isEmpty() || $users->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 10; $i++) {

            $order = $orders->random();
            $user  = $users->random();

            UserNotification::create([
                'user_id'  => $user->id,
                'order_id' => $order->id,
                'type'     => null,
                'body'     => null,
                'params'   => null,
            ]);
        }
    }
}
