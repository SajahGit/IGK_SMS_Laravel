<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ---- Permissions (module.action) per Section 7 matrix ----
        $permissions = [
            // Users
            'users.create', 'users.read', 'users.update', 'users.delete',
            // Roles & permissions
            'roles_and_permissions.create', 'roles_and_permissions.read', 'roles_and_permissions.update', 'roles_and_permissions.delete',
            // Settings
            'settings.read', 'settings.update',
            // Academic years & terms
            'academic_years.create', 'academic_years.read', 'academic_years.update', 'academic_years.delete',
            'terms.create', 'terms.read', 'terms.update', 'terms.delete',
            // Departments, programs, subjects, curriculum
            'departments.create', 'departments.read', 'departments.update', 'departments.delete',
            'programs.create', 'programs.read', 'programs.update', 'programs.delete',
            'subjects.create', 'subjects.read', 'subjects.update', 'subjects.delete',
            'curriculum.create', 'curriculum.read', 'curriculum.update', 'curriculum.delete',
            // Classes & sections
            'classes.create', 'classes.read', 'classes.update', 'classes.delete',
            'sections.create', 'sections.read', 'sections.update', 'sections.delete',
            // Staff
            'staff.create', 'staff.read', 'staff.update', 'staff.delete',
            'staff_attendance.create', 'staff_attendance.read', 'staff_attendance.update',
            // Students
            'students.create', 'students.read', 'students.update', 'students.delete',
            // Guardians
            'guardians.create', 'guardians.read', 'guardians.update', 'guardians.delete',
            // Student documents
            'student_documents.create', 'student_documents.read', 'student_documents.update', 'student_documents.delete',
            // Class subjects
            'class_subjects.create', 'class_subjects.read', 'class_subjects.update', 'class_subjects.delete',
            // Enrollments
            'enrollments.create', 'enrollments.read', 'enrollments.update', 'enrollments.delete',
            // Timetable
            'timetable.create', 'timetable.read', 'timetable.update', 'timetable.delete',
            // Attendance
            'attendance.create', 'attendance.read', 'attendance.update', 'attendance.delete',
            // Exams
            'exams.create', 'exams.read', 'exams.update', 'exams.delete',
            'exam_subjects.create', 'exam_subjects.read', 'exam_subjects.update', 'exam_subjects.delete',
            // Marks
            'marks.read', 'marks.update',
            // Results
            'results.read', 'results.approve', 'results.publish',
            // Grade scale configuration
            'grades.read', 'grades.update',
            // Report card
            'report_card.read',
            // Fee structures
            'fee_structures.create', 'fee_structures.read', 'fee_structures.update', 'fee_structures.delete',
            // Invoices
            'invoices.create', 'invoices.read', 'invoices.update', 'invoices.delete',
            // Payments & receipts
            'payments.create', 'payments.read',
            // Announcements
            'announcements.create', 'announcements.read', 'announcements.update', 'announcements.delete', 'announcements.publish',
            // Reports
            'reports.read',
            'reports.academic',
            'reports.attendance',
            'reports.financial',
            // Audit logs
            'audit_logs.read',
            'imports.create',
            // Library circulation
            'library.create', 'library.read', 'library.update', 'library.delete',
            // Inventory and stock movements
            'inventory.create', 'inventory.read', 'inventory.update', 'inventory.delete',
            'inventory_movements.create',
            // Suppliers and purchase orders
            'suppliers.create', 'suppliers.read', 'suppliers.update', 'suppliers.delete',
            'procurement.create', 'procurement.read', 'procurement.update', 'procurement.delete', 'procurement.approve',
            // Human resources and payroll
            'hr.create', 'hr.read', 'hr.update', 'hr.delete', 'hr.approve',
            'payroll.create', 'payroll.read', 'payroll.update', 'payroll.approve', 'payroll.pay',
            // School transportation
            'transport.create', 'transport.read', 'transport.update', 'transport.delete', 'transport.assign',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ---- Roles per Section 7.1 ----
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $institutionAdmin = Role::firstOrCreate(['name' => 'Institution Admin', 'guard_name' => 'web']);
        $registrar = Role::firstOrCreate(['name' => 'Registrar', 'guard_name' => 'web']);
        $teacher = Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $student = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        $librarian = Role::firstOrCreate(['name' => 'Librarian', 'guard_name' => 'web']);

        // ---- Permission assignments per Section 7.2 matrix ----
        $superAdmin->givePermissionTo($permissions);

        $institutionAdmin->givePermissionTo(array_filter($permissions, function ($p) {
            return ! str_starts_with($p, 'roles_and_permissions.');
        }));

        $registrar->givePermissionTo([
            'users.read',
            'imports.create',
            'academic_years.create', 'academic_years.read', 'academic_years.update', 'academic_years.delete',
            'terms.create', 'terms.read', 'terms.update', 'terms.delete',
            'departments.create', 'departments.read', 'departments.update', 'departments.delete',
            'programs.create', 'programs.read', 'programs.update', 'programs.delete',
            'subjects.create', 'subjects.read', 'subjects.update', 'subjects.delete',
            'curriculum.create', 'curriculum.read', 'curriculum.update', 'curriculum.delete',
            'classes.create', 'classes.read', 'classes.update',
            'staff.read',
            'students.create', 'students.read', 'students.update', 'students.delete',
            'guardians.create', 'guardians.read', 'guardians.update', 'guardians.delete',
            'student_documents.create', 'student_documents.read', 'student_documents.update', 'student_documents.delete',
            'class_subjects.create', 'class_subjects.read', 'class_subjects.update', 'class_subjects.delete',
            'enrollments.create', 'enrollments.read', 'enrollments.update', 'enrollments.delete',
            'timetable.create', 'timetable.read', 'timetable.update', 'timetable.delete',
            'attendance.read', 'attendance.update',
            'exams.create', 'exams.read', 'exams.update', 'exams.delete',
            'exam_subjects.create', 'exam_subjects.read', 'exam_subjects.update', 'exam_subjects.delete',
            'marks.read', 'marks.update',
            'results.read', 'results.approve', 'results.publish',
            'grades.read', 'grades.update',
            'report_card.read',
            'invoices.read',
            'payments.read',
            'announcements.create', 'announcements.read', 'announcements.update', 'announcements.delete', 'announcements.publish',
            'reports.read', 'reports.academic', 'reports.financial',
        ]);

        $teacher->givePermissionTo([
            'academic_years.read', 'terms.read',
            'departments.read', 'programs.read', 'subjects.read',
            'classes.read',
            'students.read',
            'class_subjects.read',
            'enrollments.read',
            'timetable.read',
            'attendance.create', 'attendance.read', 'attendance.update',
            'exams.read',
            'marks.read',
            'results.read',
            'report_card.read',
            'announcements.read',
            'reports.read', 'reports.academic', 'reports.attendance',
        ]);

        $accountant->givePermissionTo([
            'settings.read',
            'academic_years.read', 'terms.read',
            'departments.read', 'programs.read', 'subjects.read',
            'classes.read',
            'students.read',
            'fee_structures.create', 'fee_structures.read', 'fee_structures.update', 'fee_structures.delete',
            'invoices.create', 'invoices.read', 'invoices.update', 'invoices.delete',
            'payments.create', 'payments.read',
            'suppliers.create', 'suppliers.read', 'suppliers.update', 'suppliers.delete',
            'procurement.create', 'procurement.read', 'procurement.update', 'procurement.delete',
            'announcements.read',
            'reports.read', 'reports.financial',
            'payroll.read',
        ]);

        $student->givePermissionTo([
            'students.read',
            'classes.read',
            'report_card.read',
            'announcements.read',
        ]);

        $librarian->givePermissionTo([
            'library.create', 'library.read', 'library.update', 'library.delete',
            'students.read',
        ]);

        // ---- Create Super Admin user (password rules live in SuperAdminSeeder) ----
        $this->call(SuperAdminSeeder::class);
    }
}
