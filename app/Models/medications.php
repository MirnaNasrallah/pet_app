<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class medications extends Model
{
    use HasFactory;
    protected $table = "medications";

    protected $fillable = ['medicationName','frequency','petId'];
}
