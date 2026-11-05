<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public const EMAIL = 'superadmin@school.test';

    /**
     * Local / demo / test installs use the documented demo password ("password") so the
     * Super Admin panel can be opened straight away. A production deploy gets a strong
     * temporary password that must be changed on first login (SOP PR-6, SEC-15).
     */
    public static function password(): string
    {
        return app()->environment('production') ? 'ChangeMe123!' : 'password';
    }

    public static function mustChangePassword(): bool
    {
        return app()->environment('production');
    }

    public function run(): void
    {
        // Only the first creation sets the password; re-running never resets a rotated one.
        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt(self::password()),
                'role' => 'super-admin',
                'is_active' => true,
                'must_change_password' => self::mustChangePassword(),
            ]
        );

        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $user->syncRoles([$role->name]);
        }
    }
}
