export async function callPhp(path: string, method = "GET", body?: unknown) {
  const base = (Deno.env.get("PHP_API_URL") ?? "").replace(/\/$/, "");
  const token = Deno.env.get("MOBILE_INTEGRATION_TOKEN") ?? "";
  const response = await fetch(`${base}/api/integration${path}`, {
    method,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-Integration-Token": token,
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const payload = await response.json().catch(() => ({}));
  return { ok: response.ok, status: response.status, payload };
}

export function supabaseUser(req: Request) {
  const url = Deno.env.get("SUPABASE_URL") ?? "";
  const anon = Deno.env.get("SUPABASE_ANON_KEY") ?? "";
  return { url, anon, authorization: req.headers.get("Authorization") ?? "" };
}
