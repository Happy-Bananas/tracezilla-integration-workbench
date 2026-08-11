<?php

namespace App\Features\CatalogSynchronization\Results;

enum CatalogSyncReason: string
{
    case Created = 'created';
    case DryRun = 'dry_run';
    case AlreadyExists = 'already_exists';
    case DuplicateShopifySku = 'duplicate_shopify_sku';
    case MissingSku = 'missing_sku';
    case InvalidPayload = 'invalid_payload';
    case TracezillaError = 'tracezilla_error';
}
