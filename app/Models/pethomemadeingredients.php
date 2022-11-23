<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pethomemadeingredients extends Model
{
    use HasFactory;
    protected $table = "pethomemadeingredients";

    protected $fillable = ['PetI_id','homeIngredient_id','optionalI_id'];
}
