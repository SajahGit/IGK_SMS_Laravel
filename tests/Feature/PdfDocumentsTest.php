<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_download_their_report_card_as_a_pdf(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs($student->user)
            ->get(route('student.report-card.pdf'));

        $response->assertOk();
        $response->assertDownload('report-card-'.$student->id.'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_student_can_download_only_their_own_invoice_pdf(): void
    {
        $student = $this->createStudent();
        $otherStudent = $this->createStudent('other');
        $invoice = $this->createInvoice($student);
        $otherInvoice = $this->createInvoice($otherStudent, 'INV-2026-0002');

        $response = $this->actingAs($student->user)
            ->get(route('student.invoices.pdf', $invoice));

        $response->assertDownload('invoice-'.$invoice->invoice_no.'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->actingAs($student->user)
            ->get(route('student.invoices.pdf', $otherInvoice))
            ->assertNotFound();
    }

    public function test_admin_can_download_an_invoice_and_payment_receipt_pdf(): void
    {
        $student = $this->createStudent();
        $invoice = $this->createInvoice($student);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_no' => 'RCP-2026-0001',
            'amount' => '1250.50',
            'method' => 'bank_transfer',
            'reference' => 'TX-123',
            'paid_at' => '2026-06-15',
        ]);
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertDownload('invoice-'.$invoice->invoice_no.'.pdf');

        $response = $this->actingAs($admin)
            ->get(route('admin.receipt.pdf', $payment));

        $response->assertDownload('receipt-'.$payment->receipt_no.'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->actingAs($admin)
            ->get(route('admin.students.report-card.pdf', $student))
            ->assertDownload('report-card-'.$student->id.'.pdf');
    }

    public function test_guardian_can_download_only_a_linked_child_report_card(): void
    {
        $student = $this->createStudent();
        $guardianUser = User::create([
            'name' => 'Guardian',
            'email' => 'guardian@example.test',
            'password' => 'password',
            'role' => 'parent',
        ]);
        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '555-0100',
        ]);
        $student->update(['guardian_id' => $guardian->id]);

        $response = $this->actingAs($guardianUser)
            ->get(route('guardian.report-card.pdf', $student));

        $response->assertDownload('report-card-'.$student->id.'.pdf');

        $unrelatedStudent = $this->createStudent('other');
        $this->actingAs($guardianUser)
            ->get(route('guardian.report-card.pdf', $unrelatedStudent))
            ->assertForbidden();
    }

    private function createStudent(string $suffix = 'one'): Student
    {
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => $suffix === 'one' ? 'A' : 'B']);
        $user = User::create([
            'name' => 'Student '.$suffix,
            'email' => 'student-'.$suffix.'@example.test',
            'password' => 'password',
            'role' => 'student',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'admission_no' => '2026-TEST-'.($suffix === 'one' ? '0001' : '0002'),
        ]);
    }

    private function createInvoice(Student $student, string $invoiceNo = 'INV-2026-0001'): Invoice
    {
        return Invoice::create([
            'student_id' => $student->id,
            'invoice_no' => $invoiceNo,
            'issue_date' => '2026-06-01',
            'due_date' => '2026-06-30',
            'total' => '1250.50',
            'amount_paid' => '0.00',
            'balance' => '1250.50',
            'status' => 'issued',
        ]);
    }
}
