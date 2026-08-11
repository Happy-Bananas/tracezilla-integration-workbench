<?php

namespace App\Features\OrderSynchronization\Results;

enum OrderSyncStatus: string
{
    case Created = 'created';
    case WouldCreate = 'would_create';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
