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
  const result = await callPhp("/recommend", "POST", body);
  return Response.json(result.payload, { status: result.ok ? 200 : (result.status || 502) });
});