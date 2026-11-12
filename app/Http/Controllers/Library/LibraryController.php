<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryBookRequest;
use App\Http\Requests\StoreLibraryCopyRequest;
use App\Http\Requests\StoreLibraryLoanRequest;
use App\Http\Requests\UpdateLibraryBookRequest;
use App\Models\LibraryAuthor;
use App\Models\LibraryBook;
use App\Models\LibraryCategory;
use App\Models\LibraryCopy;
use App\Models\LibraryFine;
use App\Models\LibraryLoan;
use App\Models\Student;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LibraryBook::class);

        $books = LibraryBook::query()
            ->with(['author', 'category'])
            ->withCount(['copies', 'copies as available_copies_count' => fn ($query) => $query->where('status', 'available')])
            ->when($request->filled('q'), fn ($query) => $query->where(function ($search) use ($request) {
                $search->where('title', 'like', '%'.$request->string('q').'%')
                    ->orWhere('isbn', 'like', '%'.$request->string('q').'%');
            }))
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $loans = LibraryLoan::with(['copy.book', 'student.user', 'fine'])
            ->whereNull('returned_at')
            ->whereHas('copy')
            ->whereHas('student')
            ->orderBy('due_at')
            ->paginate(10, ['*'], 'loans_page')
            ->withQueryString();

        $fines = LibraryFine::with(['loan.student.user', 'loan.copy.book'])
            ->whereNull('paid_at')
            ->whereHas('loan.copy')
            ->whereHas('loan.student')
            ->latest()
            ->limit(20)
            ->get();

        return view('Library.LibraryDashboard', [
            'books' => $books,
            'loans' => $loans,
            'fines' => $fines,
            'students' => Student::with('user')->where('status', 'active')->orderBy('id')->get(),
            'availableCopies' => LibraryCopy::with('book')->where('status', 'available')->orderBy('accession_no')->get(),
            'dailyFine' => config('school.library_daily_fine', '50.00'),
        ]);
    }

    public function storeBook(StoreLibraryBookRequest $request): RedirectResponse
    {
        $this->authorize('create', LibraryBook::class);
        $data = $request->validated();

        $book = DB::transaction(function () use ($data) {
            $author = LibraryAuthor::firstOrCreate(['name' => trim($data['author'])]);
            $category = LibraryCategory::firstOrCreate(['name' => trim($data['category'])]);

            $book = LibraryBook::create([
                'author_id' => $author->id,
                'category_id' => $category->id,
                'title' => trim($data['title']),
                'isbn' => $data['isbn'] ?? null,
                'publication_year' => $data['publication_year'] ?? null,
            ]);

            for ($number = 1; $number <= $data['copies']; $number++) {
                $book->copies()->create([
                    'copy_number' => $number,
                    'accession_no' => sprintf('LIB-%06d-%03d', $book->id, $number),
                    'condition' => 'good',
                    'status' => 'available',
                ]);
            }

            return $book;
        });

        AuditLogger::log('created_library_book', $book, null, $book->toArray());

        return back()->with('success', 'Book and copies added to the library.');
    }

    public function edit(LibraryBook $book): View
    {
        $this->authorize('update', $book);

        return view('Library.LibraryBookEdit', [
            'book' => $book->load(['author', 'category', 'copies']),
            'loanHistoryExists' => $book->copies()->whereHas('loans')->exists(),
        ]);
    }

    public function update(UpdateLibraryBookRequest $request, LibraryBook $book): RedirectResponse
    {
        $this->authorize('update', $book);
        $data = $request->validated();

        DB::transaction(function () use ($data, $book) {
            $book->update([
                'title' => trim($data['title']),
                'isbn' => $data['isbn'] ?? null,
                'publication_year' => $data['publication_year'] ?? null,
                'author_id' => LibraryAuthor::firstOrCreate(['name' => trim($data['author'])])->id,
                'category_id' => LibraryCategory::firstOrCreate(['name' => trim($data['category'])])->id,
            ]);
        });

        return redirect()->route('library.dashboard')->with('success', 'Library book updated.');
    }

    public function destroy(LibraryBook $book): RedirectResponse
    {
        $this->authorize('delete', $book);
        if ($book->copies()->whereHas('loans')->exists()) {
            throw ValidationException::withMessages([
                'book' => 'A book with circulation history cannot be deleted. Retain it in the catalogue instead.',
            ]);
        }

        DB::transaction(function () use ($book) {
            $book->copies()->delete();
            $book->delete();
        });

        return back()->with('success', 'Library book removed from the catalogue.');
    }

    public function storeCopy(StoreLibraryCopyRequest $request, LibraryBook $book): RedirectResponse
    {
        $this->authorize('create', LibraryCopy::class);
        $data = $request->validated();

        DB::transaction(function () use ($book, $data) {
            $lockedBook = LibraryBook::query()->lockForUpdate()->findOrFail($book->id);
            $number = (int) $lockedBook->copies()->withTrashed()->max('copy_number') + 1;
            $lockedBook->copies()->create([
                'copy_number' => $number,
                'accession_no' => sprintf('LIB-%06d-%03d', $lockedBook->id, $number),
                'condition' => $data['condition'],
                'status' => 'available',
            ]);
        });

        return back()->with('success', 'Library copy added.');
    }

    public function destroyCopy(LibraryCopy $copy): RedirectResponse
    {
        $this->authorize('delete', $copy);
        if ($copy->loans()->exists()) {
            throw ValidationException::withMessages([
                'copy' => 'A copy with circulation history cannot be deleted.',
            ]);
        }

        $copy->delete();

        return back()->with('success', 'Library copy removed.');
    }

    public function borrow(StoreLibraryLoanRequest $request): RedirectResponse
    {
        $this->authorize('create', LibraryLoan::class);
        $data = $request->validated();

        $loan = DB::transaction(function () use ($data) {
            $copy = LibraryCopy::query()->lockForUpdate()->findOrFail($data['copy_id']);
            if ($copy->status !== 'available') {
                throw ValidationException::withMessages(['copy_id' => 'That copy is no longer available.']);
            }
            $student = Student::query()->lockForUpdate()->findOrFail($data['student_id']);
            if (($copy->branch_id !== null || $student->branch_id !== null)
                && (int) $copy->branch_id !== (int) $student->branch_id) {
                throw ValidationException::withMessages([
                    'student_id' => 'The student and library copy must belong to the same campus.',
                ]);
            }

            $loan = LibraryLoan::create([
                'copy_id' => $copy->id,
                'student_id' => $data['student_id'],
                'issued_by' => auth()->id(),
                'issued_at' => now(),
                'due_at' => $data['due_at'],
            ]);

            $copy->update(['status' => 'borrowed']);
            AuditLogger::log('borrowed_library_copy', $loan, null, $loan->toArray());

            return $loan;
        });

        return back()->with('success', "Copy {$loan->copy->accession_no} checked out successfully.");
    }

    public function returnBook(LibraryLoan $loan): RedirectResponse
    {
        $this->authorize('update', $loan);
        DB::transaction(function () use ($loan) {
            $loan = LibraryLoan::query()->lockForUpdate()->with('copy')->findOrFail($loan->id);
            if ($loan->returned_at) {
                throw ValidationException::withMessages(['loan' => 'This copy has already been returned.']);
            }

            $loan->update(['returned_at' => now(), 'returned_by' => auth()->id()]);
            $loan->copy->update(['status' => 'available']);

            $overdueDays = $loan->due_at->startOfDay()->diffInDays(today(), false);
            if ($overdueDays > 0) {
                $amount = Money::mul((string) $overdueDays, (string) config('school.library_daily_fine', '50.00'));
                $fine = LibraryFine::create([
                    'loan_id' => $loan->id,
                    'amount' => $amount,
                    'reason' => "{$overdueDays} overdue day(s)",
                ]);
                AuditLogger::log('assessed_library_fine', $fine, null, $fine->toArray());
            }

            AuditLogger::log('returned_library_copy', $loan, ['returned_at' => null], ['returned_at' => $loan->returned_at]);
        });

        return back()->with('success', 'Book returned and copy made available.');
    }

    public function payFine(LibraryFine $fine): RedirectResponse
    {
        $this->authorize('update', $fine);
        DB::transaction(function () use ($fine) {
            $fine = LibraryFine::query()->lockForUpdate()->findOrFail($fine->id);
            if ($fine->paid_at) {
                throw ValidationException::withMessages(['fine' => 'This fine has already been paid.']);
            }

            $fine->update(['paid_at' => now(), 'collected_by' => auth()->id()]);
            AuditLogger::log('paid_library_fine', $fine, ['paid_at' => null], ['paid_at' => $fine->paid_at]);
        });

        return back()->with('success', 'Library fine payment recorded.');
    }
}
