<?php

namespace Database\Seeders;

use App\Models\CafeteriaItem;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Models\User;
use App\Services\CafeteriaOrderService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CafeteriaStudentDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Cafeteria demo data may only be seeded outside production.');
        }

        $student = Student::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'student@school.test'))
            ->first();
        if (! $student?->branch_id) {
            return;
        }

        $menu = [
            ['name' => 'Fresh fruit cup', 'unit' => 'serving', 'stock' => 100, 'price' => '250.00'],
            ['name' => 'Wholegrain sandwich', 'unit' => 'serving', 'stock' => 80, 'price' => '450.00'],
            ['name' => 'Bottled water', 'unit' => 'bottle', 'stock' => 120, 'price' => '100.00'],
        ];
        $items = collect();

        foreach ($menu as $index => $row) {
            $sku = sprintf('DEMO-CAF-B%d-%03d', $student->branch_id, $index + 1);
            $inventory = InventoryItem::firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $row['name'],
                    'category' => 'Cafeteria',
                    'unit' => $row['unit'],
                    'quantity_on_hand' => 0,
                    'reorder_level' => 10,
                    'is_active' => true,
                    'description' => 'Freshly stocked cafeteria item for students and staff at the assigned campus.',
                ]
            );
            if ($inventory->branch_id !== (int) $student->branch_id) {
                $inventory->forceFill(['branch_id' => $student->branch_id])->save();
            }
            if ($inventory->movements()->doesntExist() && $inventory->quantity_on_hand === 0) {
                InventoryService::recordMovement(
                    $inventory,
                    'receipt',
                    $row['stock'],
                    'DEMO-CAF-OPENING-B'.$student->branch_id,
                    'Cafeteria opening delivery for the student dining service',
                    User::query()->where('email', 'admin@school.test')->value('id')
                );
            }

            $item = CafeteriaItem::firstOrCreate(
                ['inventory_item_id' => $inventory->id],
                [
                    'name' => $row['name'],
                    'description' => 'Available for purchase by enrolled students during the school day.',
                    'price' => $row['price'],
                    'is_available' => true,
                ]
            );
            if ($item->branch_id !== (int) $student->branch_id) {
                $item->forceFill(['branch_id' => $student->branch_id])->save();
            }
            $items->push($item);
        }

        if (DB::table('cafeteria_orders')->where('student_id', $student->id)->exists()) {
            return;
        }

        app(CafeteriaOrderService::class)->placeOrder($student, today()->toDateString(), [
            ['cafeteria_item_id' => $items[0]->id, 'quantity' => 1],
            ['cafeteria_item_id' => $items[2]->id, 'quantity' => 1],
        ]);
    }
}
