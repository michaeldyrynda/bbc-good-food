<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Method extends Model
{
    protected $fillable = [ 'body', 'sort_order', ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class)->orderBy('sort_order');
    }
}
