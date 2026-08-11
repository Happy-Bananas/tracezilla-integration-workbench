import json
import unittest

from src.clients import ShopifyClient, TracezillaClient
from src.config import Config


def config() -> Config:
    return Config(
        shop_url="shop.test", shopify_client_id="client", shopify_client_secret="secret",
        shopify_scope="read_products", shopify_api_version="2025-10",
        tracezilla_base_url="https://tracezilla.test", tracezilla_team_slug="team",
        tracezilla_api_key="key", page_size=1, max_pages=3, timeout_seconds=10,
    )


class ClientTest(unittest.TestCase):
    def test_shopify_exchanges_token_and_follows_cursors(self):
        calls = []

        def transport(url, method, headers, body, timeout):
            calls.append((url, method, headers, body, timeout))
            if url.endswith("/access_token"):
                return {"access_token": "token"}
            after = json.loads(body)["variables"]["after"]
            code = "ONE" if after is None else "TWO"
            return {"data": {"productVariants": {
                "nodes": [{"id": code, "sku": code, "title": code, "product": {"title": "Product"}}],
                "pageInfo": {"hasNextPage": after is None, "endCursor": "cursor" if after is None else None},
            }}}

        variants = ShopifyClient(config(), transport).get_variants()
        self.assertEqual(["ONE", "TWO"], [variant.sku for variant in variants])
        self.assertEqual("POST", calls[0][1])
        self.assertEqual("token", calls[1][2]["X-Shopify-Access-Token"])

    def test_tracezilla_uses_get_and_follows_next_page(self):
        calls = []

        def transport(url, method, headers, body, timeout):
            calls.append((url, method, headers, body, timeout))
            if "page=2" in url:
                return {"data": [{"sku_code": "TWO"}], "links": {"next_page": None}}
            return {"data": [{"sku_code": "ONE"}], "links": {"next_page": "/api/v1/team/skus?page=2"}}

        skus = TracezillaClient(config(), transport).get_skus()
        self.assertEqual(["ONE", "TWO"], [sku.sku_code for sku in skus])
        self.assertTrue(all(call[1] == "GET" and call[3] is None for call in calls))
        self.assertTrue(all(call[2]["Authorization"] == "Bearer key" for call in calls))

    def test_tracezilla_rejects_cross_host_pagination(self):
        def transport(url, method, headers, body, timeout):
            return {"data": [], "links": {"next_page": "https://evil.test/skus?page=2"}}

        with self.assertRaisesRegex(ValueError, "another host"):
            TracezillaClient(config(), transport).get_skus()


if __name__ == "__main__":
    unittest.main()
