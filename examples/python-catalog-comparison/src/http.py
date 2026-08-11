from collections.abc import Callable
import json
from typing import Any
from urllib.request import Request, urlopen


Transport = Callable[[str, str, dict[str, str], bytes | None, int], Any]


def request_json(
    url: str,
    method: str,
    headers: dict[str, str],
    body: bytes | None,
    timeout_seconds: int,
) -> Any:
    request = Request(url, data=body, headers=headers, method=method)
    with urlopen(request, timeout=timeout_seconds) as response:
        return json.loads(response.read().decode("utf-8"))
