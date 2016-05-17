<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [ 'name', 'image_url', 'description', 'fingerprint', 'source_url', ];

    protected $nullable = [ 'image_url', 'description', ];

    public function chef()
    {
        return $this->belongsToMany(Chef::class);
    }


    public function show()
    {
        return $this->belongsToMany(Show::class);
    }


    public function method()
    {
        return $this->hasMany(Method::class)->orderBy('sort_order');
    }


    public function ingredient_sections()
    {
        return $this->hasMany(IngredientSection::class);
    }


    public function ingredients()
    {
        return $this->hasManyThrough(Ingredient::class, IngredientSection::class);
    }


    public function metadata()
    {
        return $this->belongsToMany(Metadata::class)->withPivot('value');
    }
}
