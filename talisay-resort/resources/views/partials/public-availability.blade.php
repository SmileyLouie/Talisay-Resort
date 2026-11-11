{{-- Availability bar used at the bottom of the public pages. Submitting leads to the client log in. --}}
<section class="lp-avail">
    <div class="lp-section lp-avail-inner">
        <div>
            <div class="lp-label">Plan your stay</div>
            <h2 class="lp-title">Check availability</h2>
            <p class="lp-copy">Pick your dates to see which rooms and cottages are free. Book by the night or by the hour.</p>
        </div>
        <form class="lp-search lp-search-flat" action="{{ route('public.booking') }}" method="get">
            <label>Check in
                <input type="date" name="check_in" aria-label="Check in">
            </label>
            <label>Check out
                <input type="date" name="check_out" aria-label="Check out">
            </label>
            <label>Guests
                <input type="number" name="guests" min="1" value="2" aria-label="Guests">
            </label>
            <button type="submit" class="lp-btn lp-btn-sand"><i class="bi bi-search"></i> Check Availability</button>
        </form>
    </div>
</section>
