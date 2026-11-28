<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'is_active',
        'lms_material_access',
        'lms_video_access',
        'lms_pdf_access',
        'lms_can_upload_materials',
        'must_change_password',
        'last_login_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_enabled_at',
        'two_factor_last_used_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'lms_material_access' => true,
        'lms_video_access' => true,
        'lms_pdf_access' => true,
        'lms_can_upload_materials' => true,
        'must_change_password' => false,
    ];

    protected $hidden = ['password', 'remember_token'];

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->relationLoaded('teacher') && $user->teacher) {
                $user->teacher->touch();
            } elseif ($user->teacher()->exists()) {
                $user->teacher()->first()?->touch();
            }

            if ($user->relationLoaded('student') && $user->student) {
                $user->student->touch();
            } elseif ($user->student()->exists()) {
                $user->student()->first()?->touch();
            }

            if ($user->relationLoaded('guardian') && $user->guardian) {
                $user->guardian->touch();
            } elseif ($user->guardian()->exists()) {
                $user->guardian()->first()?->touch();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'lms_material_access' => 'boolean',
            'lms_video_access' => 'boolean',
            'lms_pdf_access' => 'boolean',
            'lms_can_upload_materials' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_enabled_at' => 'datetime',
            'two_factor_last_used_at' => 'integer',
        ];
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function uploadedLearningMaterials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'uploaded_by');
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

    public function requiresTwoFactor(): bool
    {
        if (! config('school.two_factor_enabled', true)) {
            return false;
        }

        return $this->isSuperAdmin() || $this->isInstitutionAdmin();
    }

    public function isTeacher(): bool
    {
        return strtolower($this->role) === 'teacher';
    }

    public function isStudent(): bool
    {
        return strtolower($this->role) === 'student';
    }

    public function isRegistrar(): bool
    {
        return $this->hasRole('Registrar') || strtolower($this->role) === 'registrar';
    }

    public function isAccountant(): bool
    {
        return $this->hasRole('Accountant') || strtolower($this->role) === 'accountant';
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
            'librarian' => 'library.dashboard',
            'student' => 'student.dashboard',
            default => 'login',
        };
    }
}
