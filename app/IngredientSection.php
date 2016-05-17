<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IngredientSection extends Model
{
    protected $fillable = [ 'recipe_id', 'label', 'sort_order', ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }


    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }
}
