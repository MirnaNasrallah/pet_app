<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class calculations extends Model
{
    use HasFactory;
    protected $table = "calculations";

    protected $fillable = ['der','rer','startcbd','petId','maxcbd'];
}
