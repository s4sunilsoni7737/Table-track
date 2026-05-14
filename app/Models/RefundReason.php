<?php

namespace App\Models;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class RefundReason extends BaseModel
{
    use HasBranch, HasFactory;

    protected $guarded = ['id'];
}

