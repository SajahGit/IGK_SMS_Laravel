@extends('Layouts.LayoutApp')
@section('title', 'Library')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Library</h3>
        <small class="text-muted">Manage the catalogue, circulation and overdue fines.</small>
    </div>
</div>

@include('Layouts.LayoutErrors')

<div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent"><strong>Add books and copies</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('library.books.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6"><label class="form-label" for="title">Title</label><input id="title" name="title" class="form-control" maxlength="200" required></div>
                    <div class="col-md-6"><label class="form-label" for="author">Author</label><input id="author" name="author" class="form-control" maxlength="120" required></div>
                    <div class="col-md-6"><label class="form-label" for="category">Category</label><input id="category" name="category" class="form-control" maxlength="80" required></div>
                    <div class="col-md-6"><label class="form-label" for="isbn">ISBN</label><input id="isbn" name="isbn" class="form-control" maxlength="20"></div>
                    <div class="col-md-6"><label class="form-label" for="publication_year">Publication year</label><input id="publication_year" name="publication_year" type="number" class="form-control" min="1000" max="{{ now()->year + 1 }}"></div>
                    <div class="col-md-6"><label class="form-label" for="copies">Number of copies</label><input id="copies" name="copies" type="number" class="form-control" min="1" max="100" value="1" required></div>
                    <div class="col-12"><button class="btn btn-primary">Add to catalogue</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent"><strong>Check out a copy</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('library.loans.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12"><label class="form-label" for="copy_id">Available copy</label>
                        <select id="copy_id" name="copy_id" class="form-select" required>
                            <option value="">Select a copy</option>
                            @foreach ($availableCopies as $copy)
                                <option value="{{ $copy->id }}">{{ $copy->book->title }} — {{ $copy->accession_no }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7"><label class="form-label" for="student_id">Student</label>
                        <select id="student_id" name="student_id" class="form-select" required>
                            <option value="">Select a student</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->user->name }} ({{ $student->admission_no }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5"><label class="form-label" for="due_at">Due date</label><input id="due_at" name="due_at" type="date" min="{{ today()->addDay()->toDateString() }}" class="form-control" required></div>
                    <div class="col-12"><button class="btn btn-primary">Check out</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong>Catalogue</strong>
        <form method="GET" action="{{ route('library.dashboard') }}" class="d-flex gap-2">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search title or ISBN" aria-label="Search title or ISBN">
            <button class="btn btn-sm btn-outline-primary">Search</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Title</th><th>Author</th><th>Category</th><th>ISBN</th><th>Copies available</th><th></th></tr></thead>
            <tbody>
            @forelse ($books as $book)
                <tr>
                    <td>{{ $book->title }}</td><td>{{ $book->author->name }}</td><td>{{ $book->category->name }}</td><td>{{ $book->isbn ?: '—' }}</td><td>{{ $book->available_copies_count }} / {{ $book->copies_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('library.books.edit', $book) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        @if ($book->copies()->whereHas('loans')->doesntExist())
                            <form method="POST" action="{{ route('library.books.destroy', $book) }}" class="d-inline" onsubmit="return confirm('Remove this book and its copies?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No library books found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $books->links('pagination::bootstrap-5') }}</div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><strong>Books currently on loan</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Book</th><th>Copy</th><th>Student</th><th>Issued</th><th>Due</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td>{{ $loan->copy->book->title }}</td><td>{{ $loan->copy->accession_no }}</td>
                    <td>{{ $loan->student->user->name }} ({{ $loan->student->admission_no }})</td>
                    <td>{{ $loan->issued_at->format('Y-m-d') }}</td><td>{{ $loan->due_at->format('Y-m-d') }}</td>
                    <td><form method="POST" action="{{ route('library.loans.return', $loan) }}" onsubmit="return confirm('Record this book as returned?')">@csrf<button class="btn btn-sm btn-outline-primary">Return</button></form></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No books are currently on loan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $loans->links('pagination::bootstrap-5') }}</div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent"><strong>Unpaid overdue fines</strong><span class="text-muted small ms-2">Daily fine: {{ \App\Support\Money::format($dailyFine) }}</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Student</th><th>Book</th><th>Overdue</th><th>Amount</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($fines as $fine)
                <tr>
                    <td>{{ $fine->loan->student->user->name }}</td><td>{{ $fine->loan->copy->book->title }}</td>
                    <td>{{ $fine->reason }}</td><td>{{ \App\Support\Money::format($fine->amount) }}</td>
                    <td><form method="POST" action="{{ route('library.fines.pay', $fine) }}" onsubmit="return confirm('Record this fine as paid?')">@csrf<button class="btn btn-sm btn-outline-primary">Record payment</button></form></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No unpaid library fines.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
