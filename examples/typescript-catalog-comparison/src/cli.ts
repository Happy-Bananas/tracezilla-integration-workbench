import { ShopifyClient } from "./clients/shopify-client.js";
import { TracezillaClient } from "./clients/tracezilla-client.js";
import { compareCatalogs } from "./catalog/compare-catalogs.js";
import { loadConfig } from "./config.js";

async function main(): Promise<void> {
  const config = loadConfig();
  const shopify = new ShopifyClient(config.shopify, config.timeoutMs);
  const tracezilla = new TracezillaClient(config.tracezilla, config.timeoutMs);
  const [variants, skus] = await Promise.all([
    shopify.getVariants(config.pageSize, config.maxPages),
    tracezilla.getSkus(config.pageSize, config.maxPages),
  ]);

  process.stdout.write(`${JSON.stringify(compareCatalogs(variants, skus), null, 2)}\n`);
}

main().catch((error: unknown) => {
  const message = error instanceof Error ? error.message : "Unknown failure";
  process.stderr.write(`Catalog comparison failed: ${message}\n`);
  process.exitCode = 1;
});
