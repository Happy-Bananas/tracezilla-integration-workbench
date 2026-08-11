import unittest

from src.clients import ShopifyVariant, TracezillaSku
from src.comparison import compare_catalogs


class ComparisonTest(unittest.TestCase):
    def test_reports_shared_missing_blank_and_duplicate_skus(self):
        result = compare_catalogs([
            ShopifyVariant("1", "BOTH", "One", "Product"),
            ShopifyVariant("2", "SHOPIFY", "Two", "Product"),
            ShopifyVariant("3", "", "Blank", "Product"),
            ShopifyVariant("4", "BOTH", "Duplicate", "Product"),
        ], [TracezillaSku("BOTH"), TracezillaSku("TRACEZILLA")])

        self.assertEqual(["BOTH"], result["presentInBoth"])
        self.assertEqual(["SHOPIFY"], result["onlyInShopify"])
        self.assertEqual(["TRACEZILLA"], result["onlyInTracezilla"])
        self.assertEqual(1, result["summary"]["blankShopifySkus"])
        self.assertEqual(["1", "4"], result["duplicateShopifySkus"][0]["variantIds"])


if __name__ == "__main__":
    unittest.main()
