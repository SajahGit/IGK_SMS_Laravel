<?php

namespace App\Events;

use App\Models\Term;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResultPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Term $term)
    {
    }
}
