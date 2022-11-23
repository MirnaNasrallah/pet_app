<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dailyexcercise extends Model
{
    use HasFactory;
    protected $table = "dailyexcercise";

    protected $fillable = ['exerciseName','hours','mins','petE_id','intensity'];
}
