<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function isAdmin(): bool
    {
        return in_array(strtolower($this->role), ['admin', 'super-admin', 'institution admin', 'super admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin')
            || in_array(strtolower($this->role), ['super-admin', 'super admin'], true);
    }

    public function isInstitutionAdmin(): bool
    {
        return ! $this->isSuperAdmin()
            && ($this->hasRole('Institution Admin')
                || in_array(strtolower($this->role), ['admin', 'institution admin'], true));
    }

    public function isTeacher(): bool
    {
        return strtolower($this->role) === 'teacher';
    }

    public function isStudent(): bool
    {
        return strtolower($this->role) === 'student';
    }

    public function isAccountant(): bool
    {
        return strtolower($this->role) === 'accountant';
    }

    public function isParent(): bool
    {
        return strtolower($this->role) === 'parent';
    }

    /** Route name of the dashboard for this user's role. */
    public function dashboardRoute(): string
    {
        return match (strtolower($this->role)) {
            'admin' => 'admin.dashboard',
            'super-admin' => 'admin.dashboard',
            'institution admin', 'super admin' => 'admin.dashboard',
            'teacher' => 'teacher.dashboard',
            'accountant' => 'accountant.dashboard',
            'registrar' => 'registrar.dashboard',
            'parent' => 'guardian.dashboard',
            'student' => 'student.dashboard',
            default => 'login',
        };
    }
}
