<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chef extends Model
{
    protected $fillable = [ 'name', 'image' ];

    public function shows()
    {
        return $this->belongsToMany(Show::class);
    }


    public function recipes()
    {
        return $this->belongsToMany(Recipe::class);
    }
}
