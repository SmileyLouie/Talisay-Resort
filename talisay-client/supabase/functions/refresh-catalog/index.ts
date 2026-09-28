import { createClient } from "https://esm.sh/@supabase/supabase-js@2";
import { callPhp, supabaseUser } from "../_shared/integration.ts";

Deno.serve(async (req) => {
  const { url, anon, authorization } = supabaseUser(req);
  const userClient = createClient(url, anon, {
    global: { headers: { Authorization: authorization } },
  });
  const { data: authData, error } = await userClient.auth.getUser();
  if (error || !authData.user) {
    return Response.json({ message: "Sign in is required." }, { status: 401 });
  }

  const result = await callPhp("/catalog");
  if (!result.ok) {
    return Response.json({
      message: "Stay information could not be refreshed from the resort.",
    }, { status: result.status || 502 });
  }

  const admin = createClient(url, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") ?? "");
  const units = result.payload.units ?? [];
  if (units.length > 0) {
    const { error: upsertError } = await admin.from("units").upsert(units, { onConflict: "mysql_id" });
    if (upsertError) {
      return Response.json({ message: upsertError.message }, { status: 500 });
    }
  }

  return Response.json({ count: units.length });
});
