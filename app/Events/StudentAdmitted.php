<?php

namespace App\Events;

use App\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentAdmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Student $student)
    {
    }
}
