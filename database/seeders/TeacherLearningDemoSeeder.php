<?php

namespace Database\Seeders;

use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\LmsAssignment;
use App\Models\LmsCourse;
use App\Models\LmsCourseMaterial;
use App\Models\LmsLesson;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TeacherLearningDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Teacher learning demo data may only be seeded outside production.');
        }

        $teacherUser = User::query()->where('email', 'teacher@school.test')->first();
        if (! $teacherUser || ! $teacherUser->branch_id) {
            throw new RuntimeException('Seed the assigned demo teacher account before teacher learning data.');
        }

        $teacher = Teacher::query()->where('user_id', $teacherUser->id)->first();
        if (! $teacher) {
            throw new RuntimeException('The demo teacher account has no teacher profile.');
        }

        $offeringId = DB::table('class_subject')
            ->join('classes', 'classes.id', '=', 'class_subject.class_id')
            ->where('class_subject.teacher_id', $teacher->id)
            ->where('classes.branch_id', $teacherUser->branch_id)
            ->orderBy('class_subject.id')
            ->value('class_subject.id');
        if (! $offeringId) {
            throw new RuntimeException('The demo teacher needs a same-branch class subject for sample learning data.');
        }

        $offering = ClassSubject::with(['schoolClass', 'subject', 'term'])->findOrFail($offeringId);
        $description = 'Demo course with learning modules and past examination practice.';
        $courseTitle = $offering->subject->name === 'English'
            ? 'English Language: Reading and Writing'
            : $offering->subject->name.': Demo Learning Course';
        $course = LmsCourse::query()
            ->where('class_subject_id', $offering->id)
            ->first();
        if (! $course) {
            $course = LmsCourse::query()
                ->where('description', 'Demo course with learning modules and past examination practice.')
                ->whereHas('classSubject', fn ($query) => $query->where('teacher_id', $teacher->id))
                ->first();
            if ($course) {
                $course->update([
                    'class_subject_id' => $offering->id,
                    'title' => $courseTitle,
                ]);
            } else {
                $course = LmsCourse::create([
                    'class_subject_id' => $offering->id,
                    'title' => $courseTitle,
                    'description' => $description,
                ]);
            }
        }

        LmsLesson::firstOrCreate(
            ['course_id' => $course->id, 'position' => 1],
            [
                'title' => 'Module 1: Learning foundations',
                'content' => 'Review the attached module PDF. Identify its key ideas, explain the important terms, and support your answers with evidence from the material.',
                'published_at' => now(),
            ]
        );
        LmsAssignment::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Module 1 practice assignment'],
            [
                'instructions' => 'Complete the questions in the published module and support your answers with evidence from the material.',
                'due_at' => now()->addDays(14),
                'max_points' => 20,
                'published_at' => now(),
            ]
        );

        Exam::updateOrCreate(
            [
                'name' => 'Demo Term Assessment - '.$offering->subject->name,
                'created_by' => $teacherUser->id,
            ],
            [
                'class_id' => $offering->class_id,
                'subject_id' => $offering->subject_id,
                'term_id' => $offering->term_id,
                'type' => 'final',
                'exam_date' => today()->addDays(14),
                'max_marks' => 100,
                'weight' => 100,
            ]
        );

        $this->seedPdfMaterial(
            $course,
            $teacherUser,
            'module',
        'Module 1: Learning foundations',
        'Sample course module PDF with learning content and practice.'
        );
        $this->seedPdfMaterial(
            $course,
            $teacherUser,
            'past_paper',
        'Past Paper - Sample Practice',
        'Sample past-paper questions for this course.'
        );
    }

    private function seedPdfMaterial(
        LmsCourse $course,
        User $teacher,
        string $type,
        string $title,
        string $description
    ): void {
        $path = sprintf('lms/demo/course-%d/%s.pdf', $course->id, $type);
        $disk = Storage::disk('local');
        if (! $disk->exists($path) && ! $disk->put($path, $this->pdfContent($title))) {
            throw new RuntimeException("Unable to create the sample PDF at {$path}.");
        }

        $material = LmsCourseMaterial::firstOrNew([
            'course_id' => $course->id,
            'type' => $type,
            'file_path' => $path,
        ]);
        $material->fill([
            'title' => $title,
            'description' => $description,
            'original_name' => str_replace(' ', '-', strtolower($title)).'.pdf',
            'uploaded_by' => $teacher->id,
            'published_at' => $material->published_at ?? now(),
        ])->save();
    }

    private function pdfContent(string $title): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $title);
        $stream = 'BT /F1 16 Tf 50 740 Td ('.$text.') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".count($offsets)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".count($offsets)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF\n";
    }
}
