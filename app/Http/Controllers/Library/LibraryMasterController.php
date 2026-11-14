<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibraryAuthor;
use App\Models\LibraryCategory;
use App\Models\LibraryCopy;
use App\Models\LibraryFine;
use App\Models\LibraryLoan;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LibraryMasterController extends Controller
{
    // ---------- Authors ----------
    public function authors(): View
    {
        return view('Library.LibraryMasterIndex', [
            'title' => 'Authors', 'singular' => 'Author', 'slug' => 'authors',
            'rows' => LibraryAuthor::withCount('books')->orderBy('name')->paginate(15),
        ]);
    }

    public function authorStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z\s.\'-]+\z/', 'unique:library_authors,name']]);
        $model = LibraryAuthor::create($data);
        AuditLogger::log('created_library_author', $model, null, $model->toArray());

        return back()->with('success', 'Author added.');
    }

    public function authorUpdate(Request $request, LibraryAuthor $author): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'regex:/\A[A-Za-z\s.\'-]+\z/', Rule::unique('library_authors', 'name')->ignore($author->id)]]);
        $before = $author->toArray();
        $author->update($data);
        AuditLogger::log('updated_library_author', $author, $before, $author->toArray());

        return back()->with('success', 'Author updated.');
    }

    public function authorDestroy(LibraryAuthor $author): RedirectResponse
    {
        if ($author->books()->withTrashed()->exists()) {
            return back()->withErrors(['name' => 'This author has books and cannot be deleted.']);
        }
        $before = $author->toArray();
        $author->delete();
        AuditLogger::log('deleted_library_author', $author, $before, null);

        return back()->with('success', 'Author deleted.');
    }

    // ---------- Categories ----------
    public function categories(): View
    {
        return view('Library.LibraryMasterIndex', [
            'title' => 'Categories', 'singular' => 'Category', 'slug' => 'categories',
            'rows' => LibraryCategory::withCount('books')->orderBy('name')->paginate(15),
        ]);
    }

    public function categoryStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'regex:/\A[A-Za-z][A-Za-z0-9\s\-:()\'&]*\z/', 'unique:library_categories,name']]);
        $model = LibraryCategory::create($data);
        AuditLogger::log('created_library_category', $model, null, $model->toArray());

        return back()->with('success', 'Category added.');
    }

    public function categoryUpdate(Request $request, LibraryCategory $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'regex:/\A[A-Za-z][A-Za-z0-9\s\-:()\'&]*\z/', Rule::unique('library_categories', 'name')->ignore($category->id)]]);
        $before = $category->toArray();
        $category->update($data);
        AuditLogger::log('updated_library_category', $category, $before, $category->toArray());

        return back()->with('success', 'Category updated.');
    }

    public function categoryDestroy(LibraryCategory $category): RedirectResponse
    {
        if ($category->books()->withTrashed()->exists()) {
            return back()->withErrors(['name' => 'This category has books and cannot be deleted.']);
        }
        $before = $category->toArray();
        $category->delete();
        AuditLogger::log('deleted_library_category', $category, $before, null);

        return back()->with('success', 'Category deleted.');
    }

    // ---------- Copies ----------
    public function copies(Request $request): View
    {
        $copies = LibraryCopy::with('book')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('book_id')->orderBy('copy_number')->paginate(20)->withQueryString();

        return view('Library.LibraryCopiesIndex', compact('copies'));
    }

    public function copyUpdate(Request $request, LibraryCopy $copy): RedirectResponse
    {
        $data = $request->validate([
            'condition' => ['required', Rule::in(['new', 'good', 'fair', 'poor', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'borrowed', 'lost', 'withdrawn'])],
        ]);
        if ($copy->status === 'borrowed' && $data['status'] !== 'borrowed' && $copy->loans()->whereNull('returned_at')->exists()) {
            return back()->withErrors(['status' => 'Return the active loan before changing this copy\'s status.']);
        }
        $before = $copy->toArray();
        $copy->update($data);
        AuditLogger::log('updated_library_copy', $copy, $before, $copy->toArray());

        return back()->with('success', 'Copy updated.');
    }

    // ---------- Loans ----------
    public function loans(Request $request): View
    {
        $loans = LibraryLoan::with(['copy.book', 'student.user', 'fine'])
            ->whereHas('copy')
            ->whereHas('student')
            ->when($request->input('state') === 'active', fn ($q) => $q->whereNull('returned_at'))
            ->when($request->input('state') === 'overdue', fn ($q) => $q->whereNull('returned_at')->whereDate('due_at', '<', today()))
            ->when($request->input('state') === 'returned', fn ($q) => $q->whereNotNull('returned_at'))
            ->orderByDesc('issued_at')->paginate(20)->withQueryString();

        return view('Library.LibraryLoansIndex', compact('loans'));
    }

    public function loanUpdate(Request $request, LibraryLoan $loan): RedirectResponse
    {
        $data = $request->validate(['due_at' => ['required', 'date']]);
        if ($loan->returned_at) {
            return back()->withErrors(['due_at' => 'A returned loan cannot be changed.']);
        }
        $before = $loan->toArray();
        $loan->update($data);
        AuditLogger::log('updated_library_loan', $loan, $before, $loan->toArray());

        return back()->with('success', 'Due date updated.');
    }

    public function loanDestroy(LibraryLoan $loan): RedirectResponse
    {
        if (! $loan->returned_at) {
            return back()->withErrors(['loan' => 'Return the book before deleting its loan record.']);
        }
        if ($loan->fine && ! $loan->fine->paid_at) {
            return back()->withErrors(['loan' => 'Collect or delete the unpaid fine first.']);
        }
        $before = $loan->toArray();
        $loan->fine?->delete();
        $loan->delete();
        AuditLogger::log('deleted_library_loan', $loan, $before, null);

        return back()->with('success', 'Loan record deleted.');
    }

    // ---------- Fines ----------
    public function fines(): View
    {
        $fines = LibraryFine::with(['loan.copy.book', 'loan.student.user'])
            ->whereHas('loan.copy')
            ->whereHas('loan.student')
            ->orderByDesc('id')
            ->paginate(20);

        return view('Library.LibraryFinesIndex', compact('fines'));
    }

    public function fineUpdate(Request $request, LibraryFine $fine): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000', 'decimal:0,2'],
            'reason' => ['required', 'string', 'max:160'],
        ]);
        if ($fine->paid_at) {
            return back()->withErrors(['amount' => 'A paid fine cannot be changed.']);
        }
        $before = $fine->toArray();
        $fine->update($data);
        AuditLogger::log('updated_library_fine', $fine, $before, $fine->toArray());

        return back()->with('success', 'Fine updated.');
    }

    public function fineDestroy(LibraryFine $fine): RedirectResponse
    {
        if ($fine->paid_at) {
            return back()->withErrors(['amount' => 'A paid fine cannot be deleted.']);
        }
        $before = $fine->toArray();
        $fine->delete();
        AuditLogger::log('deleted_library_fine', $fine, $before, null);

        return back()->with('success', 'Fine waived (deleted).');
    }
}
