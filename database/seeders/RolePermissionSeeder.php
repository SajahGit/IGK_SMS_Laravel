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
            'marks.create', 'marks.read', 'marks.update',
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
        $guardian = Role::firstOrCreate(['name' => 'Guardian', 'guard_name' => 'web']);
        $librarian = Role::firstOrCreate(['name' => 'Librarian', 'guard_name' => 'web']);

        // ---- Permission assignments per Section 7.2 matrix ----
        $superAdmin->syncPermissions($permissions);

        $institutionAdminPermissions = array_values(array_filter($permissions, function ($permission) {
            return (! str_starts_with($permission, 'roles_and_permissions.')
                    || $permission === 'roles_and_permissions.read')
                && ! in_array($permission, ['invoices.delete', 'payments.create'], true);
        }));
        $institutionAdmin->syncPermissions($institutionAdminPermissions);

        $registrar->syncPermissions([
            'users.read',
            'imports.create',
            'academic_years.read', 'terms.read',
            'departments.read', 'programs.read', 'subjects.read', 'curriculum.read',
            'classes.create', 'classes.read', 'classes.update',
            'staff.read',
            'students.create', 'students.read', 'students.update', 'students.delete',
            'guardians.create', 'guardians.read', 'guardians.update', 'guardians.delete',
            'student_documents.create', 'student_documents.read', 'student_documents.update', 'student_documents.delete',
            'class_subjects.create', 'class_subjects.read', 'class_subjects.update',
            'enrollments.create', 'enrollments.read', 'enrollments.update', 'enrollments.delete',
            'timetable.create', 'timetable.read', 'timetable.update',
            'attendance.read', 'attendance.update',
            'exams.create', 'exams.read', 'exams.update', 'exams.delete',
            'exam_subjects.create', 'exam_subjects.read', 'exam_subjects.update', 'exam_subjects.delete',
            'marks.read', 'marks.update',
            'results.read', 'results.approve', 'results.publish',
            'grades.read', 'grades.update',
            'report_card.read',
            'fee_structures.read',
            'invoices.read',
            'payments.read',
            'announcements.create', 'announcements.read', 'announcements.update', 'announcements.publish',
            'reports.read', 'reports.academic',
        ]);

        $teacher->syncPermissions([
            'academic_years.read', 'terms.read',
            'departments.read', 'programs.read', 'subjects.read', 'curriculum.read',
            'classes.read', 'staff.read', 'students.read', 'guardians.read',
            'class_subjects.read', 'enrollments.read', 'timetable.read',
            'attendance.create', 'attendance.read', 'attendance.update',
            'exams.read', 'marks.create', 'marks.read', 'marks.update',
            'results.read',
            'report_card.read',
            'announcements.read',
            'reports.read', 'reports.academic', 'reports.attendance',
        ]);

        $accountant->syncPermissions([
            'settings.read',
            'academic_years.read', 'terms.read',
            'departments.read', 'programs.read', 'subjects.read', 'curriculum.read',
            'classes.read', 'staff.read', 'students.read', 'guardians.read', 'enrollments.read',
            'fee_structures.create', 'fee_structures.read', 'fee_structures.update', 'fee_structures.delete',
            'invoices.create', 'invoices.read', 'invoices.update',
            'payments.create', 'payments.read',
            'announcements.read',
            'reports.read', 'reports.financial',
        ]);

        $student->syncPermissions([
            'students.read',
            'departments.read', 'programs.read', 'subjects.read', 'curriculum.read',
            'classes.read',
            'guardians.read', 'class_subjects.read', 'enrollments.read', 'timetable.read',
            'attendance.read', 'exams.read', 'results.read',
            'report_card.read',
            'invoices.read', 'payments.read',
            'announcements.read',
        ]);

        $guardian->syncPermissions([
            'students.read',
            'guardians.read',
            'student_documents.read',
            'attendance.read',
            'results.read',
            'report_card.read',
            'invoices.read',
            'payments.read',
            'announcements.read',
        ]);

        $librarian->syncPermissions([
            'students.read',
            'library.create', 'library.read', 'library.update', 'library.delete',
            'announcements.read',
        ]);

        // ---- Create Super Admin user (password rules live in SuperAdminSeeder) ----
        $this->call(SuperAdminSeeder::class);
    }
}
