import type { ShopifyVariant } from "../clients/shopify-client.js";
import type { TracezillaSku } from "../clients/tracezilla-client.js";

export interface CatalogComparison {
  summary: {
    shopifyVariants: number;
    tracezillaSkus: number;
    presentInBoth: number;
    onlyInShopify: number;
    onlyInTracezilla: number;
    blankShopifySkus: number;
    duplicateShopifySkus: number;
  };
  presentInBoth: string[];
  onlyInShopify: string[];
  onlyInTracezilla: string[];
  blankShopifySkus: Array<{ variantId: string; productTitle: string; variantTitle: string }>;
  duplicateShopifySkus: Array<{ sku: string; variantIds: string[] }>;
}

export function compareCatalogs(
  variants: ShopifyVariant[],
  tracezillaSkus: TracezillaSku[],
): CatalogComparison {
  const shopifyBySku = new Map<string, ShopifyVariant[]>();
  const blankShopifySkus: CatalogComparison["blankShopifySkus"] = [];

  for (const variant of variants) {
    if (variant.sku === "") {
      blankShopifySkus.push({
        variantId: variant.id,
        productTitle: variant.productTitle,
        variantTitle: variant.title,
      });
      continue;
    }
    shopifyBySku.set(variant.sku, [...(shopifyBySku.get(variant.sku) ?? []), variant]);
  }

  const shopifyCodes = new Set(shopifyBySku.keys());
  const tracezillaCodes = new Set(tracezillaSkus.map((sku) => sku.skuCode));
  const presentInBoth = sorted([...shopifyCodes].filter((sku) => tracezillaCodes.has(sku)));
  const onlyInShopify = sorted([...shopifyCodes].filter((sku) => !tracezillaCodes.has(sku)));
  const onlyInTracezilla = sorted([...tracezillaCodes].filter((sku) => !shopifyCodes.has(sku)));
  const duplicateShopifySkus = [...shopifyBySku.entries()]
    .filter(([, matches]) => matches.length > 1)
    .map(([sku, matches]) => ({ sku, variantIds: matches.map((match) => match.id).sort() }))
    .sort((left, right) => left.sku.localeCompare(right.sku));

  return {
    summary: {
      shopifyVariants: variants.length,
      tracezillaSkus: tracezillaSkus.length,
      presentInBoth: presentInBoth.length,
      onlyInShopify: onlyInShopify.length,
      onlyInTracezilla: onlyInTracezilla.length,
      blankShopifySkus: blankShopifySkus.length,
      duplicateShopifySkus: duplicateShopifySkus.length,
    },
    presentInBoth,
    onlyInShopify,
    onlyInTracezilla,
    blankShopifySkus,
    duplicateShopifySkus,
  };
}

function sorted(values: string[]): string[] {
  return values.sort((left, right) => left.localeCompare(right));
}
