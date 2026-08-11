import assert from "node:assert/strict";
import test from "node:test";
import { compareCatalogs } from "../src/catalog/compare-catalogs.js";

test("compares exact SKU codes and reports data-quality issues", () => {
  const result = compareCatalogs(
    [
      { id: "1", sku: "BANANA-1", title: "Small", productTitle: "Banana" },
      { id: "2", sku: "BANANA-1", title: "Large", productTitle: "Banana" },
      { id: "3", sku: "APPLE-1", title: "Default", productTitle: "Apple" },
      { id: "4", sku: "", title: "Default", productTitle: "No SKU" },
    ],
    [
      { id: 10, skuCode: "BANANA-1", name: "Banana" },
      { id: 11, skuCode: "banana-1", name: "Case differs" },
      { id: 12, skuCode: "PEAR-1", name: "Pear" },
    ],
  );

  assert.deepEqual(result.presentInBoth, ["BANANA-1"]);
  assert.deepEqual(result.onlyInShopify, ["APPLE-1"]);
  assert.deepEqual(result.onlyInTracezilla, ["banana-1", "PEAR-1"]);
  assert.equal(result.blankShopifySkus.length, 1);
  assert.deepEqual(result.duplicateShopifySkus, [
    { sku: "BANANA-1", variantIds: ["1", "2"] },
  ]);
});
