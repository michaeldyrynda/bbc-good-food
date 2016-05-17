<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [ 'fingerprint' , 'title' ];

    public function chef()
    {
        return $this->belongsToMany(Chef::class);
    }


    public function show()
    {
        return $this->belongsToMany(Show::class);
    }


    public function methods()
    {
        return $this->hasMany(Method::class);
    }

}
