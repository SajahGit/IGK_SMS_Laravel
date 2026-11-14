<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\FeeItem;
use App\Models\FeeStructure;
use App\Models\LibraryAuthor;
use App\Models\LibraryBook;
use App\Models\LibraryCategory;
use App\Models\LibraryCopy;
use App\Models\LibraryFine;
use App\Models\LibraryLoan;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Fills every table the other demo seeders leave empty: library, fee items, curriculum,
 * payment reversal, private messages and student documents. Safe to run repeatedly.
 */
class CompleteSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Sample data may only be seeded in local or testing environments.');
        }

        $admin = User::query()->whereIn('role', ['admin', 'super-admin'])->orderBy('id')->first() ?? User::query()->orderBy('id')->first();
        if (! $admin) {
            throw new RuntimeException('Seed users first (run DemoDataSeeder or DatabaseSeeder).');
        }

        $this->seedLibrary($admin);
        $this->seedFeePackages();
        $this->call(AcademicStructureSeeder::class);
        $this->seedAnnouncements();
        $this->seedPaymentReversal($admin);
        $this->seedMessages($admin);
        $this->seedStudentDocuments($admin);
    }

    private function seedAnnouncements(): void
    {
        $ibMiddleYears = Program::query()->where('code', 'IBMYP')->firstOrFail();
        $gradeTen = SchoolClass::query()
            ->where('name', 'Grade 10')
            ->where('section', 'A')
            ->firstOrFail();

        foreach ([
            [
                'title' => '2026-2027 International School Academic Calendar',
                'body' => 'Term 1 concludes on 15 January 2027. Families can review the published term dates in the academic calendar.',
                'target_role' => 'all',
                'program_id' => null,
                'class_id' => null,
            ],
            [
                'title' => 'IB MYP Grade 10 Assessment Schedule',
                'body' => 'Grade 10A students should review the Term 1 assessment schedule with their subject teachers.',
                'target_role' => 'student',
                'program_id' => $ibMiddleYears->id,
                'class_id' => $gradeTen->id,
            ],
            [
                'title' => 'IB MYP Grade 10 Mark Submission',
                'body' => 'Teachers assigned to Grade 10A should submit and verify Term 1 marks before the registrar review.',
                'target_role' => 'teacher',
                'program_id' => $ibMiddleYears->id,
                'class_id' => $gradeTen->id,
            ],
            [
                'title' => 'Grade 10 Family-Teacher Conference',
                'body' => 'Parents and guardians of Grade 10A students may book a conference with the teaching team through the school office.',
                'target_role' => 'parent',
                'program_id' => $ibMiddleYears->id,
                'class_id' => $gradeTen->id,
            ],
            [
                'title' => 'Registrar Term 1 Admissions Review',
                'body' => 'Please complete the international admissions-file review before the Term 1 results publication meeting.',
                'target_role' => 'registrar',
                'program_id' => null,
                'class_id' => null,
            ],
        ] as $announcement) {
            Announcement::firstOrCreate(
                ['title' => $announcement['title']],
                $announcement + [
                    'published_at' => now()->subDays(2),
                    'expires_at' => now()->addMonths(3),
                    'created_by' => User::query()->whereIn('role', ['admin', 'super-admin'])->orderBy('id')->value('id'),
                ]
            );
        }
    }

    private function seedLibrary(User $admin): void
    {
        $authors = ['Chinua Achebe', 'Jane Austen', 'George Orwell', 'Marie Curie', 'Stephen Hawking', 'R. K. Narayan', 'Khaled Hosseini', 'A. P. J. Abdul Kalam', 'I. M. Gelfand'];
        $categories = ['Fiction', 'Science', 'Mathematics', 'History', 'Biography', 'Reference', 'Technology'];
        foreach ($authors as $name) {
            LibraryAuthor::firstOrCreate(['name' => $name]);
        }
        foreach ($categories as $name) {
            LibraryCategory::firstOrCreate(['name' => $name]);
        }

        $a = fn (string $n) => LibraryAuthor::where('name', $n)->value('id');
        $c = fn (string $n) => LibraryCategory::where('name', $n)->value('id');
        $books = [
            ['Things Fall Apart', 'Chinua Achebe', 'Fiction', '9780385474542', 1958],
            ['Pride and Prejudice', 'Jane Austen', 'Fiction', '9780141439518', 1813],
            ['Nineteen Eighty-Four', 'George Orwell', 'Fiction', '9780451524935', 1949],
            ['Animal Farm', 'George Orwell', 'Fiction', '9780451526342', 1945],
            ['Radioactive Substances', 'Marie Curie', 'Science', '9780000000011', 1903],
            ['A Brief History of Time', 'Stephen Hawking', 'Science', '9780553380163', 1988],
            ['The Universe in a Nutshell', 'Stephen Hawking', 'Science', '9780553802023', 2001],
            ['Malgudi Days', 'R. K. Narayan', 'Fiction', '9780143039655', 1943],
            ['The Kite Runner', 'Khaled Hosseini', 'Fiction', '9781594631931', 2003],
            ['Wings of Fire', 'A. P. J. Abdul Kalam', 'Biography', '9788173711466', 1999],
            ['Ignited Minds', 'A. P. J. Abdul Kalam', 'Biography', '9780143033110', 2002],
            ['Algebra', 'I. M. Gelfand', 'Mathematics', '9780817636777', 1961],
        ];
        foreach ($books as [$title, $author, $category, $isbn, $year]) {
            $book = LibraryBook::firstOrCreate(['isbn' => $isbn], [
                'author_id' => $a($author), 'category_id' => $c($category), 'title' => $title, 'publication_year' => $year,
            ]);
            for ($n = 1; $n <= 3; $n++) {
                LibraryCopy::firstOrCreate(['book_id' => $book->id, 'copy_number' => $n], [
                    'accession_no' => sprintf('LIB-%06d-%03d', $book->id, $n),
                    'condition' => $n === 3 ? 'fair' : 'good',
                    'status' => 'available',
                ]);
            }
        }

        if (LibraryLoan::query()->exists()) {
            return;
        }

        $students = Student::query()->orderBy('id')->take(12)->pluck('id')->values();
        $copies = LibraryCopy::query()->where('status', 'available')->orderBy('id')->take(12)->get()->values();
        if ($students->count() < 12 || $copies->count() < 12) {
            return;
        }

        $make = function (int $i, string $issued, string $due, ?string $returned) use ($students, $copies, $admin): LibraryLoan {
            $loan = LibraryLoan::create([
                'copy_id' => $copies[$i]->id,
                'student_id' => $students[$i],
                'issued_by' => $admin->id,
                'returned_by' => $returned ? $admin->id : null,
                'issued_at' => now()->subDays((int) $issued),
                'due_at' => now()->subDays((int) $due)->toDateString(),
                'returned_at' => $returned ? now()->subDays((int) $returned) : null,
            ]);
            $copies[$i]->update(['status' => $returned ? 'available' : 'borrowed']);

            return $loan;
        };

        foreach (range(0, 5) as $i) {        // returned on time
            $make($i, '30', '16', '20');
        }
        foreach ([6, 7, 8] as $i) {          // currently active
            $make($i, '5', '-9', null);
        }
        foreach ([9, 10] as $i) {            // overdue, still out
            $make($i, '40', '26', null);
        }
        $late = $make(11, '45', '31', '25'); // returned late -> unpaid fine
        LibraryFine::create(['loan_id' => $late->id, 'amount' => '60.00', 'reason' => 'Returned 6 days late', 'paid_at' => null, 'collected_by' => null]);
        $paid = LibraryLoan::query()->orderBy('id')->first(); // an older loan with a paid fine
        if ($paid && ! $paid->fine) {
            LibraryFine::create(['loan_id' => $paid->id, 'amount' => '25.00', 'reason' => 'Damaged cover', 'paid_at' => now()->subDays(10), 'collected_by' => $admin->id]);
        }
    }

    private function seedFeePackages(): void
    {
        $year = AcademicYear::query()->where('is_current', true)->first() ?? AcademicYear::query()->orderBy('id')->first();
        if (! $year) {
            return;
        }
        $term = Term::firstOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Term 2'],
            ['sequence' => 2, 'start_date' => '2027-01-10', 'end_date' => '2027-04-30', 'is_current' => false]
        );

        foreach (Program::query()->get() as $program) {
            $currency = config('school.program_currencies.'.$program->code, config('school.default_currency', 'LKR'));
            $currencyFees = [
                'LKR' => ['5000.00', '8000.00', '30000.00', '4500.00', '6000.00', '1500.00'],
                'USD' => ['50.00', '80.00', '300.00', '45.00', '60.00', '15.00'],
                'GBP' => ['40.00', '65.00', '240.00', '35.00', '48.00', '12.00'],
                'EUR' => ['45.00', '75.00', '275.00', '40.00', '55.00', '14.00'],
                'AED' => ['185.00', '295.00', '1100.00', '165.00', '220.00', '55.00'],
            ];
            [$registration, $books, $tuition, $examination, $laboratory, $optionalDrafts] = $currencyFees[$currency];
            $items = [
                ['Student Registration Fee', $registration, true],
                ['Course Materials and Books', $books, true],
                ['Term Tuition', $tuition, true],
                ['Assessment and Examination Fee', $examination, true],
                ['Science Laboratory Fee', $laboratory, true],
                ['Optional Additional Practice Papers', $optionalDrafts, false],
            ];
            $mandatory = '0.00';
            foreach ($items as [, $amount, $isMandatory]) {
                if ($isMandatory) {
                    $mandatory = Money::add($mandatory, $amount);
                }
            }

            $structure = FeeStructure::firstOrCreate(
                ['name' => 'Term 2 Fee Package', 'program_id' => $program->id, 'academic_year_id' => $year->id, 'class_id' => null, 'term_id' => $term->id],
                ['amount' => $mandatory, 'currency' => $currency, 'is_active' => true]
            );
            if (! $structure->isReferencedByInvoices()) {
                $structure->update(['amount' => $mandatory, 'currency' => $currency]);
            }
            if ($structure->wasRecentlyCreated || ! FeeItem::where('fee_structure_id', $structure->id)->exists()) {
                foreach ($items as [$name, $amount, $isMandatory]) {
                    FeeItem::firstOrCreate(['fee_structure_id' => $structure->id, 'name' => $name], ['amount' => $amount, 'is_mandatory' => $isMandatory]);
                }
            }
        }
    }

    private function seedPaymentReversal(User $admin): void
    {
        if (PaymentReversal::query()->exists()) {
            return;
        }
        $payment = Payment::query()->whereNull('reversed_at')->orderByDesc('id')->first();
        if (! $payment) {
            return;
        }
        PaymentService::reversePayment($payment, 'Duplicate tuition receipt entered during reconciliation', $admin->id);
    }

    private function seedMessages(User $admin): void
    {
        $others = User::query()
            ->whereIn('email', [
                'superadmin@school.test',
                'admin@school.test',
                'registrar@school.test',
                'accountant@school.test',
                'librarian@school.test',
                'teacher@school.test',
                'student@school.test',
                'guardian@school.test',
            ])
            ->where('is_active', true)
            ->whereKeyNot($admin->id)
            ->orderBy('id')
            ->get()
            ->filter()
            ->values();

        foreach ($others as $user) {
            if ($user->id === $admin->id) {
                continue;
            }
            $pairKey = hash('sha256', min($admin->id, $user->id).':'.max($admin->id, $user->id));
            $conversation = DB::table('message_conversations')->where('pair_key', $pairKey)->first();
            $conversationId = $conversation?->id ?? DB::table('message_conversations')->insertGetId([
                'pair_key' => $pairKey,
                'subject' => 'Term planning',
                'created_by' => $admin->id,
                'last_message_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ([$admin->id, $user->id] as $userId) {
                DB::table('message_participants')->insertOrIgnore([
                    'conversation_id' => $conversationId,
                    'user_id' => $userId,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if (DB::table('school_messages')->where('conversation_id', $conversationId)->exists()) {
                continue;
            }

            DB::table('school_messages')->insert([
                ['conversation_id' => $conversationId, 'sender_id' => $admin->id, 'body' => 'Hello '.$user->name.', please share your Term 2 requirements by Friday.', 'delivered_at' => now()->subHours(5), 'read_at' => now()->subHours(4), 'created_at' => now()->subHours(5), 'updated_at' => now()->subHours(4)],
                ['conversation_id' => $conversationId, 'sender_id' => $user->id, 'body' => 'Thank you, I will send the list today.', 'delivered_at' => now()->subHours(3), 'read_at' => null, 'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3)],
            ]);
            DB::table('message_conversations')->where('id', $conversationId)->update([
                'last_message_at' => now()->subHours(3),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedStudentDocuments(User $admin): void
    {
        if (StudentDocument::query()->exists()) {
            return;
        }
        foreach (Student::query()->orderBy('id')->take(10)->get() as $student) {
            foreach ([
                ['birth_cert', 'birth-registration-record.txt', 'Birth registration record received and verified for the admissions file.'],
                ['id_card', 'student-identity-record.txt', 'Student identity details checked by the registrar during enrolment.'],
            ] as [$type, $name, $description]) {
                $path = 'student-documents/sample/'.$student->id.'-'.$name;
                $contents = "IGK International School - Admissions File\n"
                    ."Student: {$student->user->name}\n"
                    ."Admission number: {$student->admission_no}\n"
                    ."Record: {$description}\n"
                    ."No government identity numbers or personal document images are included in this training dataset.\n";
                if (! Storage::disk('local')->put($path, $contents)) {
                    throw new \RuntimeException("Unable to create the student admissions record at {$path}.");
                }
                StudentDocument::create([
                    'student_id' => $student->id, 'type' => $type, 'original_name' => $name, 'path' => $path,
                    'mime' => 'text/plain', 'size' => Storage::disk('local')->size($path), 'uploaded_by' => $admin->id,
                ]);
            }
        }
    }
}
