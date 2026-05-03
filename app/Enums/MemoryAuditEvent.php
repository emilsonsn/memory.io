<?php

namespace App\Enums;

enum MemoryAuditEvent: string
{
    case DELETED_DUE_DATE = 'memory.deleted_due_date';
}
