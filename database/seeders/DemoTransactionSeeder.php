<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure realistic food items exist
        $foods = Food::all();
        if ($foods->isEmpty()) {
            $foods = collect([
                Food::create([
                    'name'         => 'Grilled Tasmanian Salmon',
                    'price'        => 3850.00,
                    'type'         => 'Dinner',
                    'description'  => 'Pan-seared wild salmon served with asparagus and lemon-butter emulsion.',
                    'is_available' => true,
                ]),
                Food::create([
                    'name'         => 'Angus Beef Tenderloin',
                    'price'        => 4600.00,
                    'type'         => 'Dinner',
                    'description'  => 'Grass-fed prime beef medallion with truffled potato puree.',
                    'is_available' => true,
                ]),
                Food::create([
                    'name'         => 'Artisan Burrata Salad',
                    'price'        => 2250.00,
                    'type'         => 'Lunch',
                    'description'  => 'Fresh Italian burrata with heirloom tomatoes and aged balsamic glaze.',
                    'is_available' => true,
                ]),
                Food::create([
                    'name'         => 'Cold-Pressed Valencia Orange Juice',
                    'price'        => 800.00,
                    'type'         => 'Beverage',
                    'description'  => 'Freshly squeezed 100% natural orange nectar.',
                    'is_available' => true,
                ]),
            ]);
        }

        // 2. Create 3 dedicated clients
        $customersData = [
            [
                'name'     => 'Dr. Aritha Wickramasinghe',
                'email'    => 'aritha.wick@gmail.com',
                'phone'    => '+94 77 123 4567',
                'address'  => '45/2 Ward Place, Cinnamon Gardens, Colombo 07',
                'role'     => 'Customer',
            ],
            [
                'name'     => 'Dinithi Jayawardena',
                'email'    => 'dinithi.j@outlook.com',
                'phone'    => '+94 71 892 3410',
                'address'  => '12 Queens Road, Colombo 03',
                'role'     => 'Customer',
            ],
            [
                'name'     => 'Kavinda Senanayake',
                'email'    => 'kavinda.s@corp.lk',
                'phone'    => '+94 76 543 2198',
                'address'  => '88 Richmond Hill Road, Galle',
                'role'     => 'Customer',
            ],
        ];

        $users = [];
        foreach ($customersData as $c) {
            $users[] = User::firstOrCreate(
                ['email' => $c['email']],
                [
                    'name'     => $c['name'],
                    'phone'    => $c['phone'],
                    'address'  => $c['address'],
                    'role'     => $c['role'],
                    'password' => Hash::make('password123'),
                ]
            );
        }

        // 3. Define the 3 realistic demo transactions
        $transactionsData = [
            // Transaction 1: Bank Transfer - Pending Verification (Action Required)
            [
                'user'              => $users[0],
                'payment_method'    => 'bank_transfer',
                'payment_status'    => 'pending',
                'payment_reference' => 'BOC-TXN-902148',
                'bank_receipt_url'  => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1000&q=80',
                'status'            => 'pending',
                'is_subscription'   => true,
                'created_at'        => Carbon::now()->subHours(2),
                'items'             => [
                    ['food' => $foods->first(), 'quantity' => 2, 'price' => 3850.00],
                    ['food' => $foods->last(),  'quantity' => 1, 'price' => 750.00],
                ],
            ],

            // Transaction 2: Bank Transfer - Verified & Approved (High-Ticket Ledger Inflow)
            [
                'user'              => $users[1],
                'payment_method'    => 'bank_transfer',
                'payment_status'    => 'verified',
                'payment_reference' => 'HNB-TRF-640192',
                'bank_receipt_url'  => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=1000&q=80',
                'status'            => 'processing',
                'is_subscription'   => false,
                'created_at'        => Carbon::now()->subHours(5),
                'items'             => [
                    ['food' => $foods->skip(1)->first() ?? $foods->first(), 'quantity' => 2, 'price' => 4600.00],
                    ['food' => $foods->skip(2)->first() ?? $foods->first(), 'quantity' => 1, 'price' => 2250.00],
                    ['food' => $foods->last(),                               'quantity' => 2, 'price' => 800.00],
                ],
            ],

            // Transaction 3: Online Transfer - Verified / Settled (Direct Payment Gateway)
            [
                'user'              => $users[2],
                'payment_method'    => 'online_transfer',
                'payment_status'    => 'verified',
                'payment_reference' => 'IPG-OL-773910',
                'bank_receipt_url'  => null,
                'status'            => 'completed',
                'is_subscription'   => false,
                'created_at'        => Carbon::now()->subDays(1),
                'items'             => [
                    ['food' => $foods->skip(2)->first() ?? $foods->first(), 'quantity' => 2, 'price' => 2250.00],
                    ['food' => $foods->last(),                               'quantity' => 1, 'price' => 800.00],
                ],
            ],
        ];

        // 4. Insert records with calculated totals and relations
        foreach ($transactionsData as $tData) {
            $totalAmount = 0;
            foreach ($tData['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Insert into orders table
            $orderId = DB::table('orders')->insertGetId([
                'user_id'           => $tData['user']->id,
                'total_price'       => $totalAmount,
                'status'            => $tData['status'],
                'address'           => $tData['user']->address,
                'dinner_address'    => null,
                'payment_method'    => $tData['payment_method'],
                'payment_status'    => $tData['payment_status'],
                'payment_reference' => $tData['payment_reference'],
                'bank_receipt_url'  => $tData['bank_receipt_url'],
                'is_subscription'   => $tData['is_subscription'],
                'created_at'        => $tData['created_at'],
                'updated_at'        => $tData['created_at'],
            ]);

            // Insert order items
            foreach ($tData['items'] as $item) {
                DB::table('order_items')->insert([
                    'order_id'       => $orderId,
                    'food_id'        => $item['food']->id,
                    'quantity'       => $item['quantity'],
                    'price'          => $item['price'],
                    'scheduled_date' => $tData['created_at']->copy()->addHours(2),
                    'created_at'     => $tData['created_at'],
                    'updated_at'     => $tData['created_at'],
                ]);
            }
        }
    }
}
