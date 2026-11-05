<?php

return [
    'attendance_edit_window_hours' => 48,
    'receipt_prefix' => 'RCP',
    'invoice_prefix' => 'INV',
    'admission_number_pattern' => '/^\d{4}-[A-Z]{2,6}-\d{4}$/',
    'document_max_kb' => 5120,
    'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    'document_types' => ['birth_cert', 'prior_transcript', 'photo', 'national_id', 'other'],
    'demo_emails' => [
        'superadmin@school.test',
        'admin@school.test',
        'registrar@school.test',
        'teacher@school.test',
        'accountant@school.test',
        'student@school.test',
    ],
];
