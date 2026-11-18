<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Branch;
use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrarAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_can_open_its_dashboard_and_read_academic_but_not_financial_reports(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $registrar = User::create([
            'name' => 'Registrar',
            'email' => 'registrar@example.test',
            'password' => 'password',
            'role' => 'registrar',
        ]);
        $registrar->syncRoles(['Registrar']);

        SchoolClass::create(['name' => 'Grade 8', 'section' => 'A']);

        $this->actingAs($registrar)
            ->get(route('registrar.dashboard'))
            ->assertOk()
            ->assertSee('Registrar Dashboard');

        $this->get(route('reports.enrollment'))
            ->assertOk();

        $this->get(route('reports.fee-collection'))
            ->assertForbidden();
        $this->get(route('reports.student-ledger'))
            ->assertForbidden();
    }

    public function test_registrar_gets_scoped_read_access_but_not_role_or_finance_write_access(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $registrar = User::create([
            'name' => 'Registrar',
            'email' => 'registrar@example.test',
            'password' => 'password',
            'role' => 'registrar',
        ]);
        $registrar->syncRoles(['Registrar']);

        $this->actingAs($registrar)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->get(route('admin.students.index'))->assertOk();
        $this->get(route('admin.departments.create'))->assertForbidden();
        $this->get(route('admin.programs.create'))->assertForbidden();
        $this->get(route('admin.curricula.create'))->assertForbidden();
        $this->get(route('admin.exams.create'))->assertOk();
        $this->post(route('admin.departments.store'), ['name' => 'Languages', 'code' => 'LANG'])
            ->assertForbidden();
        $this->assertDatabaseMissing('departments', ['code' => 'LANG']);
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->get(route('accountant.invoices.create'))->assertForbidden();
        $this->get(route('accountant.payments.create'))
            ->assertForbidden();
    }

    public function test_registrar_can_open_create_class_form_with_dropdowns_and_save_class(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $branch = Branch::create([
            'name' => 'Main Campus',
            'code' => 'MAIN',
            'is_main' => true,
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $dept = Department::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $program = Program::create([
            'department_id' => $dept->id,
            'name' => 'Physical Science',
            'code' => 'PS',
            'is_active' => true,
        ]);

        $registrar = User::create([
            'name' => 'Registrar Officer',
            'email' => 'registrar.class@example.test',
            'password' => 'password',
            'role' => 'registrar',
            'branch_id' => $branch->id,
        ]);
        $registrar->syncRoles(['Registrar']);

        $response = $this->actingAs($registrar)->get(route('admin.classes.create'));
        $response->assertOk();
        $response->assertSee('Add class');
        $response->assertSee('Class name (e.g. Grade 10)');
        $response->assertSee('Campus / Branch');
        $response->assertSee('Physical Science');
        $response->assertSee('2026-2027');
        $response->assertSee('Main Campus (MAIN)');

        // Save class
        $postResponse = $this->actingAs($registrar)->post(route('admin.classes.store'), [
            'name' => 'Grade 11',
            'section' => 'A',
            'capacity' => 35,
            'branch_id' => $branch->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);

        $postResponse->assertRedirect(route('admin.classes.index'));
        $postResponse->assertSessionHas('success', 'Class created successfully.');

        $this->assertDatabaseHas('classes', [
            'name' => 'Grade 11',
            'section' => 'A',
            'capacity' => 35,
            'branch_id' => $branch->id,
            'program_id' => $program->id,
            'academic_year_id' => $year->id,
        ]);

        $schoolClass = SchoolClass::where('name', 'Grade 11')->where('section', 'A')->firstOrFail();
        $editResponse = $this->get(route('admin.classes.edit', $schoolClass));
        $editResponse->assertOk();
        $editResponse->assertSee('Grade 11');
        $editResponse->assertSee('Update Class');
    }

    public function test_registrar_can_see_teachers_and_students_across_all_branches_with_assigned_classes_and_campuses(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $branch1 = Branch::create(['name' => 'Colombo Campus', 'code' => 'COL', 'is_main' => true, 'is_active' => true]);
        $branch2 = Branch::create(['name' => 'Kandy Campus', 'code' => 'KDY', 'is_main' => false, 'is_active' => true]);

        $year = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $term = Term::create([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'sequence' => 1,
            'is_current' => true,
        ]);

        $classCol = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A', 'branch_id' => $branch1->id]);
        $classKdy = SchoolClass::create(['name' => 'Grade 11', 'section' => 'B', 'branch_id' => $branch2->id]);

        $subjectMath = Subject::create(['name' => 'Mathematics', 'code' => 'MATH101']);
        $subjectPhys = Subject::create(['name' => 'Physics', 'code' => 'PHYS101']);

        // Teacher 1 in Colombo Campus
        $userT1 = User::create(['name' => 'Colombo Teacher', 'email' => 't1@example.test', 'password' => 'password', 'role' => 'teacher', 'branch_id' => $branch1->id]);
        $teacher1 = Teacher::create(['user_id' => $userT1->id, 'branch_id' => $branch1->id, 'status' => 'active']);
        ClassSubject::create(['class_id' => $classCol->id, 'subject_id' => $subjectMath->id, 'teacher_id' => $teacher1->id, 'term_id' => $term->id]);

        // Teacher 2 in Kandy Campus
        $userT2 = User::create(['name' => 'Kandy Teacher', 'email' => 't2@example.test', 'password' => 'password', 'role' => 'teacher', 'branch_id' => $branch2->id]);
        $teacher2 = Teacher::create(['user_id' => $userT2->id, 'branch_id' => $branch2->id, 'status' => 'active']);
        ClassSubject::create(['class_id' => $classKdy->id, 'subject_id' => $subjectPhys->id, 'teacher_id' => $teacher2->id, 'term_id' => $term->id]);

        // Students in both campuses
        $userS1 = User::create(['name' => 'Colombo Student', 'email' => 's1@example.test', 'password' => 'password', 'role' => 'student', 'branch_id' => $branch1->id]);
        $student1 = Student::create(['user_id' => $userS1->id, 'class_id' => $classCol->id, 'branch_id' => $branch1->id, 'admission_no' => 'COL-001', 'admission_date' => '2026-01-01', 'status' => 'active']);

        $userS2 = User::create(['name' => 'Kandy Student', 'email' => 's2@example.test', 'password' => 'password', 'role' => 'student', 'branch_id' => $branch2->id]);
        $student2 = Student::create(['user_id' => $userS2->id, 'class_id' => $classKdy->id, 'branch_id' => $branch2->id, 'admission_no' => 'KDY-001', 'admission_date' => '2026-01-01', 'status' => 'active']);

        // Registrar with no branch restriction
        $registrar = User::create([
            'name' => 'Global Registrar',
            'email' => 'global.registrar@example.test',
            'password' => 'password',
            'role' => 'registrar',
            'branch_id' => null,
        ]);
        $registrar->syncRoles(['Registrar']);

        // Registrar sees teachers across all branches with campus and classes
        $teachersResponse = $this->actingAs($registrar)->get(route('admin.teachers.index'));
        $teachersResponse->assertOk();
        $teachersResponse->assertSee('Colombo Teacher');
        $teachersResponse->assertSee('Colombo Campus');
        $teachersResponse->assertSee('Grade 10 - A');
        $teachersResponse->assertSee('Mathematics');
        $teachersResponse->assertSee('Kandy Teacher');
        $teachersResponse->assertSee('Kandy Campus');
        $teachersResponse->assertSee('Grade 11 - B');
        $teachersResponse->assertSee('Physics');

        // Registrar sees students across all branches
        $studentsResponse = $this->actingAs($registrar)->get(route('admin.students.index'));
        $studentsResponse->assertOk();
        $studentsResponse->assertSee('Colombo Student');
        $studentsResponse->assertSee('Kandy Student');
    }

    public function test_global_auto_sort_by_latest_activity_moves_created_and_edited_records_to_top(): void
    {
        $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class])->run();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@example.test',
            'password' => 'password',
            'role' => 'super-admin',
        ]);
        $admin->syncRoles(['Super Admin']);

        // Create 2 teachers with staggered updated_at
        $t1User = User::create(['name' => 'Alpha Teacher', 'email' => 'alpha@test.com', 'password' => 'password', 'role' => 'teacher']);
        $t1 = Teacher::create(['user_id' => $t1User->id, 'status' => 'active']);
        \Illuminate\Support\Facades\DB::table('teachers')->where('id', $t1->id)->update(['updated_at' => now()->subMinutes(10)]);

        $t2User = User::create(['name' => 'Beta Teacher', 'email' => 'beta@test.com', 'password' => 'password', 'role' => 'teacher']);
        $t2 = Teacher::create(['user_id' => $t2User->id, 'status' => 'active']);
        \Illuminate\Support\Facades\DB::table('teachers')->where('id', $t2->id)->update(['updated_at' => now()->subMinutes(5)]);

        // Initially, Beta (subMinutes(5)) is newer than Alpha (subMinutes(10))
        $response = $this->actingAs($admin)->get(route('admin.teachers.index'));
        $response->assertOk();
        $teachers = $response->viewData('teachers');
        $this->assertEquals($t2->id, $teachers->first()->id);

        // Edit/update Alpha Teacher now
        $updateResponse = $this->actingAs($admin)->put(route('admin.teachers.update', $t1), [
            'name' => 'Alpha Teacher Updated',
            'email' => 'alpha@test.com',
            'status' => 'active',
        ]);
        $updateResponse->assertSessionHasNoErrors();

        // Alpha Teacher is now at the very top!
        $responseAfterUpdate = $this->actingAs($admin)->get(route('admin.teachers.index'));
        $teachersAfterUpdate = $responseAfterUpdate->viewData('teachers');
        $this->assertEquals($t1->id, $teachersAfterUpdate->first()->id);

        // Now create a third teacher, Gamma Teacher
        $createResponse = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => 'Gamma Teacher Fresh',
            'email' => 'gamma@test.com',
            'password' => 'SecurePass123!',
            'status' => 'active',
        ]);
        $createResponse->assertSessionHasNoErrors();

        // Gamma Teacher is now at the very top!
        $responseAfterCreate = $this->actingAs($admin)->get(route('admin.teachers.index'));
        $teachersAfterCreate = $responseAfterCreate->viewData('teachers');
        $this->assertEquals('Gamma Teacher Fresh', $teachersAfterCreate->first()->user->name);
    }
}
