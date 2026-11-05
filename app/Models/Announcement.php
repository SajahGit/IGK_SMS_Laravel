<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'target_role',
        'program_id',
        'class_id',
        'notifications_sent_at',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'notifications_sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function scopeActiveForRole(Builder $query, string $role): Builder
    {
        return $query->where(function ($q) use ($role) {
            $q->where('target_role', 'all')->orWhere('target_role', $role);
        })
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderByDesc('published_at');
    }

    public function scopeActiveForUser(Builder $query, User $user): Builder
    {
        $role = strtolower($user->role);
        $classIds = collect();
        $programIds = collect();

        if ($user->student) {
            $classIds->push($user->student->class_id);
            if ($user->student->program_id) {
                $programIds->push($user->student->program_id);
            }
        }

        if ($user->teacher) {
            $teacherClasses = $user->teacher->classSubjects()->with('schoolClass')->get()
                ->pluck('schoolClass');
            $classIds = $classIds->merge($teacherClasses->pluck('id'));
            $programIds = $programIds->merge($teacherClasses->pluck('program_id'));
        }

        if ($user->guardian) {
            $children = $user->guardian->students()->with('class')->get();
            $classIds = $classIds->merge($children->pluck('class_id'));
            $programIds = $programIds->merge($children->pluck('program_id'));
        }

        $classIds = $classIds->filter()->unique()->values();
        $programIds = $programIds->filter()->unique()->values();

        return $query->where(function (Builder $target) use ($role) {
            $target->where('target_role', 'all')->orWhere('target_role', $role);
        })
            ->where(function (Builder $target) use ($classIds) {
                $target->whereNull('class_id');
                if ($classIds->isNotEmpty()) {
                    $target->orWhereIn('class_id', $classIds);
                }
            })
            ->where(function (Builder $target) use ($programIds) {
                $target->whereNull('program_id');
                if ($programIds->isNotEmpty()) {
                    $target->orWhereIn('program_id', $programIds);
                }
            })
            ->where('published_at', '<=', now())
            ->where(fn (Builder $target) => $target->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderByDesc('published_at');
    }
}
