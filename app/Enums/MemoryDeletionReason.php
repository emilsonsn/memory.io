<?php

namespace App\Enums;

enum MemoryDeletionReason: string
{
    case DUE_DATE_EXPIRED = 'due_date_expired';
}
