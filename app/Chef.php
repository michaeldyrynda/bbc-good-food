<?php

namespace App;

use Iatstuti\Database\Support\NullableFields;
use Illuminate\Database\Eloquent\Model;

class Chef extends Model
{
    use NullableFields;

    protected $fillable = [ 'name', 'image', ];

    protected $nullable = [ 'image', ];

    public function shows()
    {
        return $this->belongsToMany(Show::class);
    }


    public function recipes()
    {
        return $this->belongsToMany(Recipe::class);
    }
}
