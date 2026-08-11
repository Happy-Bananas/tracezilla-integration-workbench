import type { Config } from "../config.js";
import { fetchJson, type Fetch } from "../http.js";

export interface ShopifyVariant {
  id: string;
  sku: string;
  title: string;
  productTitle: string;
}

interface VariantConnection {
  nodes: Array<{
    id: unknown;
    sku: unknown;
    title: unknown;
    product?: { title?: unknown };
  }>;
  pageInfo: { hasNextPage: boolean; endCursor: string | null };
}

const CATALOG_QUERY = `
query CatalogForComparison($first: Int!, $after: String) {
  productVariants(first: $first, after: $after) {
    nodes { id sku title product { title } }
    pageInfo { hasNextPage endCursor }
  }
}`;

export class ShopifyClient {
  public constructor(
    private readonly config: Config["shopify"],
    private readonly timeoutMs: number,
    private readonly fetcher: Fetch = fetch,
  ) {}

  public async getVariants(pageSize: number, maxPages: number): Promise<ShopifyVariant[]> {
    const token = await this.getAccessToken();
    const variants: ShopifyVariant[] = [];
    let after: string | null = null;

    for (let page = 1; page <= maxPages; page += 1) {
      const payload = await fetchJson(
        this.fetcher,
        `https://${this.config.shopUrl}/admin/api/${this.config.apiVersion}/graphql.json`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-Shopify-Access-Token": token,
          },
          body: JSON.stringify({
            query: CATALOG_QUERY,
            variables: { first: Math.min(pageSize, 250), after },
          }),
        },
        this.timeoutMs,
      );

      const connection = this.variantConnection(payload);
      variants.push(...connection.nodes.map((node) => this.variant(node)));

      if (!connection.pageInfo.hasNextPage) {
        return variants;
      }

      if (!connection.pageInfo.endCursor || connection.pageInfo.endCursor === after) {
        throw new Error("Shopify pagination returned an invalid or repeated cursor.");
      }

      after = connection.pageInfo.endCursor;
    }

    throw new Error(`Shopify catalog exceeded CATALOG_MAX_PAGES (${maxPages}).`);
  }

  private async getAccessToken(): Promise<string> {
    const body = new URLSearchParams({
      grant_type: "client_credentials",
      client_id: this.config.clientId,
      client_secret: this.config.clientSecret,
      scope: this.config.scope,
    });
    const payload = await fetchJson(
      this.fetcher,
      `https://${this.config.shopUrl}/admin/oauth/access_token`,
      {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body,
      },
      this.timeoutMs,
    );

    if (!isRecord(payload) || typeof payload.access_token !== "string") {
      throw new Error("Shopify authentication did not return an access token.");
    }

    return payload.access_token;
  }

  private variantConnection(payload: unknown): VariantConnection {
    if (!isRecord(payload)) {
      throw new Error("Shopify returned an invalid GraphQL response.");
    }
    if (Array.isArray(payload.errors) && payload.errors.length > 0) {
      throw new Error(`Shopify GraphQL error: ${JSON.stringify(payload.errors)}`);
    }

    const data = payload.data;
    const connection = isRecord(data) ? data.productVariants : undefined;
    const pageInfo = isRecord(connection) ? connection.pageInfo : undefined;

    if (
      !isRecord(connection) ||
      !Array.isArray(connection.nodes) ||
      !isRecord(pageInfo) ||
      typeof pageInfo.hasNextPage !== "boolean" ||
      !(typeof pageInfo.endCursor === "string" || pageInfo.endCursor === null)
    ) {
      throw new Error("Shopify returned an invalid productVariants connection.");
    }

    return {
      nodes: connection.nodes,
      pageInfo: {
        hasNextPage: pageInfo.hasNextPage,
        endCursor: pageInfo.endCursor,
      },
    };
  }

  private variant(node: VariantConnection["nodes"][number]): ShopifyVariant {
    if (typeof node.id !== "string") {
      throw new Error("Shopify returned a variant without an ID.");
    }

    return {
      id: node.id,
      sku: typeof node.sku === "string" ? node.sku.trim() : "",
      title: typeof node.title === "string" ? node.title : "",
      productTitle:
        isRecord(node.product) && typeof node.product.title === "string"
          ? node.product.title
          : "",
    };
  }
}

function isRecord(value: unknown): value is Record<string, any> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}
