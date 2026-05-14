<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class RefundReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($branch): void
    {
        $refundReasons = [
            'The item was prepared but returned by the customer.',
            'The item was delivered but rejected.',
            'A mistake in the order.',
            'Product quality issue.',
        ];

        $data = [];

        foreach ($refundReasons as $reason) {
            $data[] = [
                'reason' => $reason,
                'branch_id' => $branch->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('refund_reasons')->insert($data);
    }
}

