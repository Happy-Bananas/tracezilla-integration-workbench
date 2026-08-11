import json
import sys

from .clients import ShopifyClient, TracezillaClient
from .comparison import compare_catalogs
from .config import Config


def main() -> int:
    try:
        config = Config.from_environment()
        result = compare_catalogs(ShopifyClient(config).get_variants(), TracezillaClient(config).get_skus())
        print(json.dumps(result, indent=2))
        return 0
    except Exception as error:
        print(f"Catalog comparison failed: {error}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
