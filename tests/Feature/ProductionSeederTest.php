<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeder_creates_only_the_break_glass_account_and_configuration(): void
    {
        // The break-glass account is only forced to rotate its password on a production deploy.
        $this->app->detectEnvironment(fn () => 'production');

        // Run the seeder directly: going through `db:seed` would ask the production
        // "are you sure?" confirmation, which cannot be answered inside a test.
        (new ProductionSeeder)->setContainer($this->app)->run();

        $this->assertSame(8, Role::count());
        $this->assertSame(Permission::query()->distinct('name')->count('name'), Permission::count());
        $this->assertSame(1, User::role('Super Admin')->count());
        $this->assertTrue(User::role('Super Admin')->firstOrFail()->must_change_password);
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Result::count());
    }
}
