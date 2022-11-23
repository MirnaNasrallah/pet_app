<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class Message extends Model
{
    use HasFactory;
    protected $table = "messages";
    protected $fillable = [
        'body',
        'user_id',
        'pet_id',
        'symptom_id',
    ];
}
