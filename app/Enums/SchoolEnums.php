<?php

namespace App\Enums;

enum StudentStatus: string
{
    case APPLICANT = 'applicant';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case GRADUATED = 'graduated';
    case WITHDRAWN = 'withdrawn';
}

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case EXCUSED = 'excused';
}

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}

enum ExamStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case ONGOING = 'ongoing';
    case MARKING = 'marking';
    case PUBLISHED = 'published';
}

enum ResultStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case PUBLISHED = 'published';
    case WITHHELD = 'withheld';
}
