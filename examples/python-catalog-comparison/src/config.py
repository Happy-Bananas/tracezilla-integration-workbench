from dataclasses import dataclass
import os


def required(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value:
        raise ValueError(f"{name} is required")
    return value


def positive_integer(name: str, default: int) -> int:
    raw = os.getenv(name, str(default))
    try:
        value = int(raw)
    except ValueError as error:
        raise ValueError(f"{name} must be a positive integer") from error
    if value < 1:
        raise ValueError(f"{name} must be a positive integer")
    return value


@dataclass(frozen=True)
class Config:
    shop_url: str
    shopify_client_id: str
    shopify_client_secret: str
    shopify_scope: str
    shopify_api_version: str
    tracezilla_base_url: str
    tracezilla_team_slug: str
    tracezilla_api_key: str
    page_size: int
    max_pages: int
    timeout_seconds: int

    @classmethod
    def from_environment(cls) -> "Config":
        return cls(
            shop_url=required("SHOPIFY_SHOP_URL").removeprefix("https://").rstrip("/"),
            shopify_client_id=required("SHOPIFY_CLIENT_ID"),
            shopify_client_secret=required("SHOPIFY_CLIENT_SECRET"),
            shopify_scope=os.getenv("SHOPIFY_SCOPE", "read_products").strip() or "read_products",
            shopify_api_version=os.getenv("SHOPIFY_API_VERSION", "2025-10").strip() or "2025-10",
            tracezilla_base_url=required("TRACEZILLA_BASE_URL").rstrip("/"),
            tracezilla_team_slug=required("TRACEZILLA_TEAM_SLUG"),
            tracezilla_api_key=required("TRACEZILLA_API_KEY"),
            page_size=min(positive_integer("CATALOG_PAGE_SIZE", 250), 250),
            max_pages=positive_integer("CATALOG_MAX_PAGES", 100),
            timeout_seconds=positive_integer("HTTP_TIMEOUT_SECONDS", 30),
        )
