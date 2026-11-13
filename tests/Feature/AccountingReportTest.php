<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_expense_report_uses_the_selected_currency_for_each_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::create([
            'name' => 'International School Administrator',
            'email' => 'accounting-report-admin@school.test',
            'password' => 'password',
            'role' => 'admin',
            'must_change_password' => false,
        ]);
        $admin->syncRoles(['Institution Admin']);

        Account::create([
            'code' => '4000',
            'name' => 'International Programme Tuition',
            'type' => 'income',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.accounting.income-expense', ['currency' => 'AED']))
            ->assertOk()
            ->assertSee('AED 0.00');
    }
}
