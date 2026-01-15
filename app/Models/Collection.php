<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'image_url', 'description', 'is_active', 'featured_on_home'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product');
    }
}