<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    protected $fillable = [ 'name' ];

    public function chefs()
    {
        return $this->belongsToMany(Chef::class);
    }


    public function recipes()
    {
        return $this->belongsToMany(Recipe::class);
    }
}
