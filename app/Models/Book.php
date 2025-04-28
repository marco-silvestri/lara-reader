<?php

namespace App\Models;
use Smalot\Epub\Epub;

use App\Enums\ProcessingStatusEnum;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $guarded = ['id'];

    public function casts()
    {
        return [
            'processing_status' => ProcessingStatusEnum::class,
        ];
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }
}
