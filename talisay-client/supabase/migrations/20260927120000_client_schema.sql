-- Client database for the Android and iOS app.
-- This schema is for Supabase/PostgreSQL only.
-- It is not a copy of the XAMPP MySQL database.

create extension if not exists pgcrypto;

create table public.profiles (
    id uuid primary key references auth.users (id) on delete cascade,
    mysql_user_id bigint unique,
    full_name text not null,
    phone text,
    avatar_path text,
    created_at timestamptz not null default now()
);

create table public.units (
    id uuid primary key default gen_random_uuid(),
    mysql_id bigint not null unique,
    unit_number text not null,
    unit_type text not null,
    variant text,
    max_occupancy integer not null,
    price_per_night numeric(12, 2) not null,
    description text,
    amenities jsonb not null default '[]'::jsonb,
    images jsonb not null default '[]'::jsonb,
    is_available boolean not null default true,
    updated_at timestamptz not null default now()
);

create table public.bookings (
    id uuid primary key default gen_random_uuid(),
    client_id uuid not null references public.profiles (id) on delete cascade,
    mysql_booking_id bigint unique,
    mysql_unit_id bigint not null,
    reference_no text,
    check_in date not null,
    check_out date not null,
    guests_count integer not null check (guests_count > 0),
    payment_method text not null,
    special_requests text,
    status text not null default 'pending',
    sync_status text not null default 'pending',
    sync_error text,
    total_amount numeric(12, 2),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    check (sync_status in ('pending', 'synced', 'rejected', 'failed'))
);

create table public.payments (
    id uuid primary key default gen_random_uuid(),
    booking_id uuid not null unique references public.bookings (id) on delete cascade,
    mysql_payment_id bigint,
    amount numeric(12, 2) not null,
    method text not null,
    status text not null default 'pending',
    proof_path text,
    updated_at timestamptz not null default now()
);

create table public.reviews (
    id uuid primary key default gen_random_uuid(),
    client_id uuid not null references public.profiles (id) on delete cascade,
    booking_id uuid not null unique references public.bookings (id) on delete cascade,
    mysql_review_id bigint unique,
    rating integer not null check (rating between 1 and 5),
    comment text not null,
    is_comment_blocked boolean not null default false,
    sync_status text not null default 'pending',
    created_at timestamptz not null default now()
);

create table public.memories (
    id uuid primary key default gen_random_uuid(),
    client_id uuid not null references public.profiles (id) on delete cascade,
    caption text,
    photo_path text not null,
    taken_on date,
    created_at timestamptz not null default now()
);

create table public.notifications (
    id uuid primary key default gen_random_uuid(),
    client_id uuid not null references public.profiles (id) on delete cascade,
    title text not null,
    body text not null,
    read_at timestamptz,
    created_at timestamptz not null default now()
);

alter table public.profiles enable row level security;
alter table public.units enable row level security;
alter table public.bookings enable row level security;
alter table public.payments enable row level security;
alter table public.reviews enable row level security;
alter table public.memories enable row level security;
alter table public.notifications enable row level security;

create policy profiles_select_own on public.profiles
    for select to authenticated using (id = auth.uid());
create policy profiles_update_own on public.profiles
    for update to authenticated using (id = auth.uid()) with check (id = auth.uid());

create policy units_read on public.units
    for select to authenticated using (true);

create policy bookings_select_own on public.bookings
    for select to authenticated using (client_id = auth.uid());
create policy bookings_insert_own on public.bookings
    for insert to authenticated with check (
        client_id = auth.uid()
        and status = 'pending'
        and sync_status = 'pending'
        and mysql_booking_id is null
        and reference_no is null
    );

create policy payments_select_own on public.payments
    for select to authenticated using (
        exists (
            select 1 from public.bookings b
            where b.id = payments.booking_id and b.client_id = auth.uid()
        )
    );

create policy reviews_select_own on public.reviews
    for select to authenticated using (client_id = auth.uid());
create policy reviews_insert_own on public.reviews
    for insert to authenticated with check (
        client_id = auth.uid()
        and sync_status = 'pending'
        and mysql_review_id is null
    );

create policy memories_all_own on public.memories
    for all to authenticated using (client_id = auth.uid()) with check (client_id = auth.uid());

create policy notifications_select_own on public.notifications
    for select to authenticated using (client_id = auth.uid());
create policy notifications_update_own on public.notifications
    for update to authenticated using (client_id = auth.uid()) with check (client_id = auth.uid());

create or replace function public.handle_new_user()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
    insert into public.profiles (id, full_name)
    values (
        new.id,
        coalesce(new.raw_user_meta_data ->> 'full_name', split_part(new.email, '@', 1))
    );
    return new;
end;
$$;

create trigger on_auth_user_created
    after insert on auth.users
    for each row execute function public.handle_new_user();

alter publication supabase_realtime add table public.bookings;
alter publication supabase_realtime add table public.notifications;
alter publication supabase_realtime add table public.units;

insert into storage.buckets (id, name, public)
values
    ('avatars', 'avatars', false),
    ('memories', 'memories', false),
    ('payment-proofs', 'payment-proofs', false)
on conflict (id) do nothing;

create policy storage_read_own on storage.objects
    for select to authenticated using (
        bucket_id in ('avatars', 'memories', 'payment-proofs')
        and (storage.foldername(name))[1] = auth.uid()::text
    );

create policy storage_insert_own on storage.objects
    for insert to authenticated with check (
        bucket_id in ('avatars', 'memories', 'payment-proofs')
        and (storage.foldername(name))[1] = auth.uid()::text
    );

create policy storage_update_own on storage.objects
    for update to authenticated using (
        bucket_id in ('avatars', 'memories', 'payment-proofs')
        and (storage.foldername(name))[1] = auth.uid()::text
    );

create policy storage_delete_own on storage.objects
    for delete to authenticated using (
        bucket_id in ('avatars', 'memories', 'payment-proofs')
        and (storage.foldername(name))[1] = auth.uid()::text
    );
