<?php

return [
    'attendance_edit_window_hours' => 48,
    'receipt_prefix' => 'RCP',
    'invoice_prefix' => 'INV',
    'admission_number_pattern' => '/^\d{4}-[A-Z]{2,6}-\d{4}$/',
    'document_max_kb' => 5120,
    'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    'document_types' => ['birth_cert', 'prior_transcript', 'photo', 'national_id', 'other'],
    'library_daily_fine' => '50.00',
    'local_demo_two_factor_setup_code' => env('LOCAL_DEMO_TWO_FACTOR_SETUP_CODE', '434649'),
    'demo_emails' => [
        'superadmin@school.test',
        'admin@school.test',
        'registrar@school.test',
        'teacher@school.test',
        'accountant@school.test',
        'student@school.test',
    ],
];
