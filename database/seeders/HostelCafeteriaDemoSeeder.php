<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CafeteriaItem;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Models\User;
use App\Services\HostelAssignmentService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

class HostelCafeteriaDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Hostel and cafeteria demo data may only be seeded outside production.');
        }

        $academicYear = AcademicYear::query()->where('is_current', true)->firstOrFail();
        $admin = User::query()->where('role', 'admin')->orderBy('id')->firstOrFail();
        $students = Student::query()
            ->where('status', 'active')
            ->whereHas('user', fn ($query) => $query->where(function ($emails) {
                $emails->where('email', 'like', 'demo.student.%@school.test')
                    ->orWhere('email', 'student@school.test');
            }))
            ->orderBy('id')
            ->limit(12)
            ->get();

        $this->seedHostelAssignments($academicYear, $students);
        $this->seedCafeteriaMenu($admin);

        $this->command?->info('Hostel and cafeteria demo data is ready.');
    }

    private function seedHostelAssignments(AcademicYear $academicYear, $students): void
    {
        $northHostel = Hostel::firstOrCreate(
            ['name' => 'Sapphire House Boarding Residence'],
            ['location' => 'Colombo campus north garden', 'is_active' => true]
        );
        $southHostel = Hostel::firstOrCreate(
            ['name' => 'Orchid House Boarding Residence'],
            ['location' => 'Colombo campus south garden', 'is_active' => true]
        );

        $rooms = collect([
            [$northHostel, 'N-101'],
            [$northHostel, 'N-102'],
            [$southHostel, 'S-201'],
        ])->map(fn (array $data) => $data[0]->rooms()->firstOrCreate(
            ['room_number' => $data[1]],
            ['bed_capacity' => 40, 'is_active' => true]
        ));

        foreach ($students->values() as $index => $student) {
            if ($student->hostelAssignments()->exists()) {
                continue;
            }

            $startDate = $academicYear->start_date->toDateString();
            $bed = $this->findAvailableBed($rooms, $startDate);
            if (! $bed) {
                throw new RuntimeException('No available bed for the sample hostel assignment.');
            }

            app(HostelAssignmentService::class)->assign(null, [
                'student_id' => $student->id,
                'hostel_room_id' => $bed['room']->id,
                'academic_year_id' => $academicYear->id,
                'bed_number' => $bed['bed_number'],
                'start_date' => $startDate,
                'end_date' => null,
                'status' => 'active',
            ]);
        }
    }

    /** @param Collection<int, HostelRoom> $rooms
     * @return array{room: HostelRoom, bed_number: int}|null
     */
    private function findAvailableBed($rooms, string $startDate): ?array
    {
        foreach ($rooms as $room) {
            for ($bedNumber = 1; $bedNumber <= $room->bed_capacity; $bedNumber++) {
                $occupied = $room->assignments()
                    ->where('status', 'active')
                    ->where('bed_number', $bedNumber)
                    ->whereDate('start_date', '<=', '9999-12-31')
                    ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $startDate))
                    ->exists();

                if (! $occupied) {
                    return ['room' => $room, 'bed_number' => $bedNumber];
                }
            }
        }

        return null;
    }

    private function seedCafeteriaMenu(User $admin): void
    {
        $menu = [
            ['sku' => 'DEMO-CAF-001', 'name' => 'Fresh fruit cup', 'unit' => 'serving', 'stock' => 100, 'price' => '250.00'],
            ['sku' => 'DEMO-CAF-002', 'name' => 'Wholegrain sandwich', 'unit' => 'serving', 'stock' => 80, 'price' => '450.00'],
            ['sku' => 'DEMO-CAF-003', 'name' => 'Bottled water', 'unit' => 'bottle', 'stock' => 120, 'price' => '100.00'],
        ];

        foreach ($menu as $row) {
            $inventory = InventoryItem::firstOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'category' => 'Cafeteria',
                    'unit' => $row['unit'],
                    'quantity_on_hand' => 0,
                    'reorder_level' => 10,
                    'is_active' => true,
                    'description' => 'Freshly stocked school cafeteria item for students and staff.',
                ]
            );
            if ($inventory->movements()->doesntExist() && $inventory->quantity_on_hand === 0) {
                InventoryService::recordMovement(
                    $inventory,
                    'receipt',
                    $row['stock'],
                    'DEMO-CAF-OPENING',
                    'Synthetic demo cafeteria opening stock',
                    $admin->id
                );
            }
            CafeteriaItem::firstOrCreate(
                ['inventory_item_id' => $inventory->id],
                [
                    'name' => $row['name'],
                    'description' => 'Prepared and served during the school day at the Colombo main campus.',
                    'price' => $row['price'],
                    'is_available' => true,
                ]
            );
        }
    }
}
