<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class foodActivity extends Model
{
    use HasFactory;
    protected $table = "foodactivity";

    protected $fillable = ['unit','calPerUnit','noOfUnits','petA_id',"food"];
}
