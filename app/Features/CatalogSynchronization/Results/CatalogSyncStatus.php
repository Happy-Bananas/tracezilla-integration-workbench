<?php

namespace App\Features\CatalogSynchronization\Results;

enum CatalogSyncStatus: string
{
    case Created = 'created';
    case WouldCreate = 'would_create';
    case Skipped = 'skipped';
    case Invalid = 'invalid';
    case Failed = 'failed';
}
