<?php

namespace App\Enums;

enum EventTaskStatus: string
{
    case Todo = 'TODO';
    case InProgress = 'IN_PROGRESS';
    case Blocked = 'BLOCKED';
    case Done = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'Belum dikerjakan',
            self::InProgress => 'Sedang dikerjakan',
            self::Blocked => 'Terhambat',
            self::Done => 'Selesai',
        };
    }
}
