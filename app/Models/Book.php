<?php

namespace App\Models;

use App\Enums\BookProcessingStatusEnum;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $guarded = ['id'];

    public function casts()
    {
        return [
            'processing_status' => BookProcessingStatusEnum::class,
        ];
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }
}
