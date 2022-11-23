<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pet extends Model
{
    use HasFactory;
    protected $table = "petinfo";

     protected $fillable = ['PetName','specie','birthday','PetYears','PetMonths','weight','gender','spayedOr','user_id','image_path','BodyConditionScore','worklevel','watchingweight','losingweight'];
}
