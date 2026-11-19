@extends('Layouts.LayoutApp')
@section('title', 'Create Exam')
@section('content')
<h3 class="mb-3">Create exam</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('teacher.exams.store') }}">
        @csrf
        @include('Layouts.LayoutErrors')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Exam name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Term 1 Test" required>
            </div>
            <div class="col-md-6">
                <label for="exam_class_id" class="form-label">Class</label>
                <select id="exam_class_id" class="form-select" required>
                    @forelse ($assignmentOptions->unique('class_id') as $assignment)
                        <option value="{{ $assignment['class_id'] }}" @selected($selectedClassId == $assignment['class_id'])>
                            {{ $assignment['class_name'] }}
                        </option>
                    @empty
                        <option value="" selected disabled>No class assignments found for your account.</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-6">
                <label for="exam_assignment_id" class="form-label">Subject</label>
                <select id="exam_assignment_id" name="assignment" class="form-select" required @disabled($assignmentOptions->isEmpty())>
                    <option value="">Select Subject</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Exam date</label>
                <input type="date" name="exam_date" class="form-control" value="{{ old('exam_date') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Maximum marks</label>
                <input type="number" name="max_marks" class="form-control" value="{{ old('max_marks', 100) }}" min="1" max="1000" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Create exam</button>
            <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div></div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var assignments = {{ \Illuminate\Support\Js::from($assignmentOptions) }};
        var classSelect = document.getElementById('exam_class_id');
        var assignmentSelect = document.getElementById('exam_assignment_id');
        var selectedAssignmentId = @json((string) $selectedAssignmentId);

        function updateSubjects() {
            var classId = classSelect.value;
            var classAssignments = assignments.filter(function (assignment) {
                return String(assignment.class_id) === classId;
            });
            assignmentSelect.replaceChildren(new Option('Select Subject', ''));
            classAssignments.forEach(function (assignment) {
                var label = assignment.subject_name + ' (' + assignment.term_name + ')';
                assignmentSelect.add(new Option(label, assignment.id));
            });
            assignmentSelect.disabled = classAssignments.length === 0;
            if (classAssignments.some(function (assignment) {
                return String(assignment.id) === selectedAssignmentId;
            })) {
                assignmentSelect.value = selectedAssignmentId;
            } else if (classAssignments.length > 0) {
                assignmentSelect.value = String(classAssignments[0].id);
            }
            selectedAssignmentId = '';
        }

        classSelect.addEventListener('change', updateSubjects);
        updateSubjects();
    });
</script>
@endsection
