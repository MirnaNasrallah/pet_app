<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCheckIn extends Model
{
    use HasFactory;
    protected $table = "daily_check_in";
    protected $fillable = ['pet_id','user_id','exercise_min','exercise_level','energy_level','cough_or_sneeze','vomit_or_diarrhea','limping_or_soreness','scratching_or_licking','seizures','notes'];
}
