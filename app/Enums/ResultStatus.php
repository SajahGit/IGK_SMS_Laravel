<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Published = 'published';
    case Withheld = 'withheld';
}
