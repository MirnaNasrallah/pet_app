<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetHealthData extends Model
{
    use HasFactory;
    protected $table = "pethealthcondition";

    protected $fillable = ['PetCondition','PetConditionState','PetH_id'];
}
