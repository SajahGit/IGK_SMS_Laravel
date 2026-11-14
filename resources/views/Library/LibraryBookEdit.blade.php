@extends('Layouts.LayoutApp')
@section('title', 'Edit library book')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h3 class="mb-0">Edit library book</h3><small class="text-muted">{{ $book->title }}</small></div>
    <a href="{{ route('library.dashboard') }}" class="btn btn-outline-secondary">Back to library</a>
</div>
@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><strong>Book details</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('library.books.update', $book) }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-md-6"><label class="form-label" for="title">Title</label><input id="title" name="title" value="{{ old('title', $book->title) }}" class="form-control" maxlength="120" pattern="[A-Za-z][A-Za-z0-9\s:\x28\x29\x27\x26\x2d]*" data-live-filter="letter-start-book" required></div>
            <div class="col-md-6"><label class="form-label" for="author">Author</label><input id="author" name="author" value="{{ old('author', $book->author->name) }}" class="form-control" maxlength="80" pattern="[A-Za-z\s.\x27\x2d]+" data-live-filter="author-name" required></div>
            <div class="col-md-6"><label class="form-label" for="category">Category</label><input id="category" name="category" value="{{ old('category', $book->category->name) }}" class="form-control" maxlength="120" pattern="[A-Za-z][A-Za-z0-9\s:\x28\x29\x27\x26\x2d]*" data-live-filter="letter-start-book" required></div>
            <div class="col-md-6"><label class="form-label" for="isbn">ISBN</label><input id="isbn" name="isbn" value="{{ old('isbn', $book->isbn) }}" class="form-control" minlength="10" maxlength="17" pattern="[0-9X\x2d]+" data-live-filter="isbn"></div>
            <div class="col-md-6"><label class="form-label" for="publication_year">Publication year</label><input id="publication_year" name="publication_year" value="{{ old('publication_year', $book->publication_year) }}" type="number" class="form-control" min="1800" max="2026" step="1"></div>
            <div class="col-12"><button class="btn btn-primary">Save changes</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent"><strong>Copies</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('library.books.copies.store', $book) }}" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-4"><label class="form-label" for="condition">Condition</label><select id="condition" name="condition" class="form-select"><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
            <div class="col-md-4"><button class="btn btn-outline-primary">Add copy</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Accession No.</th><th>Condition</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($book->copies as $copy)
                    <tr>
                        <td>{{ $copy->accession_no }}</td><td>{{ ucfirst($copy->condition) }}</td><td>{{ ucfirst($copy->status) }}</td>
                        <td class="text-end">
                            @if ($copy->status === 'available' && ! $copy->loans()->exists())
                                <form method="POST" action="{{ route('library.copies.destroy', $copy) }}" onsubmit="return confirm('Remove this copy?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                            @else
                                <span class="text-muted small">In circulation history</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No copies on file.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
