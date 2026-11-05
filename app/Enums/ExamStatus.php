<?php

namespace App\Enums;

enum ExamStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Ongoing = 'ongoing';
    case Marking = 'marking';
    case Published = 'published';
}
