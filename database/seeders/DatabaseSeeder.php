<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Branch::firstOrCreate(['code' => 'WH'], ['name' => 'Surathala Main Warehouse', 'type' => 'warehouse']);

        User::firstOrCreate(['email' => 'admin@petshop.test'], [
            'name' => 'Main Admin', 'password' => 'password', 'role' => 'admin',
        ]);

        // Demo data (safe to delete): one shop with a login, sample catalogue.
        $shop = Branch::firstOrCreate(['code' => 'CMB'], ['name' => 'Surathala Ekala', 'type' => 'shop', 'address' => '357, Orex City complex, Ekala.', 'email' => 'info@surathala.com']);
        User::firstOrCreate(['email' => 'colombo@petshop.test'], [
            'name' => 'Colombo Cashier', 'password' => 'password', 'role' => 'branch', 'branch_id' => $shop->id,
        ]);

        Supplier::firstOrCreate(['name' => 'PetCare Distributors']);
        $cats = collect(['Dog Food', 'Cat Food', 'Accessories', 'Grooming', 'Fish & Aquarium'])
            ->mapWithKeys(fn ($n) => [$n => Category::firstOrCreate(['name' => $n])->id]);

        foreach ([
            ['DF-001', 'Dog Food Adult 3kg', 'Dog Food', 2500, 3200],
            ['DF-002', 'Puppy Starter 1kg', 'Dog Food', 1100, 1500],
            ['CF-001', 'Cat Food Tuna 1.5kg', 'Cat Food', 1800, 2400],
            ['AC-001', 'Pet Collar Medium', 'Accessories', 350, 650],
            ['GR-001', 'Pet Shampoo 250ml', 'Grooming', 600, 950],
            ['FS-001', 'Fish Food Flakes 100g', 'Fish & Aquarium', 250, 450],
        ] as [$sku, $name, $cat, $cost, $price]) {
            Product::firstOrCreate(['sku' => $sku], [
                'name' => $name, 'category_id' => $cats[$cat], 'cost_price' => $cost, 'sale_price' => $price,
            ]);
        }

        Customer::firstOrCreate(['phone' => '0771234567'], [
            'branch_id' => $shop->id, 'name' => 'Walk-in Sample', 'pet_name' => 'Rex', 'pet_type' => 'Dog',
        ]);
    }
}
