import assert from "node:assert/strict";
import test from "node:test";
import { ShopifyClient } from "../src/clients/shopify-client.js";
import { TracezillaClient } from "../src/clients/tracezilla-client.js";
import type { Fetch } from "../src/http.js";

function response(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

test("Shopify requests a token and follows GraphQL cursors", async () => {
  const requests: Array<{ url: string; init?: RequestInit }> = [];
  const fetcher: Fetch = async (input, init) => {
    const url = input.toString();
    requests.push({ url, ...(init ? { init } : {}) });
    if (url.endsWith("/admin/oauth/access_token")) {
      return response({ access_token: "temporary-token" });
    }
    const requestBody = JSON.parse(String(init?.body)) as { variables: { after: string | null } };
    return response({
      data: {
        productVariants: {
          nodes: [
            {
              id: requestBody.variables.after ? "gid://variant/2" : "gid://variant/1",
              sku: requestBody.variables.after ? "SKU-2" : "SKU-1",
              title: "Default",
              product: { title: "Product" },
            },
          ],
          pageInfo: requestBody.variables.after
            ? { hasNextPage: false, endCursor: null }
            : { hasNextPage: true, endCursor: "cursor-1" },
        },
      },
    });
  };
  const client = new ShopifyClient(
    {
      shopUrl: "shop.myshopify.com",
      clientId: "client-id",
      clientSecret: "client-secret",
      scope: "read_products",
      apiVersion: "2025-10",
    },
    1_000,
    fetcher,
  );

  const variants = await client.getVariants(250, 10);

  assert.deepEqual(variants.map((variant) => variant.sku), ["SKU-1", "SKU-2"]);
  assert.equal(requests.length, 3);
  assert.equal(
    new Headers(requests[1]?.init?.headers).get("X-Shopify-Access-Token"),
    "temporary-token",
  );
});

test("tracezilla follows next-page links with GET requests only", async () => {
  const requests: Array<{ url: string; method: string }> = [];
  const fetcher: Fetch = async (input, init) => {
    const url = input.toString();
    requests.push({ url, method: init?.method ?? "GET" });
    return url.includes("page=2")
      ? response({ data: [{ id: 2, sku_code: "SKU-2" }], links: { next_page: null } })
      : response({
          data: [{ id: 1, sku_code: "SKU-1" }],
          links: { next_page: "/api/v1/team/skus?page=2" },
        });
  };
  const client = new TracezillaClient(
    { baseUrl: "https://tracezilla.test", teamSlug: "team", apiKey: "key" },
    1_000,
    fetcher,
  );

  const skus = await client.getSkus(250, 10);

  assert.deepEqual(skus.map((sku) => sku.skuCode), ["SKU-1", "SKU-2"]);
  assert.deepEqual(requests.map((request) => request.method), ["GET", "GET"]);
});
