<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\InventoryItem;
use App\Models\LibraryAuthor;
use App\Models\LibraryBook;
use App\Models\LibraryCategory;
use App\Models\LibraryCopy;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Spatie\Permission\Models\Role;

class BulkCsvImportService
{
    private const MAX_ROWS = 5000;

    private const REQUIRED_HEADERS = [
        'students' => ['name', 'email', 'guardian_id', 'class_id'],
        'staff' => ['name', 'email'],
        'subjects' => ['name', 'code'],
        'guardians' => ['name', 'email'],
        'inventory' => ['sku', 'name', 'category', 'unit'],
        'library_books' => ['title', 'author', 'category'],
    ];

    public function inspect(string $entity, UploadedFile $file): array
    {
        if (! isset(self::REQUIRED_HEADERS[$entity])) {
            throw new \InvalidArgumentException('Unsupported CSV import type.');
        }

        $path = $file->getRealPath();
        if ($path === false) {
            throw new RuntimeException('The uploaded CSV file could not be opened.');
        }

        return $this->inspectPath($entity, $path);
    }

    /** Validate a CSV already on disk (used by the queued import job and the import:csv command). */
    public function inspectPath(string $entity, string $path): array
    {
        if (! isset(self::REQUIRED_HEADERS[$entity])) {
            throw new \InvalidArgumentException('Unsupported CSV import type.');
        }
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The CSV file could not be opened.');
        }

        $stream = fopen($path, 'r');
        if ($stream === false) {
            throw new RuntimeException('The uploaded CSV file could not be opened.');
        }

        try {
            $rawHeaders = fgetcsv($stream, null, ',', '"', '\\');
            if ($rawHeaders === false) {
                return $this->result([], ['The CSV file is empty.']);
            }

            $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rawHeaders);
            $missing = array_values(array_diff(self::REQUIRED_HEADERS[$entity], $headers));
            if ($missing !== []) {
                return $this->result([], ['Missing required column(s): '.implode(', ', $missing).'.']);
            }

