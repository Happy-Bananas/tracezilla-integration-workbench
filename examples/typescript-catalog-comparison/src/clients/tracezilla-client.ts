import type { Config } from "../config.js";
import { fetchJson, type Fetch } from "../http.js";

export interface TracezillaSku {
  id: string | number | null;
  skuCode: string;
  name: string;
}

export class TracezillaClient {
  public constructor(
    private readonly config: Config["tracezilla"],
    private readonly timeoutMs: number,
    private readonly fetcher: Fetch = fetch,
  ) {}

  public async getSkus(pageSize: number, maxPages: number): Promise<TracezillaSku[]> {
    const skus = new Map<string, TracezillaSku>();
    const visited = new Set<string>();
    let nextUrl = new URL(
      `/api/v1/${encodeURIComponent(this.config.teamSlug)}/skus`,
      this.config.baseUrl,
    );
    nextUrl.search = new URLSearchParams({
      sortBy: "sku_code",
      sortDirection: "asc",
      perPage: String(Math.min(pageSize, 250)),
    }).toString();

    for (let page = 1; page <= maxPages; page += 1) {
      const fingerprint = nextUrl.toString();
      if (visited.has(fingerprint)) {
        throw new Error("tracezilla pagination returned the same page twice.");
      }
      visited.add(fingerprint);

      const payload = await fetchJson(
        this.fetcher,
        nextUrl,
        {
          method: "GET",
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${this.config.apiKey}`,
          },
        },
        this.timeoutMs,
      );
      const pageData = this.page(payload);

      for (const sku of pageData.skus) {
        if (sku.skuCode !== "") {
          skus.set(sku.skuCode, sku);
        }
      }

      if (!pageData.nextPage) {
        return [...skus.values()];
      }

      const candidate = new URL(pageData.nextPage, this.config.baseUrl);
      if (candidate.origin !== new URL(this.config.baseUrl).origin) {
        throw new Error("tracezilla pagination returned a next-page URL on another host.");
      }
      nextUrl = candidate;
    }

    throw new Error(`tracezilla catalog exceeded CATALOG_MAX_PAGES (${maxPages}).`);
  }

  private page(payload: unknown): { skus: TracezillaSku[]; nextPage: string | null } {
    if (!isRecord(payload) || !Array.isArray(payload.data)) {
      throw new Error("tracezilla returned an invalid SKU response.");
    }

    const links = isRecord(payload.links) ? payload.links : {};
    const nextPage = links.next_page;

    if (!(typeof nextPage === "string" || nextPage === null || nextPage === undefined)) {
      throw new Error("tracezilla returned an invalid next-page link.");
    }

    return {
      skus: payload.data.map((item) => {
        if (!isRecord(item)) {
          throw new Error("tracezilla returned an invalid SKU item.");
        }
        return {
          id:
            typeof item.id === "string" || typeof item.id === "number"
              ? item.id
              : null,
          skuCode: typeof item.sku_code === "string" ? item.sku_code.trim() : "",
          name:
            typeof item.global_name === "string"
              ? item.global_name
              : typeof item.name === "string"
                ? item.name
                : "",
        };
      }),
      nextPage: nextPage ?? null,
    };
  }
}

function isRecord(value: unknown): value is Record<string, any> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}
