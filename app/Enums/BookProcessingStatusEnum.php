<?php

namespace App\Enums;

enum BookProcessingStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case DONE = 'done';
    case ERROR = 'error';

    public function getValue():string
    {
        return match($this){
            $this => $this->value
        };
    }
}
