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

  const { booking_id, reason } = await req.json();
  const result = await callPhp("/bookings/cancel", "POST", {
    external_id: booking_id,
    client_external_id: authData.user.id,
    reason,
  });

  if (result.ok) {
    const admin = createClient(url, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") ?? "");
    await admin.from("bookings").update({
      status: result.payload.status,
      sync_status: "synced",
      sync_error: null,
      updated_at: new Date().toISOString(),
    }).eq("id", booking_id).eq("client_id", authData.user.id);
  }

  return Response.json(result.payload, { status: result.ok ? 200 : (result.status || 502) });
});
