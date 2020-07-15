<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalUsersFields extends Model
{
    protected $fillable = [
        'type', 'required',  'validation_rules
        '
    ];
    protected $casts = [
        'validation_rules' => 'array',
    ];
}
