<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransactionSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Transaction::factory()->count(100)->create();
    }
}
