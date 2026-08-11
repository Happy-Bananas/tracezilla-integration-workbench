export interface Config {
  shopify: {
    shopUrl: string;
    clientId: string;
    clientSecret: string;
    scope: string;
    apiVersion: string;
  };
  tracezilla: {
    baseUrl: string;
    teamSlug: string;
    apiKey: string;
  };
  pageSize: number;
  maxPages: number;
  timeoutMs: number;
}

function required(environment: NodeJS.ProcessEnv, name: string): string {
  const value = environment[name]?.trim();

  if (!value) {
    throw new Error(`Missing required environment variable: ${name}`);
  }

  return value;
}

function positiveInteger(
  environment: NodeJS.ProcessEnv,
  name: string,
  fallback: number,
): number {
  const raw = environment[name]?.trim();
  const value = raw ? Number(raw) : fallback;

  if (!Number.isSafeInteger(value) || value < 1) {
    throw new Error(`${name} must be a positive integer.`);
  }

  return value;
}

function shopDomain(value: string): string {
  const domain = value.replace(/^https?:\/\//i, "").replace(/\/$/, "");

  if (!/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/i.test(domain)) {
    throw new Error("SHOPIFY_SHOP_URL must be a myshopify.com hostname.");
  }

  return domain;
}

export function loadConfig(environment: NodeJS.ProcessEnv = process.env): Config {
  const tracezillaBaseUrl = new URL(
    environment.TRACEZILLA_BASE_URL?.trim() || "https://app.tracezilla.com",
  );

  if (tracezillaBaseUrl.protocol !== "https:") {
    throw new Error("TRACEZILLA_BASE_URL must use HTTPS.");
  }

  return {
    shopify: {
      shopUrl: shopDomain(required(environment, "SHOPIFY_SHOP_URL")),
      clientId: required(environment, "SHOPIFY_CLIENT_ID"),
      clientSecret: required(environment, "SHOPIFY_CLIENT_SECRET"),
      scope: environment.SHOPIFY_SCOPE?.trim() || "read_products",
      apiVersion: environment.SHOPIFY_API_VERSION?.trim() || "2025-10",
    },
    tracezilla: {
      baseUrl: tracezillaBaseUrl.toString().replace(/\/$/, ""),
      teamSlug: required(environment, "TRACEZILLA_TEAM_SLUG"),
      apiKey: required(environment, "TRACEZILLA_API_KEY"),
    },
    pageSize: positiveInteger(environment, "CATALOG_PAGE_SIZE", 250),
    maxPages: positiveInteger(environment, "CATALOG_MAX_PAGES", 100),
    timeoutMs: positiveInteger(environment, "HTTP_TIMEOUT_MS", 30_000),
  };
}
