# IGK ERP Module Implementation Status

This is a current implementation snapshot, not evidence that every SOP acceptance criterion is complete. See [IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md](./IGK_ERP_FINAL_SOP_COMPLIANCE_AUDIT.md) for known gaps and sign-off requirements.

| Module | Exists | Tables | Models | Controllers | Services | Requests | Policies | Routes | Views | Tests | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| M1 Auth | Yes | users,sessions | User | Auth controllers | — | LoginRequest | — | auth routes | auth/* | Phase1Auth | Complete |
| M2 RBAC | Yes | roles,permissions,model_has_roles | Role,Permission | RoleController | — | — | Policies | role routes | roles/* | Phase1Rbac | Complete |
| M3 Users | Yes | users | User | UserController | — | StoreUserRequest | UserPolicy | user routes | users/* | Phase1Rbac | Complete |
| M4 Academic Year/Term | Yes | academic_years,terms | AcademicYear,Term | AcademicYearController,TermController | — | — | — | academic/* | Phase1Auth | Complete |
| M5 Departments/Programs/Subjects/Curriculum | Yes | departments,programs,subjects,curriculums | Department,Program,Subject,Curriculum | *Controller | — | — | — | academic/* | CurriculumModule | Complete |
| M6 Classes/Sections | Yes | classes,sections | ClassModel,Section | ClassController,SectionController | — | — | — | academic/* | Phase2 | Complete |
| M7 Staff | Yes | teachers | Teacher | TeacherController | — | — | — | admin routes | teacher/* | Full suite | Department/status fields and assignments implemented; acceptance walkthrough pending |
| M8 Students | Yes | students | Student | StudentController | EnrollmentService | — | StudentPolicy | student routes | students/* | Phase2,Enrollment | Student profiles, enrollment, and filtered export implemented |
| M9 Guardians/Documents | Yes | guardians,guardian_student,student_documents | Guardian,StudentDocument | GuardianController,StudentController | — | — | — | admin routes | students/* | Phase2,Phase3,Phase5 | Many-to-many guardian links and primary-contact support implemented; multiple-guardian UAT pending |
| M10 Search/Export | Yes | students | Student | Admin\StudentController | — | — | — | admin routes | students/* | StudentRegisterExport | Complete: filtered CSV export tested |
| M11 Class Subjects | Yes | class_subjects | ClassSubject | ClassSubjectController | — | — | — | routes | academic/* | — | Complete |
| M12 Enrollment | Yes | student_subject_enrollments | Student,ClassSubject | StudentController,ClassSubjectController | EnrollmentService | — | — | admin routes | enrollment/* | EnrollmentAndMarks | Term-aware class-subject enrollment implemented; promotion/drop acceptance walkthrough pending |
| M13 Timetable | Yes | timetables | Timetable | TimetableController | — | — | — | routes | timetable/* | Phase2 | Complete |
| M14 Attendance | Yes | attendance,attendance_sessions | Attendance,AttendanceSession | AttendanceController | AttendanceSessionService | StoreAttendanceRequest | AttendancePolicy | teacher/admin routes | attendance/* | Phase2,ReportsParity | Session-based attendance, scope, remarks, excused status, and Registrar correction implemented |
| M15 Exams | Yes | exams,exam_subjects | Exam,ExamSubject | ExamController | — | — | — | routes | exams/* | Phase3 | Complete |
| M16 Marks | Yes | marks | Mark | MarkController | MarksEntryService | — | MarkPolicy | routes | marks/* | EnrollmentAndMarks | Complete |
| M17 Grading | Yes | grade_scales | GradeScale | — | GradingService | — | — | — | — | GradingServiceTest | Complete |
| M18 Results/Report Card | Yes | results | Result | ResultController | — | — | — | routes | results/*,report_card | Phase3 | Complete |
| M19 Fee Structures | Yes | fee_structures | FeeStructure | FeeStructureController | — | — | — | routes | fees/* | Phase4 | Complete |
| M20 Invoices | Yes | invoices | Invoice | InvoiceController | — | — | — | routes | invoices/* | Phase4 | Complete |
| M21 Payments/Receipts | Yes | payments,receipts | Payment,Receipt | PaymentController | — | — | — | routes | payments/* | Phase4 | Complete |
| M22 Announcements | Yes | announcements,notifications | Announcement | AnnouncementController | AnnouncementDeliveryService | — | — | admin routes, scheduled command | announcements/* | Phase5,AccountantFinanceAccess | Role/program/class targeting, in-app notifications, queued email, scheduled delivery, and notification dropdown regression are verified; migration 028 repairs a missing notification table |
| M23 Dashboards | Yes | — | — | role dashboard controllers | — | — | — | role routes | role dashboards | RegistrarAccess | All six local role dashboards checked; Super Admin role management and Institution Admin denial verified; full workflow UAT pending |
| M24 Reports | Yes | — | — | Report controllers | — | — | — | reports routes | reports/* | ReportsParity,Phase5 | R1-R11 filters/columns reviewed; CSV headers aligned and regression-tested; product-owner sign-off and performance checks pending |
| M25 Audit Logs | Yes | audit_logs | AuditLog | AuditLogController | — | — | — | routes | audit/* | Phase5 | Complete |
| M26 UI Foundation | Yes | — | — | — | — | — | — | — | components/* | — | Complete |

Automated tests: 201 passed, 965 assertions, 1 skipped after removing the non-SOP cashbook application feature. Pint passed for the modified route files. Disposable SQLite migration/seed and a real local queue-worker job passed. The configured local manual-test database's missing notification and finance cashbook tables were repaired additively; the cashbook application routes/screens/code/tests have since been removed, while its applied migration/table remain untouched to preserve history and data. Full workflow UAT, coverage measurement, permission-catalog reconciliation (78 SOP vs 110 seeded), product-owner report sign-off, and production deployment/restore remain outstanding.
