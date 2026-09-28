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

  const body = await req.json();
  const result = await callPhp("/clients", "POST", {
    external_id: authData.user.id,
    name: body.name,
    email: authData.user.email,
    phone: body.phone ?? null,
  });

  if (!result.ok) {
    return Response.json({
      message: result.payload.message ?? "Your profile could not be linked to the resort right now.",
    }, { status: result.status || 502 });
  }

  const admin = createClient(url, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") ?? "");
  await admin.from("profiles").update({
    full_name: body.name,
    phone: body.phone ?? null,
    mysql_user_id: result.payload.mysql_user_id,
  }).eq("id", authData.user.id);

  return Response.json(result.payload);
});
