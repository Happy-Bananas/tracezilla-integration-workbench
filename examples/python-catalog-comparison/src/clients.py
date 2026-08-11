from dataclasses import dataclass
import json
from typing import Any
from urllib.parse import urlencode, urljoin, urlparse

from .config import Config
from .http import Transport, request_json


CATALOG_QUERY = """
query CatalogForComparison($first: Int!, $after: String) {
  productVariants(first: $first, after: $after) {
    nodes { id sku title product { title } }
    pageInfo { hasNextPage endCursor }
  }
}
"""


@dataclass(frozen=True)
class ShopifyVariant:
    id: str
    sku: str
    title: str
    product_title: str


@dataclass(frozen=True)
class TracezillaSku:
    sku_code: str


class ShopifyClient:
    def __init__(self, config: Config, transport: Transport = request_json):
        self.config = config
        self.transport = transport

    def get_variants(self) -> list[ShopifyVariant]:
        token = self._access_token()
        variants: list[ShopifyVariant] = []
        after: str | None = None
        for _page in range(self.config.max_pages):
            payload = self.transport(
                f"https://{self.config.shop_url}/admin/api/{self.config.shopify_api_version}/graphql.json",
                "POST",
                {"Content-Type": "application/json", "X-Shopify-Access-Token": token},
                json.dumps({"query": CATALOG_QUERY, "variables": {"first": self.config.page_size, "after": after}}).encode(),
                self.config.timeout_seconds,
            )
            if payload.get("errors"):
                raise ValueError("Shopify returned a GraphQL error")
            connection = payload.get("data", {}).get("productVariants", {})
            for node in connection.get("nodes", []):
                if not isinstance(node.get("id"), str):
                    raise ValueError("Shopify returned a variant without an ID")
                variants.append(ShopifyVariant(
                    id=node["id"],
                    sku=node.get("sku", "").strip() if isinstance(node.get("sku", ""), str) else "",
                    title=node.get("title", "") if isinstance(node.get("title", ""), str) else "",
                    product_title=node.get("product", {}).get("title", "") if isinstance(node.get("product"), dict) else "",
                ))
            page_info = connection.get("pageInfo", {})
            if not page_info.get("hasNextPage"):
                return variants
            cursor = page_info.get("endCursor")
            if not isinstance(cursor, str) or not cursor or cursor == after:
                raise ValueError("Shopify pagination returned an invalid or repeated cursor")
            after = cursor
        raise ValueError(f"Shopify catalog exceeded CATALOG_MAX_PAGES ({self.config.max_pages})")

    def _access_token(self) -> str:
        body = urlencode({
            "grant_type": "client_credentials",
            "client_id": self.config.shopify_client_id,
            "client_secret": self.config.shopify_client_secret,
            "scope": self.config.shopify_scope,
        }).encode()
        payload = self.transport(
            f"https://{self.config.shop_url}/admin/oauth/access_token",
            "POST",
            {"Content-Type": "application/x-www-form-urlencoded"},
            body,
            self.config.timeout_seconds,
        )
        token = payload.get("access_token") if isinstance(payload, dict) else None
        if not isinstance(token, str) or not token:
            raise ValueError("Shopify authentication did not return an access token")
        return token


class TracezillaClient:
    def __init__(self, config: Config, transport: Transport = request_json):
        self.config = config
        self.transport = transport

    def get_skus(self) -> list[TracezillaSku]:
        query = urlencode({"sortBy": "sku_code", "sortDirection": "asc", "perPage": self.config.page_size})
        next_url = f"{self.config.tracezilla_base_url}/api/v1/{self.config.tracezilla_team_slug}/skus?{query}"
        visited: set[str] = set()
        skus: dict[str, TracezillaSku] = {}
        expected_origin = urlparse(self.config.tracezilla_base_url).netloc

        for _page in range(self.config.max_pages):
            if next_url in visited:
                raise ValueError("tracezilla pagination returned the same page twice")
            visited.add(next_url)
            payload = self.transport(next_url, "GET", {
                "Accept": "application/json",
                "Authorization": f"Bearer {self.config.tracezilla_api_key}",
            }, None, self.config.timeout_seconds)
            if not isinstance(payload, dict) or not isinstance(payload.get("data"), list):
                raise ValueError("tracezilla returned an invalid SKU response")
            for item in payload["data"]:
                code = item.get("sku_code", "").strip() if isinstance(item, dict) and isinstance(item.get("sku_code", ""), str) else ""
                if code:
                    skus[code] = TracezillaSku(code)
            candidate = payload.get("links", {}).get("next_page") if isinstance(payload.get("links", {}), dict) else None
            if not candidate:
                return list(skus.values())
            next_url = urljoin(self.config.tracezilla_base_url, candidate)
            if urlparse(next_url).netloc != expected_origin:
                raise ValueError("tracezilla pagination returned a next-page URL on another host")
        raise ValueError(f"tracezilla catalog exceeded CATALOG_MAX_PAGES ({self.config.max_pages})")
