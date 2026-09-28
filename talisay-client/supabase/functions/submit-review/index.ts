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

  const { review_id } = await req.json();
  const admin = createClient(url, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") ?? "");
  const { data: review } = await admin.from("reviews").select("*").eq("id", review_id).single();
  if (!review || review.client_id !== authData.user.id) {
    return Response.json({ message: "Review was not found." }, { status: 404 });
  }

  const result = await callPhp("/reviews", "POST", {
    external_id: review.id,
    client_external_id: authData.user.id,
    booking_external_id: review.booking_id,
    rating: review.rating,
    comment: review.comment,
  });

  await admin.from("reviews").update({
    mysql_review_id: result.payload.mysql_review_id ?? null,
    sync_status: result.ok ? "synced" : "failed",
  }).eq("id", review.id);

  return Response.json(
    result.ok ? result.payload : { message: result.payload.message ?? "The review could not be sent to the resort." },
    { status: result.ok ? 201 : (result.status || 502) },
  );
});
