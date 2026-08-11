export type Fetch = typeof fetch;

export async function fetchJson(
  fetcher: Fetch,
  url: string | URL,
  init: RequestInit,
  timeoutMs: number,
): Promise<unknown> {
  const response = await fetcher(url, {
    ...init,
    signal: AbortSignal.timeout(timeoutMs),
  });

  const body = await response.text();

  if (!response.ok) {
    throw new Error(
      `HTTP ${response.status} from ${new URL(url).host}: ${body.slice(0, 300)}`,
    );
  }

  try {
    return JSON.parse(body) as unknown;
  } catch {
    throw new Error(`Invalid JSON from ${new URL(url).host}.`);
  }
}
