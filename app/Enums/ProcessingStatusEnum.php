<?php

namespace App\Enums;

enum ProcessingStatusEnum: string
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
