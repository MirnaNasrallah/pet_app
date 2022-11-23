<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Symptom extends Model
{
    use HasFactory, Notifiable;
    protected $table = "symptoms";
    protected $fillable = [
        'name',
        'severity',
        'pet_id',
        'user_id',


    ];
}
