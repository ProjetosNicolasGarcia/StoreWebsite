<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    // Substituímos o $guarded = [] por este bloco explicito:
    protected $fillable = [
        'image_url',
        'title',
        'description',
        'link_url',
        'position',
        'is_active',
        'location', 
    ];
}