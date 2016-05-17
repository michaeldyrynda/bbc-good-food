<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [ 'body' ];

    public function ingredientSection()
    {
        return $this->belongsTo(IngredientSection::class);
    }
}