            $rows = [];
            $rowNumber = 1;
            while (($values = fgetcsv($stream, null, ',', '"', '\\')) !== false) {
                $rowNumber++;
                if ($this->isBlankRow($values)) {
                    continue;
                }
                if (count($rows) >= self::MAX_ROWS) {
                    return $this->result($rows, ['The CSV exceeds the maximum of '.self::MAX_ROWS.' data rows.']);
                }

                $data = [];
                foreach ($headers as $index => $header) {
                    if ($header !== '') {
                        $data[$header] = trim((string) ($values[$index] ?? ''));
                    }
                }
                $rows[] = ['line' => $rowNumber, 'data' => $data, 'errors' => []];
            }
        } finally {
            fclose($stream);
        }

        return $this->validateRows($entity, $rows);
    }

    public function import(string $entity, array $rows): int
    {
        return DB::transaction(function () use ($entity, $rows) {
            foreach ($rows as $row) {
                $data = $row['data'];
                if ($entity === 'subjects') {
                    $subject = Subject::create([
                        'name' => trim($data['name']),
                        'code' => strtoupper(trim($data['code'])),
                        'credit_hours' => ($data['credit_hours'] ?? '') ?: null,
                    ]);
                    AuditLogger::log('imported_subject', $subject, null, $subject->toArray());

                    continue;
                }

                if ($entity === 'students') {
                    EnrollmentService::enrollStudent([
                        'name' => trim($data['name']),
                        'email' => Str::lower(trim($data['email'])),
                        'password' => Str::random(64),
                        'class_id' => (int) $data['class_id'],
                        'program_id' => ($data['program_id'] ?? '') ?: null,
                        'academic_year_id' => ($data['academic_year_id'] ?? '') ?: null,
                        'guardian_id' => (int) $data['guardian_id'],
                        'primary_guardian_id' => (int) $data['guardian_id'],
                        'guardian_ids' => [(int) $data['guardian_id']],
                        'admission_no' => ($data['admission_no'] ?? '') ?: null,
                        'admission_date' => ($data['admission_date'] ?? '') ?: null,
                        'dob' => ($data['dob'] ?? '') ?: null,
                        'gender' => ($data['gender'] ?? '') ?: null,
                        'national_id' => ($data['national_id'] ?? '') ?: null,
                        'phone' => ($data['phone'] ?? '') ?: null,
                        'status' => ($data['status'] ?? '') ?: 'active',
                    ]);

                    continue;
                }

                if ($entity === 'guardians') {
                    $guardianUser = User::create([
                        'name' => trim($data['name']),
                        'email' => Str::lower(trim($data['email'])),
                        'password' => Str::random(64),
                        'role' => 'parent',
                        'must_change_password' => true,
                    ]);
                    $guardian = Guardian::create([
                        'user_id' => $guardianUser->id,
                        'phone' => ($data['phone'] ?? '') ?: null,
                    ]);
                    AuditLogger::log('imported_guardian', $guardian, null, $guardian->toArray());

                    continue;
                }

                if ($entity === 'inventory') {
                    $item = InventoryItem::create([
                        'sku' => Str::upper(trim($data['sku'])),
                        'name' => trim($data['name']),
                        'category' => trim($data['category']),
                        'unit' => trim($data['unit']),
                        'quantity_on_hand' => (int) (($data['quantity_on_hand'] ?? '') ?: 0),
                        'reorder_level' => (int) (($data['reorder_level'] ?? '') ?: 0),
                        'description' => ($data['description'] ?? '') ?: null,
                        'is_active' => true,
                    ]);
                    AuditLogger::log('imported_inventory_item', $item, null, $item->toArray());

                    continue;
                }

                if ($entity === 'library_books') {
                    $author = LibraryAuthor::firstOrCreate(['name' => trim($data['author'])]);
                    $category = LibraryCategory::firstOrCreate(['name' => trim($data['category'])]);
                    $book = LibraryBook::create([
                        'author_id' => $author->id,
                        'category_id' => $category->id,
                        'title' => trim($data['title']),
                        'isbn' => ($data['isbn'] ?? '') ?: null,
                        'publication_year' => ($data['publication_year'] ?? '') ?: null,
                    ]);
                    $copies = max(1, (int) (($data['copies'] ?? '') ?: 1));
                    for ($number = 1; $number <= $copies; $number++) {
                        LibraryCopy::create([
                            'book_id' => $book->id,
                            'copy_number' => $number,
                            'accession_no' => sprintf('LIB-%06d-%03d', $book->id, $number),
                            'condition' => 'good',
                            'status' => 'available',
                        ]);
                    }
                    AuditLogger::log('imported_library_book', $book, null, $book->toArray());

                    continue;
                }

                $user = User::create([
                    'name' => trim($data['name']),
                    'email' => Str::lower(trim($data['email'])),
                    'password' => Str::random(64),
                    'role' => 'teacher',
                    'must_change_password' => true,
                ]);
                $roleName = 'Teacher';
                if (Role::where('name', $roleName)->where('guard_name', 'web')->exists()) {
                    $user->syncRoles([$roleName]);
                }

                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'phone' => ($data['phone'] ?? '') ?: null,
                    'department_id' => ($data['department_id'] ?? '') ?: null,
                    'status' => ($data['status'] ?? '') ?: 'active',
                ]);
                AuditLogger::log('imported_staff', $teacher, null, $teacher->toArray());
            }

            return count($rows);
        });
    }

    private function validateRows(string $entity, array $rows): array
    {
        $seen = [];
        $seenAdmissionNumbers = [];
        $seenNationalIds = [];
        $classCounts = [];
        $validRows = [];
        foreach ($rows as $row) {
            $data = $row['data'];
            $rules = match ($entity) {
                'students' => [
                    'name' => ['required', 'string', 'max:100'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'guardian_id' => ['required', 'integer', 'exists:guardians,id'],
                    'class_id' => ['required', 'integer', 'exists:classes,id'],
                    'program_id' => ['nullable', 'integer', 'exists:programs,id'],
                    'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
                    'admission_no' => ['nullable', 'regex:/^\d{4}-[A-Z]{2,6}-\d{4}$/', 'unique:students,admission_no'],
                    'admission_date' => ['nullable', 'date', 'before_or_equal:today'],
                    'dob' => ['nullable', 'date', 'before:today'],
                    'gender' => ['nullable', 'string', 'max:20'],
                    'national_id' => ['nullable', 'string', 'max:30', 'unique:students,national_id'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'status' => ['nullable', Rule::in(['active', 'suspended', 'graduated', 'withdrawn'])],
                ],
                'staff' => [
                    'name' => ['required', 'string', 'max:100'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                    'status' => ['nullable', Rule::in(['active', 'inactive', 'on_leave'])],
                ],
                'guardians' => [
                    'name' => ['required', 'string', 'max:100'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'phone' => ['nullable', 'string', 'max:20'],
                ],
                'inventory' => [
                    'sku' => ['required', 'string', 'max:50', 'unique:inventory_items,sku'],
                    'name' => ['required', 'string', 'max:150'],
                    'category' => ['required', 'string', 'max:100'],
                    'unit' => ['required', 'string', 'max:30'],
                    'quantity_on_hand' => ['nullable', 'integer', 'min:0'],
                    'reorder_level' => ['nullable', 'integer', 'min:0'],
                    'description' => ['nullable', 'string', 'max:1000'],
                ],
                'library_books' => [
                    'title' => ['required', 'string', 'max:200'],
                    'author' => ['required', 'string', 'max:120'],
                    'category' => ['required', 'string', 'max:80'],
                    'isbn' => ['nullable', 'string', 'max:20', 'unique:library_books,isbn'],
                    'publication_year' => ['nullable', 'integer', 'between:1500,2100'],
                    'copies' => ['nullable', 'integer', 'between:1,50'],
                ],
                default => [
                    'name' => ['required', 'string', 'max:100'],
                    'code' => ['required', 'string', 'max:10', 'unique:subjects,code'],
                    'credit_hours' => ['nullable', 'integer', 'between:1,60'],
                ],
            };

            $validator = Validator::make($data, $rules);
            $identity = match ($entity) {
                'students', 'staff', 'guardians' => Str::lower(trim((string) ($data['email'] ?? ''))),
                'inventory' => Str::upper(trim((string) ($data['sku'] ?? ''))),
                'library_books' => Str::lower(trim((string) ($data['isbn'] ?? ''))),
                default => Str::upper(trim((string) ($data['code'] ?? ''))),
            };
            $identityField = match ($entity) {
                'subjects' => 'code',
                'inventory' => 'sku',
                'library_books' => 'isbn',
                default => 'email',
            };
            if ($identity !== '' && isset($seen[$identity])) {
                $validator->errors()->add($identityField, 'This value is duplicated in the uploaded file.');
            } elseif ($identity !== '') {
                $seen[$identity] = true;
            }
            if ($entity === 'students') {
                $admissionNumber = Str::upper(trim((string) ($data['admission_no'] ?? '')));
                if ($admissionNumber !== '' && isset($seenAdmissionNumbers[$admissionNumber])) {
                    $validator->errors()->add('admission_no', 'This value is duplicated in the uploaded file.');
                } elseif ($admissionNumber !== '') {
                    $seenAdmissionNumbers[$admissionNumber] = true;
                }

                $nationalId = Str::upper(trim((string) ($data['national_id'] ?? '')));
                if ($nationalId !== '' && isset($seenNationalIds[$nationalId])) {
                    $validator->errors()->add('national_id', 'This value is duplicated in the uploaded file.');
                } elseif ($nationalId !== '') {
                    $seenNationalIds[$nationalId] = true;
                }
            }

            if ($entity === 'students' && ! $validator->errors()->any()) {
                $schoolClass = SchoolClass::with(['program', 'academicYear'])->find((int) $data['class_id']);
                if ($schoolClass) {
                    $classCounts[$schoolClass->id] ??= $schoolClass->students()->count();
                    if ($classCounts[$schoolClass->id] >= $schoolClass->capacity) {
                        $validator->errors()->add('class_id', "Class {$schoolClass->full_name} has no remaining capacity.");
                    }

                    if (! $schoolClass->program_id && empty($data['program_id'])) {
                        $validator->errors()->add('program_id', 'The selected class must have a program.');
                    } elseif (! empty($data['program_id']) && $schoolClass->program_id && (int) $data['program_id'] !== $schoolClass->program_id) {
                        $validator->errors()->add('program_id', 'The program must match the selected class.');
                    }
                    if (! $schoolClass->academic_year_id && empty($data['academic_year_id'])) {
                        $validator->errors()->add('academic_year_id', 'The selected class must have an academic year.');
                    } elseif (! empty($data['academic_year_id']) && $schoolClass->academic_year_id && (int) $data['academic_year_id'] !== $schoolClass->academic_year_id) {
                        $validator->errors()->add('academic_year_id', 'The academic year must match the selected class.');
                    }
                    if (! $validator->errors()->any()) {
                        $classCounts[$schoolClass->id]++;
                    }
                }
            }

            $row['errors'] = $validator->errors()->all();
            if ($row['errors'] === []) {
                $validRows[] = $row;
            }
            $rows[$row['line'] - 2] = $row;
        }

        return $this->result(array_values($rows), [], $validRows);
    }

    private function result(array $rows, array $fileErrors, ?array $validRows = null): array
    {
        $validRows ??= array_values(array_filter($rows, fn ($row) => $row['errors'] === []));
        $invalidCount = count($rows) - count($validRows);

        return [
            'rows' => $rows,
            'valid_rows' => $validRows,
            'file_errors' => $fileErrors,
            'valid_count' => count($validRows),
            'invalid_count' => $invalidCount,
            'can_import' => $fileErrors === [] && $rows !== [] && $invalidCount === 0,
        ];
    }

    private function normalizeHeader(string $header): string
    {
        return Str::snake(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? ''));
    }

    private function isBlankRow(array $values): bool
    {
        return collect($values)->every(fn ($value) => trim((string) $value) === '');
    }
}
