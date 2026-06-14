<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Clear existing file first
        $path = Product::getStoragePath();
        if (file_exists($path)) {
            unlink($path);
        }

        $categories = [
            'Propulsion' => ['Antigravity Boots', 'Ion Thruster', 'Warp Drive Core', 'Gravity Impeller', 'Quantum Levitation Pad'],
            'Sensors' => ['Quantum Gravity Sensor', 'Tachyon Scanner', 'Event Horizon Detector', 'Dark Matter Radar', 'Chronometric Sensor'],
            'Energy' => ['Zero-Point Energy Cell', 'Dark Matter Reactor', 'Singularity Core', 'Fusion Harmonizer', 'Plasma Capacitor'],
            'Armor & Shielding' => ['Deflector Shield Generator', 'Magnetic Mesh Plating', 'Neutronium Shield', 'Gravitational Anchor', 'Phase Shifter Field'],
            'Weapons' => ['Disruptor Pistol', 'Gravity Vortex Grenade', 'Singularity Cannon', 'Plasma Blade', 'Tachyon Lance'],
            'Gadgets' => ['Chronos Wristwatch', 'Sub-space Communicator', 'Neural Uplink Interface', 'Holographic Deck', 'Nanite Repair Kit']
        ];

        $statuses = ['active', 'inactive', 'draft'];
        $count = 0;

        foreach ($categories as $category => $items) {
            foreach ($items as $item) {
                // Generate 2 variations of each item to get 60 items total
                for ($v = 1; $v <= 2; $v++) {
                    $name = $v === 1 ? $item : $item . " Mk. II";
                    $sku = "GRV-" . strtoupper(substr($category, 0, 3)) . "-" . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                    $price = rand(150, 9500) + (rand(0, 99) / 100);
                    $stock = rand(0, 150);
                    $status = $statuses[array_rand($statuses)];
                    $releasedAt = now()->subDays(rand(1, 365))->toDateString();
                    
                    $desc = "Premium high-grade $name. Engineered specifically for deep space deployment and gravity stabilization in the $category sector. Tested and approved by the Antigravity Research Lab.";

                    Product::create([
                        'name' => $name,
                        'sku' => $sku,
                        'description' => $desc,
                        'price' => $price,
                        'stock' => $stock,
                        'status' => $status,
                        'released_at' => $releasedAt
                    ]);
                    $count++;
                }
            }
        }

        // Add a few custom special items
        $specials = [
            ['name' => 'Antigravity Propulsion Matrix', 'sku' => 'GRV-PROP-001', 'price' => 12500.00, 'stock' => 5, 'status' => 'active'],
            ['name' => 'Zero Gravity Lounge Chair', 'sku' => 'GRV-GAD-999', 'price' => 499.99, 'stock' => 45, 'status' => 'active'],
            ['name' => 'Wormhole Stabilizer Ring', 'sku' => 'GRV-ENG-888', 'price' => 89000.00, 'stock' => 0, 'status' => 'inactive'],
            ['name' => 'Graviton Beam Welder', 'sku' => 'GRV-WEAP-777', 'price' => 1450.00, 'stock' => 12, 'status' => 'draft'],
        ];

        foreach ($specials as $special) {
            Product::create(array_merge($special, [
                'description' => "Specialized elite item: {$special['name']}. Developed under strict Antigravity guidelines.",
                'released_at' => now()->toDateString()
            ]));
            $count++;
        }

        $this->command->info("Successfully seeded $count products into JSON database!");
    }
}
