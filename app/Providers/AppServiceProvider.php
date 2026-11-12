<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\LibraryFine;
use App\Models\LibraryLoan;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Policies\AcademicYearPolicy;
use App\Policies\CurriculumPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\LibraryPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\RolePolicy;
use App\Policies\SchoolClassPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StudentPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\TermPolicy;
use App\Policies\UserPolicy;
use App\Services\ConfiguredHttpSmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => RolePolicy::class,
        AcademicYear::class => AcademicYearPolicy::class,
        Term::class => TermPolicy::class,
        Department::class => DepartmentPolicy::class,
        Program::class => ProgramPolicy::class,
        Subject::class => SubjectPolicy::class,
        Curriculum::class => CurriculumPolicy::class,
        SchoolClass::class => SchoolClassPolicy::class,
        Student::class => StudentPolicy::class,
        Setting::class => SettingPolicy::class,
        LibraryBook::class => LibraryPolicy::class,
        LibraryCopy::class => LibraryPolicy::class,
        LibraryLoan::class => LibraryPolicy::class,
        LibraryFine::class => LibraryPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();
        $this->app->bind(SmsGateway::class, ConfiguredHttpSmsGateway::class);
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers()->uncompromised());

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            $userId = $request->session()->get('two_factor.pending_user_id', 'setup');

            return Limit::perMinute(5)->by($userId.'|'.$request->ip());
        });

        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(10)->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip());
        });

        foreach ([
            AcademicYear::class,
            Attendance::class,
            ClassSubject::class,
            Curriculum::class,
            Department::class,
            Exam::class,
            Guardian::class,
            Program::class,
            SchoolClass::class,
            Setting::class,
            StaffAttendance::class,
            Subject::class,
            Teacher::class,
            Term::class,
            User::class,
            Role::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
