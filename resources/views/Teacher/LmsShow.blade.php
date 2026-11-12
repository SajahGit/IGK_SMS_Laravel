@extends('Layouts.LayoutApp')
@section('title', $course->title)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('teacher.lms.index') }}" class="small">Learning management</a>
        <h3 class="mb-0">{{ $course->title }}</h3>
        <div class="text-muted">{{ $course->classSubject->schoolClass->full_name }} · {{ $course->classSubject->subject->name }}</div>
    </div>
</div>
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Modules and past papers (PDF)</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('teacher.lms.materials.store', $course) }}" enctype="multipart/form-data" class="row g-3 border rounded p-3 mb-4">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="material_type">Material type</label>
                <select class="form-select" id="material_type" name="type" required>
                    <option value="module">Module</option>
                    <option value="past_paper">Past paper</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="material_title">Title</label>
                <input class="form-control" id="material_title" name="title" maxlength="160" required>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="material_file">PDF file (max 10 MB)</label>
                <input class="form-control" id="material_file" type="file" name="file" accept=".pdf,application/pdf" required>
            </div>
            <div class="col-md-9">
                <label class="form-label" for="material_description">Description</label>
                <input class="form-control" id="material_description" name="description" maxlength="2000">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="form-check mb-2"><input class="form-check-input" id="material_published" type="checkbox" name="published" value="1"><label class="form-check-label" for="material_published">Publish</label></div>
            </div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Upload PDF</button></div>
        </form>

        @forelse ($course->materials as $material)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border rounded p-3 mb-2">
                <div>
                    <span class="badge {{ $material->type === 'past_paper' ? 'bg-info' : 'bg-primary' }}">{{ $material->type === 'past_paper' ? 'Past paper' : 'Module' }}</span>
                    <strong class="ms-2">{{ $material->title }}</strong>
                    <span class="badge {{ $material->published_at ? 'bg-success' : 'bg-secondary' }} ms-1">{{ $material->published_at ? 'Published' : 'Draft' }}</span>
                    @if ($material->description)<div class="small text-muted mt-1">{{ $material->description }}</div>@endif
                    <div class="small text-muted">{{ $material->original_name }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('teacher.lms.materials.download', [$course, $material]) }}">Download</a>
                    <form method="POST" action="{{ route('teacher.lms.materials.destroy', [$course, $material]) }}" onsubmit="return confirm('Delete this PDF material?')">
                        @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No PDF modules or past papers uploaded yet.</p>
        @endforelse
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Course details</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('teacher.lms.courses.update', $course) }}" class="row g-3">
            @csrf @method('PUT')
            <div class="col-md-4"><label class="form-label" for="course_title">Title</label><input class="form-control" id="course_title" name="title" value="{{ $course->title }}" maxlength="160" required></div>
            <div class="col-md-6"><label class="form-label" for="course_description">Description</label><input class="form-control" id="course_description" name="description" value="{{ $course->description }}" maxlength="2000"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Save</button></div>
        </form>
        <form method="POST" action="{{ route('teacher.lms.courses.destroy', $course) }}" class="mt-3" onsubmit="return confirm('Delete this course and its lessons and assignments? Courses with submissions cannot be deleted.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger btn-sm">Delete course</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Lessons</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('teacher.lms.lessons.store', $course) }}" class="border rounded p-3 mb-4">
                    @csrf
                    <h5 class="h6">Add lesson</h5>
                    <label class="form-label" for="lesson_title">Title</label>
                    <input class="form-control mb-2" id="lesson_title" name="title" maxlength="160" required>
                    <label class="form-label" for="lesson_content">Lesson content</label>
                    <textarea class="form-control mb-2" id="lesson_content" name="content" rows="4" maxlength="50000" required></textarea>
                    <div class="row align-items-end">
                        <div class="col-4"><label class="form-label" for="lesson_position">Order</label><input class="form-control" id="lesson_position" type="number" name="position" min="1" value="{{ $course->lessons->count() + 1 }}" required></div>
                        <div class="col-4 form-check"><input class="form-check-input" id="lesson_published" type="checkbox" name="published" value="1"><label class="form-check-label" for="lesson_published">Published</label></div>
                        <div class="col-4"><button class="btn btn-primary w-100">Add lesson</button></div>
                    </div>
                </form>

                @forelse ($course->lessons as $lesson)
                    <div class="border rounded p-3 mb-3">
                        <form method="POST" action="{{ route('teacher.lms.lessons.update', [$course, $lesson]) }}">
                            @csrf @method('PUT')
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Lesson {{ $lesson->position }}</strong>
                                <span class="badge {{ $lesson->published_at ? 'bg-success' : 'bg-secondary' }}">{{ $lesson->published_at ? 'Published' : 'Draft' }}</span>
                            </div>
                            <label class="form-label" for="lesson_title_{{ $lesson->id }}">Title</label>
                            <input class="form-control mb-2" id="lesson_title_{{ $lesson->id }}" name="title" value="{{ $lesson->title }}" maxlength="160" required>
                            <label class="form-label" for="lesson_content_{{ $lesson->id }}">Content</label>
                            <textarea class="form-control mb-2" id="lesson_content_{{ $lesson->id }}" name="content" rows="4" maxlength="50000" required>{{ $lesson->content }}</textarea>
                            <div class="d-flex gap-3 align-items-center">
                                <input class="form-control" type="number" name="position" min="1" value="{{ $lesson->position }}" required aria-label="Lesson order">
                                <div class="form-check text-nowrap"><input class="form-check-input" id="lesson_published_{{ $lesson->id }}" type="checkbox" name="published" value="1" @checked($lesson->published_at)><label class="form-check-label" for="lesson_published_{{ $lesson->id }}">Published</label></div>
                                <button class="btn btn-outline-primary btn-sm">Save</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('teacher.lms.lessons.destroy', [$course, $lesson]) }}" class="mt-2" onsubmit="return confirm('Delete this lesson?')">
                            @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete lesson</button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted mb-0">No lessons have been added.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Assignments</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('teacher.lms.assignments.store', $course) }}" class="border rounded p-3 mb-4">
                    @csrf
                    <h5 class="h6">Add assignment</h5>
                    <label class="form-label" for="assignment_title">Title</label>
                    <input class="form-control mb-2" id="assignment_title" name="title" maxlength="160" required>
                    <label class="form-label" for="assignment_instructions">Instructions</label>
                    <textarea class="form-control mb-2" id="assignment_instructions" name="instructions" rows="3" maxlength="10000" required></textarea>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5"><label class="form-label" for="due_at">Due date and time</label><input class="form-control" id="due_at" type="datetime-local" name="due_at"></div>
                        <div class="col-md-3"><label class="form-label" for="max_points">Max points</label><input class="form-control" id="max_points" type="number" name="max_points" min="0.01" max="100000" step="0.01" required></div>
                        <div class="col-md-2 form-check"><input class="form-check-input" id="assignment_published" type="checkbox" name="published" value="1"><label class="form-check-label" for="assignment_published">Published</label></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
                    </div>
                </form>

                @forelse ($course->assignments as $assignment)
                    <div class="border rounded p-3 mb-3">
                        <form method="POST" action="{{ route('teacher.lms.assignments.update', [$course, $assignment]) }}">
                            @csrf @method('PUT')
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ $assignment->title }}</strong>
                                <span class="badge {{ $assignment->published_at ? 'bg-success' : 'bg-secondary' }}">{{ $assignment->published_at ? 'Published' : 'Draft' }}</span>
                            </div>
                            <label class="form-label" for="assignment_title_{{ $assignment->id }}">Title</label>
                            <input class="form-control mb-2" id="assignment_title_{{ $assignment->id }}" name="title" value="{{ $assignment->title }}" maxlength="160" required>
                            <label class="form-label" for="assignment_instructions_{{ $assignment->id }}">Instructions</label>
                            <textarea class="form-control mb-2" id="assignment_instructions_{{ $assignment->id }}" name="instructions" rows="3" maxlength="10000" required>{{ $assignment->instructions }}</textarea>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5"><label class="form-label" for="assignment_due_{{ $assignment->id }}">Due date</label><input class="form-control" id="assignment_due_{{ $assignment->id }}" type="datetime-local" name="due_at" value="{{ $assignment->due_at?->format('Y-m-d\TH:i') }}"></div>
                                <div class="col-md-3"><label class="form-label" for="assignment_points_{{ $assignment->id }}">Max points</label><input class="form-control" id="assignment_points_{{ $assignment->id }}" type="number" name="max_points" min="0.01" max="100000" step="0.01" value="{{ $assignment->max_points }}" required></div>
                                <div class="col-md-2 form-check"><input class="form-check-input" id="assignment_published_{{ $assignment->id }}" type="checkbox" name="published" value="1" @checked($assignment->published_at)><label class="form-check-label" for="assignment_published_{{ $assignment->id }}">Published</label></div>
                                <div class="col-md-2"><button class="btn btn-outline-primary w-100">Save</button></div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('teacher.lms.assignments.destroy', [$course, $assignment]) }}" class="my-2" onsubmit="return confirm('Delete this assignment? Assignments with submissions cannot be deleted.')">
                            @csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete assignment</button>
                        </form>
                        <h5 class="h6 mt-3">Student submissions ({{ $assignment->submissions->count() }})</h5>
                        @forelse ($assignment->submissions as $submission)
                            <form method="POST" action="{{ route('teacher.lms.submissions.grade', [$course, $assignment, $submission]) }}" class="border-top pt-3 mt-2">
                                @csrf @method('PUT')
                                <div class="fw-semibold">{{ $submission->student->user->name }} · {{ $submission->submitted_at->format('d M Y H:i') }}</div>
                                <div class="border rounded p-2 my-2" style="white-space: pre-wrap">{{ $submission->body }}</div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3"><label class="form-label" for="score_{{ $submission->id }}">Score / {{ $assignment->max_points }}</label><input class="form-control" id="score_{{ $submission->id }}" type="number" name="score" min="0" max="{{ $assignment->max_points }}" step="0.01" value="{{ $submission->score }}" required></div>
                                    <div class="col-md-7"><label class="form-label" for="feedback_{{ $submission->id }}">Feedback</label><input class="form-control" id="feedback_{{ $submission->id }}" name="feedback" value="{{ $submission->feedback }}" maxlength="5000"></div>
                                    <div class="col-md-2"><button class="btn btn-success w-100">Grade</button></div>
                                </div>
                            </form>
                        @empty
                            <p class="small text-muted">No submissions yet.</p>
                        @endforelse
                    </div>
                @empty
                    <p class="text-muted mb-0">No assignments have been added.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
