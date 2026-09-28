import { createClient } from "https://esm.sh/@supabase/supabase-js@2";
import { callPhp, supabaseUser } from "../_shared/integration.ts";

Deno.serve(async (req) => {
  const { url, anon, authorization } = supabaseUser(req);
  const userClient = createClient(url, anon, {
    global: { headers: { Authorization: authorization } },
  });
  const { data: authData, error: authError } = await userClient.auth.getUser();
  if (authError || !authData.user) {
    return Response.json({ message: "Sign in is required." }, { status: 401 });
  }

  const { booking_id } = await req.json();
  const admin = createClient(url, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY") ?? "");
  const { data: booking } = await admin.from("bookings").select("*").eq("id", booking_id).single();

  if (!booking || booking.client_id !== authData.user.id) {
    return Response.json({ message: "Reservation was not found." }, { status: 404 });
  }

  const result = await callPhp("/bookings", "POST", {
    external_id: booking.id,
    client_external_id: booking.client_id,
    mysql_unit_id: booking.mysql_unit_id,
    check_in: booking.check_in,
    check_out: booking.check_out,
    guests_count: booking.guests_count,
    payment_method: booking.payment_method,
    special_requests: booking.special_requests,
  });

  if (!result.ok) {
    const rejected = result.status === 409 || result.status === 422;
    await admin.from("bookings").update({
      sync_status: rejected ? "rejected" : "failed",
      sync_error: result.payload.message ?? "The resort could not confirm this request.",
      updated_at: new Date().toISOString(),
    }).eq("id", booking.id);

    return Response.json({
      message: result.payload.message ?? "Your booking request could not be confirmed right now. Please try again.",
      sync_status: rejected ? "rejected" : "failed",
    }, { status: result.status || 502 });
  }

  await admin.from("bookings").update({
    mysql_booking_id: result.payload.mysql_booking_id,
    reference_no: result.payload.reference_no,
    status: result.payload.status,
    total_amount: result.payload.total_amount,
    sync_status: "synced",
    sync_error: null,
    updated_at: new Date().toISOString(),
  }).eq("id", booking.id);

  if (result.payload.total_amount != null) {
    await admin.from("payments").upsert({
      booking_id: booking.id,
      amount: result.payload.total_amount,
      method: booking.payment_method,
      status: result.payload.payment_status ?? "pending",
      updated_at: new Date().toISOString(),
    }, { onConflict: "booking_id" });
  }

  return Response.json(result.payload, { status: 201 });
});
