<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $fillable = [ 'label', 'name', ];

    public function recipe()
    {
        return $this->belongsToMany(Recipe::class);
    }
}
