<?php

namespace App\Models;

use App\Models\Book;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    protected $guarded = ['id'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function shards(): HasMany
    {
        return $this->hasMany(Shard::class);
    }
}
