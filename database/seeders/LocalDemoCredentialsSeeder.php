<?php

namespace Database\Seeders;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class LocalDemoCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Default demo credentials may only be set in local or testing environments.');
        }

        if (! Role::query()->where('name', 'Super Admin')->exists()) {
            $this->call(RolePermissionSeeder::class);
        }

        $this->ensureRoleLoginAccounts();

        $users = User::query()
            ->where(function ($query): void {
                $query->whereIn('email', [
                    'superadmin@school.test',
                    'admin@school.test',
                    'registrar@school.test',
                    'teacher@school.test',
                    'teacher2@school.test',
                    'accountant@school.test',
                    'student@school.test',
                    'guardian@school.test',
                ])
                    ->orWhere('email', 'like', 'student%@school.test')
                    ->orWhere('email', 'like', 'guardian%@school.test')
                    ->orWhere('email', 'like', 'demo.%@school.test')
                    ->orWhere('email', 'like', 'perf.%@school.test');
            })
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        User::query()->whereKey($users->modelKeys())->update([
            'password' => Hash::make('password'),
            'must_change_password' => false,
            'updated_at' => now(),
        ]);

        $roleMap = [
            'super-admin' => 'Super Admin',
            'admin' => 'Institution Admin',
            'teacher' => 'Teacher',
            'student' => 'Student',
            'registrar' => 'Registrar',
            'accountant' => 'Accountant',
            'parent' => 'Guardian',
        ];
        $users->each(function (User $user) use ($roleMap): void {
            if (isset($roleMap[$user->role])) {
                $user->syncRoles([$roleMap[$user->role]]);
            }
        });
    }

    private function ensureRoleLoginAccounts(): void
    {
        foreach ([
            'registrar@school.test' => ['Amina Rahman', 'registrar'],
            'accountant@school.test' => ['Daniel Osei', 'accountant'],
        ] as $email => [$name, $role]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'is_active' => true,
                'must_change_password' => false,
            ]);
        }

        $this->ensureProfileLogin('teacher@school.test', 'Mr. Lathif', 'teacher', Teacher::class, 'user_id');
        $this->ensureProfileLogin('teacher2@school.test', 'Ms. Nisha', 'teacher', Teacher::class, 'user_id');
        $this->ensureProfileLogin('student@school.test', 'Aarav Chen', 'student', Student::class, 'user_id');

        $guardian = User::firstOrCreate(['email' => 'guardian@school.test'], [
            'name' => 'Nadia Hassan',
            'password' => 'password',
            'role' => 'parent',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        if (! Guardian::where('user_id', $guardian->id)->exists()) {
            $source = Student::query()->with('guardian')->whereHas('user', function ($query): void {
                $query->where('email', 'like', 'demo.student.%@school.test');
            })->first()?->guardian;
            if ($source) {
                $source->update(['user_id' => $guardian->id, 'nationality' => 'Sri Lankan']);
            }
        }
    }

    private function ensureProfileLogin(string $email, string $name, string $role, string $profileClass, string $foreignKey): void
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        if ($profileClass === Teacher::class && $user->name !== $name) {
            $user->update(['name' => $name]);
        }

        if ($profileClass::where($foreignKey, $user->id)->exists()) {
            return;
        }

        $source = $profileClass::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'like', $role === 'teacher' ? 'demo.staff.%@school.test' : 'demo.student.%@school.test'))
            ->orderBy('id')
            ->first();
        $source?->update([$foreignKey => $user->id]);
    }
}
