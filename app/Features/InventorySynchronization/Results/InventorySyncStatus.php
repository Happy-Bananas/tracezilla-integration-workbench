<?php

namespace App\Features\InventorySynchronization\Results;

enum InventorySyncStatus: string
{
    case Updated = 'updated';
    case WouldUpdate = 'would_update';
    case Unchanged = 'unchanged';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
