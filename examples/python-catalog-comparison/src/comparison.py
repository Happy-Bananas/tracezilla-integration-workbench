from typing import Any

from .clients import ShopifyVariant, TracezillaSku


def compare_catalogs(variants: list[ShopifyVariant], skus: list[TracezillaSku]) -> dict[str, Any]:
    by_sku: dict[str, list[ShopifyVariant]] = {}
    blanks: list[dict[str, str]] = []
    for variant in variants:
        if not variant.sku:
            blanks.append({"variantId": variant.id, "productTitle": variant.product_title, "variantTitle": variant.title})
        else:
            by_sku.setdefault(variant.sku, []).append(variant)

    shopify_codes = set(by_sku)
    tracezilla_codes = {sku.sku_code for sku in skus}
    shared = sorted(shopify_codes & tracezilla_codes)
    only_shopify = sorted(shopify_codes - tracezilla_codes)
    only_tracezilla = sorted(tracezilla_codes - shopify_codes)
    duplicates = [
        {"sku": code, "variantIds": sorted(variant.id for variant in matches)}
        for code, matches in sorted(by_sku.items()) if len(matches) > 1
    ]
    return {
        "summary": {
            "shopifyVariants": len(variants), "tracezillaSkus": len(skus),
            "presentInBoth": len(shared), "onlyInShopify": len(only_shopify),
            "onlyInTracezilla": len(only_tracezilla), "blankShopifySkus": len(blanks),
            "duplicateShopifySkus": len(duplicates),
        },
        "presentInBoth": shared, "onlyInShopify": only_shopify,
        "onlyInTracezilla": only_tracezilla, "blankShopifySkus": blanks,
        "duplicateShopifySkus": duplicates,
    }
