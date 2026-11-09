import 'package:flutter/material.dart';
import 'package:talisay_client/config.dart';
import 'package:talisay_client/repository.dart';
import 'package:talisay_client/theme.dart';
import 'package:url_launcher/url_launcher.dart';

/// Mobile layout of the public website: same sections, stacked for a phone.
class GuestLanding extends StatefulWidget {
  const GuestLanding({
    super.key,
    required this.repository,
    required this.onOpen,
    required this.onUnit,
    required this.onNotify,
  });

  final ResortRepository repository;
  final void Function(String section) onOpen;
  final void Function(Map<String, dynamic> unit) onUnit;
  final VoidCallback onNotify;

  @override
  State<GuestLanding> createState() => _GuestLandingState();
}

class _GuestLandingState extends State<GuestLanding> {
  late Future<Map<String, dynamic>> _site = widget.repository.site();
  late Future<List<Map<String, dynamic>>> _units = widget.repository.units();

  Future<void> _reload() async {
    setState(() {
      _site = widget.repository.site();
      _units = widget.repository.units();
    });
    await Future.wait([_site, _units]);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(AppConfig.resortName, style: TextStyle(color: Colors.white, fontFamily: 'serif', fontSize: 16, fontWeight: FontWeight.w700, height: 1.1)),
            Text('SMART TOURISM SYSTEM', style: TextStyle(color: Color(0xBFFFFFFF), fontSize: 9, fontWeight: FontWeight.w700, letterSpacing: 1.2)),
          ],
        ),
        actions: [
          IconButton(onPressed: widget.onNotify, icon: const Icon(Icons.notifications_none)),
          IconButton(onPressed: () => widget.onOpen('menu'), icon: const Icon(Icons.menu)),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder(
          future: Future.wait([_site, _units]),
          builder: (context, snapshot) {
            if (snapshot.connectionState != ConnectionState.done) {
              return ListView(children: [
                _Hero(onBook: () => widget.onOpen('rooms'), onTour: () => widget.onOpen('tour'), onExclusive: () => widget.onOpen('exclusive')),
                const Padding(padding: EdgeInsets.all(32), child: Center(child: CircularProgressIndicator())),
              ]);
            }
            if (snapshot.hasError) {
              return ListView(children: [
                _Hero(onBook: () => widget.onOpen('rooms'), onTour: () => widget.onOpen('tour'), onExclusive: () => widget.onOpen('exclusive')),
                Padding(padding: const EdgeInsets.all(24), child: Text('The resort details could not be loaded. Pull down to try again.\n${snapshot.error}', style: _copy)),
              ]);
            }
            final site = snapshot.data == null ? <String, dynamic>{} : Map<String, dynamic>.from(snapshot.data![0] as Map);
            final units = snapshot.data == null ? <Map<String, dynamic>>[] : (snapshot.data![1] as List).cast<Map<String, dynamic>>();
            final resort = site['resort'] is Map ? Map<String, dynamic>.from(site['resort'] as Map) : <String, dynamic>{};
            final about = site['about'] is Map ? Map<String, dynamic>.from(site['about'] as Map) : <String, dynamic>{};
            final activities = _maps(site['activities']);
            final reviews = _maps(site['reviews']);
            final rooms = units.where((unit) => unit['unit_type'] == 'room' && unit['is_available'] == true).toList();
            final cottages = units.where((unit) => unit['unit_type'] == 'cottage' && unit['is_available'] == true).toList();
            return ListView(
              children: [
                _Hero(
                  onBook: () => widget.onOpen('rooms'),
                  onTour: () => widget.onOpen('tour'),
                  onExclusive: () => widget.onOpen('exclusive'),
                ),
                _Block(
                  label: 'About the resort',
                  title: resort['name']?.toString() ?? AppConfig.resortName,
                  body: about['story']?.toString() ?? 'Rooms and cottages by the beach, with booking by the night or by the hour.',
                  action: 'More about the resort',
                  onAction: () => widget.onOpen('about'),
                ),
                _UnitStrip(label: 'Time to reconnect', title: 'Our Rooms', units: rooms, onSeeAll: () => widget.onOpen('rooms'), onUnit: widget.onUnit),
                _UnitStrip(label: 'By the water', title: 'Our Cottages', units: cottages, onSeeAll: () => widget.onOpen('cottages'), onUnit: widget.onUnit),
                if (activities.isNotEmpty)
                  _Block(
                    label: 'Things to do',
                    title: 'Resort activities',
                    body: 'Snorkeling, island hopping, and other activities you can add to a stay.',
                    action: 'See activities',
                    onAction: () => widget.onOpen('activities'),
                    extra: Column(
                      children: [
                        for (final activity in activities.take(3))
                          _ActivityCard(activity: activity, onAdd: activity['is_bookable'] == true ? () => widget.onOpen('rooms') : null),
                      ],
                    ),
                  ),
                _Arrival(resort: resort),
                _ReviewStrip(
                  reviews: reviews,
                  average: site['average_rating'],
                  count: site['review_count'],
                  onSeeAll: () => widget.onOpen('reviews'),
                ),
                _ContactStrip(resort: resort, onOpen: () => widget.onOpen('contact')),
                const SizedBox(height: 24),
              ],
            );
          },
        ),
      ),
    );
  }
}

class SiteMenuPage extends StatelessWidget {
  const SiteMenuPage({super.key, required this.onOpen});
  final void Function(String section) onOpen;

  @override
  Widget build(BuildContext context) {
    const items = [
      ['about', 'About', 'Story, vision, mission, and gallery'],
      ['tour', '360° tour', 'Walk the grounds before you arrive'],
      ['rooms', 'Rooms', 'Nightly and hourly room stays'],
      ['cottages', 'Cottages', 'Beachfront cottages'],
      ['activities', 'Activities', 'Add snorkeling and more to a stay'],
      ['exclusive', 'Exclusive booking', 'Reserve the whole resort'],
      ['reviews', 'Reviews', 'What guests said'],
      ['contact', 'Contact', 'Phone, email, map, and hours'],
      ['bookings', 'My bookings', 'References, payments, and changes'],
      ['profile', 'My profile', 'Name, photo, and password'],
      ['chat', 'Chat', 'Ask the resort assistant'],
    ];
    return Scaffold(
      appBar: AppBar(title: const Text('Menu')),
      body: ListView(
        children: [
          for (final item in items)
            ListTile(
              title: Text(item[1], style: const TextStyle(fontWeight: FontWeight.w800)),
              subtitle: Text(item[2]),
              trailing: const Icon(Icons.chevron_right),
              onTap: () {
                Navigator.pop(context);
                onOpen(item[0]);
              },
            ),
        ],
      ),
    );
  }
}

class AboutPage extends StatelessWidget {
  const AboutPage({super.key, required this.repository, required this.onRooms, required this.onCottages});
  final ResortRepository repository;
  final VoidCallback onRooms;
  final VoidCallback onCottages;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('About')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: repository.site(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
          final site = snapshot.data!;
          final about = site['about'] is Map ? Map<String, dynamic>.from(site['about'] as Map) : <String, dynamic>{};
          final resort = site['resort'] is Map ? Map<String, dynamic>.from(site['resort'] as Map) : <String, dynamic>{};
          final gallery = _maps(site['gallery']);
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
            children: [
              const _Label('Our story'),
              Text('A beach stay the way it should be', style: _serif(26)),
              const SizedBox(height: 10),
              Text(about['story']?.toString() ?? '', style: _copy),
              const SizedBox(height: 8),
              Text('${resort['address'] ?? AppConfig.location} · ${site['tour_scenes'] ?? 0} tour scenes', style: _copy),
              const SizedBox(height: 14),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton(onPressed: onRooms, child: const Text('See the rooms')),
                  OutlinedButton(onPressed: onCottages, child: const Text('See the cottages')),
                ],
              ),
              const SizedBox(height: 22),
              _InfoCard(icon: Icons.visibility_outlined, title: 'Vision', body: about['vision']?.toString() ?? ''),
              _InfoCard(icon: Icons.explore_outlined, title: 'Mission', body: about['mission']?.toString() ?? ''),
              const SizedBox(height: 8),
              const _Label('Gallery'),
              Text('Around the resort', style: _serif(26)),
              const SizedBox(height: 10),
              if (gallery.isEmpty)
                const Text('Photos of the views and attractions will appear here once the resort adds them.', style: _copy)
              else
                for (final photo in gallery) ...[
                  if (photo['image'] != null)
                    ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: Image.network(photo['image'].toString(), height: 180, width: double.infinity, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const SizedBox.shrink()),
                    ),
                  if ((photo['title'] ?? '').toString().isNotEmpty)
                    Padding(padding: const EdgeInsets.only(top: 6), child: Text(photo['title'].toString(), style: const TextStyle(fontWeight: FontWeight.w800))),
                  if ((photo['caption'] ?? '').toString().isNotEmpty)
                    Text(photo['caption'].toString(), style: _copy),
                  const SizedBox(height: 14),
                ],
            ],
          );
        },
      ),
    );
  }
}

class ActivitiesPage extends StatelessWidget {
  const ActivitiesPage({super.key, required this.repository, required this.onBook});
  final ResortRepository repository;
  final VoidCallback onBook;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Activities')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: repository.site(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
          final activities = _maps(snapshot.data!['activities']);
          if (activities.isEmpty) {
            return const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('Activities will appear here as soon as the resort adds them.')));
          }
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const _Label('Things to do'),
              Text('Activities', style: _serif(28)),
              const SizedBox(height: 8),
              const Text('Snorkeling, island hopping, and other activities you can add to a room or cottage booking.', style: _copy),
              const SizedBox(height: 14),
              for (final activity in activities) _ActivityCard(activity: activity, onAdd: activity['is_bookable'] == true ? onBook : null, full: true),
            ],
          );
        },
      ),
    );
  }
}

class ContactPage extends StatelessWidget {
  const ContactPage({super.key, required this.repository, required this.onTour});
  final ResortRepository repository;
  final VoidCallback onTour;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Contact')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: repository.site(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
          final resort = snapshot.data!['resort'] is Map ? Map<String, dynamic>.from(snapshot.data!['resort'] as Map) : <String, dynamic>{};
          final address = resort['address']?.toString() ?? AppConfig.location;
          final phone = resort['phone']?.toString() ?? '';
          final email = resort['email']?.toString() ?? '';
          final map = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent('${resort['name'] ?? AppConfig.resortName}, $address')}');
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              const _Label('Contact details'),
              Text('Reach to us', style: _serif(28)),
              const SizedBox(height: 8),
              const Text('The front desk answers during opening hours. To reserve a room, a cottage, or the whole resort, use Booking.', style: _copy),
              const SizedBox(height: 14),
              _ContactRow(icon: Icons.place_outlined, title: 'Address', value: address, onTap: () => launchUrl(map, mode: LaunchMode.externalApplication)),
              if (phone.isNotEmpty) _ContactRow(icon: Icons.phone_outlined, title: 'Phone', value: phone, onTap: () => launchUrl(Uri.parse('tel:${phone.replaceAll(RegExp(r'[^0-9+]'), '')}'))),
              if (email.isNotEmpty) _ContactRow(icon: Icons.mail_outline, title: 'Email', value: email, onTap: () => launchUrl(Uri.parse('mailto:$email'))),
              _ContactRow(icon: Icons.schedule, title: 'Open daily', value: '${resort['opens'] ?? '6:00 AM'} – ${resort['closes'] ?? '10:00 PM'}\nHourly stays start between 8:00 AM and 8:00 PM'),
              const _ContactRow(icon: Icons.account_balance_wallet_outlined, title: 'Payments', value: 'GCash, card, or cash on arrival. The GCash QR code is shown at checkout.'),
              const SizedBox(height: 12),
              const _Label('Find us'),
              Text('How to get here', style: _serif(26)),
              const SizedBox(height: 8),
              Text('The resort is in $address. Tricycles and habal-habal from Baybay City proper know the way to Maslug beach.', style: _copy),
              const SizedBox(height: 12),
              OutlinedButton.icon(onPressed: onTour, icon: const Icon(Icons.threesixty), label: const Text('Open the 360° tour')),
              const SizedBox(height: 8),
              FilledButton.icon(
                onPressed: () => launchUrl(map, mode: LaunchMode.externalApplication),
                icon: const Icon(Icons.map_outlined),
                label: const Text('Open in Google Maps'),
              ),
            ],
          );
        },
      ),
    );
  }
}

class PublicReviewsPage extends StatelessWidget {
  const PublicReviewsPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reviews')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: repository.site(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) return const Center(child: CircularProgressIndicator());
          final reviews = _maps(snapshot.data!['reviews']);
          final average = snapshot.data!['average_rating'];
          final count = snapshot.data!['review_count'];
          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              const _Label('Testimonials'),
              Text("Guest's Opinions", style: _serif(28)),
              const SizedBox(height: 8),
              Text('${average ?? 0} / 5 from $count ${count == 1 ? 'guest' : 'guests'}', style: const TextStyle(fontWeight: FontWeight.w800, color: ResortTheme.ocean800)),
              const SizedBox(height: 14),
              if (reviews.isEmpty) const Text('Guest reviews will appear here after a stay.', style: _copy),
              for (final review in reviews) _ReviewCard(review: review),
            ],
          );
        },
      ),
    );
  }
}

class _Hero extends StatelessWidget {
  const _Hero({required this.onBook, required this.onTour, required this.onExclusive});
  final VoidCallback onBook;
  final VoidCallback onTour;
  final VoidCallback onExclusive;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      color: ResortTheme.ocean900,
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('BAYBAY CITY, LEYTE', style: TextStyle(color: ResortTheme.sand, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1.4)),
          const SizedBox(height: 10),
          Text(AppConfig.resortName, style: const TextStyle(color: Colors.white, fontFamily: 'serif', fontSize: 32, fontWeight: FontWeight.w700, height: 1.05)),
          const Text('Your shore, your stay.', style: TextStyle(color: Colors.white, fontFamily: 'serif', fontSize: 26, fontStyle: FontStyle.italic, height: 1.15)),
          const SizedBox(height: 10),
          const Text(
            'Rooms and cottages by the beach, a 360° look at the grounds before you arrive, and booking by the night or by the hour with GCash, card, or cash on arrival.',
            style: TextStyle(color: Color(0xFFE0F2FE), height: 1.4, fontSize: 13),
          ),
          const SizedBox(height: 16),
          FilledButton.icon(onPressed: onBook, icon: const Icon(Icons.calendar_month), label: const Text('Book a stay')),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white54)),
            onPressed: onTour,
            icon: const Icon(Icons.threesixty),
            label: const Text('Open 360° tour'),
          ),
          const SizedBox(height: 8),
          TextButton(onPressed: onExclusive, child: const Text('Exclusive full resort', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800))),
        ],
      ),
    );
  }
}

class _Block extends StatelessWidget {
  const _Block({required this.label, required this.title, required this.body, this.action, this.onAction, this.extra});
  final String label;
  final String title;
  final String body;
  final String? action;
  final VoidCallback? onAction;
  final Widget? extra;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _Label(label),
          Text(title, style: _serif(26)),
          const SizedBox(height: 8),
          Text(body, style: _copy),
          if (extra != null) ...[const SizedBox(height: 12), extra!],
          if (action != null) ...[
            const SizedBox(height: 12),
            FilledButton(onPressed: onAction, style: FilledButton.styleFrom(backgroundColor: ResortTheme.ocean900, foregroundColor: Colors.white), child: Text(action!)),
          ],
        ],
      ),
    );
  }
}

class _UnitStrip extends StatelessWidget {
  const _UnitStrip({required this.label, required this.title, required this.units, required this.onSeeAll, required this.onUnit});
  final String label;
  final String title;
  final List<Map<String, dynamic>> units;
  final VoidCallback onSeeAll;
  final void Function(Map<String, dynamic> unit) onUnit;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 22),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Label(label),
                Text(title, style: _serif(26)),
              ],
            ),
          ),
          const SizedBox(height: 12),
          if (units.isEmpty)
            const Padding(padding: EdgeInsets.symmetric(horizontal: 20), child: Text('These will appear here as soon as the resort adds them.', style: _copy))
          else
            SizedBox(
              height: 228,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: units.length,
                separatorBuilder: (_, __) => const SizedBox(width: 12),
                itemBuilder: (context, index) {
                  final unit = units[index];
                  final images = unit['images'];
                  final image = images is List && images.isNotEmpty ? images.first.toString() : null;
                  return SizedBox(
                    width: 240,
                    child: Material(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      child: InkWell(
                        borderRadius: BorderRadius.circular(16),
                        onTap: () => onUnit(unit),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ClipRRect(
                              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                              child: image == null
                                  ? const ColoredBox(color: ResortTheme.ocean900, child: SizedBox(height: 120, width: double.infinity))
                                  : Image.network(image, height: 120, width: double.infinity, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: ResortTheme.ocean900, child: SizedBox(height: 120, width: double.infinity))),
                            ),
                            Padding(
                              padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('${_kind(unit)} · up to ${unit['max_occupancy']} guests', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, color: ResortTheme.muted)),
                                  Text(unit['unit_number']?.toString() ?? 'Stay', style: const TextStyle(fontWeight: FontWeight.w800)),
                                  Text('₱${_plain(unit['price_per_night'])} / night · ₱${_plain(unit['hourly_rate'])} / hour', style: const TextStyle(fontSize: 12, color: ResortTheme.ocean800, fontWeight: FontWeight.w700)),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
            child: TextButton(onPressed: onSeeAll, child: Text('See all ${title.toLowerCase()}')),
          ),
        ],
      ),
    );
  }
}

class _ActivityCard extends StatelessWidget {
  const _ActivityCard({required this.activity, this.onAdd, this.full = false});
  final Map<String, dynamic> activity;
  final VoidCallback? onAdd;
  final bool full;

  @override
  Widget build(BuildContext context) {
    final image = activity['image']?.toString();
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFD7E6F0))),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (image != null && image.isNotEmpty)
            Image.network(image, height: 140, width: double.infinity, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const SizedBox.shrink()),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(activity['title']?.toString() ?? 'Activity', style: const TextStyle(fontFamily: 'serif', fontSize: 20, fontWeight: FontWeight.w700)),
                if ((activity['description'] ?? '').toString().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(activity['description'].toString(), maxLines: full ? 6 : 3, overflow: TextOverflow.ellipsis, style: _copy),
                ],
                const SizedBox(height: 6),
                Text([
                  activity['schedule'],
                  activity['duration'],
                  activity['max_guests'] == null ? null : 'Up to ${activity['max_guests']} guests',
                  activity['price'] == null ? null : '₱${_plain(activity['price'])}',
                ].where((part) => part != null && part.toString().isNotEmpty).join(' · '), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: ResortTheme.ocean800)),
                if (onAdd != null) ...[
                  const SizedBox(height: 10),
                  OutlinedButton(onPressed: onAdd, child: const Text('Add to a booking')),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Arrival extends StatelessWidget {
  const _Arrival({required this.resort});
  final Map<String, dynamic> resort;

  @override
  Widget build(BuildContext context) {
    final late = resort['late_refund'] ?? 50;
    final same = resort['same_day'] ?? 0;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _Label('Convenience'),
          Text('Arrival and Registration at the Resort', style: _serif(26)),
          const SizedBox(height: 12),
          _InfoCard(icon: Icons.receipt_long_outlined, title: 'Bring your booking reference', body: 'Show your reservation reference and your GCash receipt or payment confirmation at reception. My Bookings keeps both on your phone.'),
          _InfoCard(icon: Icons.schedule, title: 'Opening hours', body: 'The resort welcomes guests from ${resort['opens'] ?? '6:00 AM'} to ${resort['closes'] ?? '10:00 PM'} daily. Hourly stays can start between 8:00 AM and 8:00 PM and run from 1 to 12 hours.'),
          const _InfoCard(icon: Icons.account_balance_wallet_outlined, title: 'Payment at the resort', body: 'Pay online with GCash or a card, either in full or with a 50% down payment, and settle the balance at the desk. Cash on arrival is paid in full when you check in.'),
          _InfoCard(icon: Icons.undo, title: 'Changes and cancellations', body: 'Cancel 48 hours or more before your stay for a full refund of a confirmed payment. Between 24 and 48 hours, $late% is refunded. Within 24 hours, ${same == 0 ? 'the payment is no longer refundable' : '$same% is refunded'}.'),
        ],
      ),
    );
  }
}

class _ReviewStrip extends StatelessWidget {
  const _ReviewStrip({required this.reviews, required this.average, required this.count, required this.onSeeAll});
  final List<Map<String, dynamic>> reviews;
  final Object? average;
  final Object? count;
  final VoidCallback onSeeAll;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _Label('Testimonials'),
          Text("Guest's Opinions", style: _serif(26)),
          const SizedBox(height: 6),
          Text('${average ?? 0} / 5 from ${count ?? 0} guests', style: const TextStyle(fontWeight: FontWeight.w800, color: ResortTheme.ocean800)),
          const SizedBox(height: 10),
          if (reviews.isEmpty) const Text('Guest reviews will appear here after a stay.', style: _copy),
          for (final review in reviews.take(3)) _ReviewCard(review: review),
          TextButton(onPressed: onSeeAll, child: const Text('See all reviews')),
        ],
      ),
    );
  }
}

class _ContactStrip extends StatelessWidget {
  const _ContactStrip({required this.resort, required this.onOpen});
  final Map<String, dynamic> resort;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 12, 20, 0),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: ResortTheme.ocean900, borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Contact', style: TextStyle(color: ResortTheme.sand, fontWeight: FontWeight.w800, letterSpacing: 1)),
          const SizedBox(height: 6),
          Text(resort['address']?.toString() ?? AppConfig.location, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          if ((resort['phone'] ?? '').toString().isNotEmpty) Text(resort['phone'].toString(), style: const TextStyle(color: Color(0xFFE0F2FE))),
          if ((resort['email'] ?? '').toString().isNotEmpty) Text(resort['email'].toString(), style: const TextStyle(color: Color(0xFFE0F2FE))),
          const SizedBox(height: 8),
          TextButton(onPressed: onOpen, child: const Text('Contact details', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800))),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.icon, required this.title, required this.body});
  final IconData icon;
  final String title;
  final String body;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: const Color(0xFFD7E6F0))),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: ResortTheme.ocean600),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                Text(body, style: _copy),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ReviewCard extends StatelessWidget {
  const _ReviewCard({required this.review});
  final Map<String, dynamic> review;

  @override
  Widget build(BuildContext context) {
    final stars = (review['rating'] as num?)?.toInt() ?? 0;
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: const Color(0xFFD7E6F0))),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('${review['name'] ?? 'Resort guest'} · ${'★' * stars}${'☆' * (5 - stars)}', style: const TextStyle(fontWeight: FontWeight.w800)),
          if ((review['when'] ?? '').toString().isNotEmpty) Text(review['when'].toString(), style: const TextStyle(fontSize: 11, color: ResortTheme.muted)),
          const SizedBox(height: 6),
          Text(review['comment']?.toString() ?? '', style: _copy),
        ],
      ),
    );
  }
}

class _ContactRow extends StatelessWidget {
  const _ContactRow({required this.icon, required this.title, required this.value, this.onTap});
  final IconData icon;
  final String title;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(icon, color: ResortTheme.ocean600),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
      subtitle: Text(value),
      onTap: onTap,
    );
  }
}

class _Label extends StatelessWidget {
  const _Label(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(text.toUpperCase(), style: const TextStyle(color: ResortTheme.ocean600, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1.2)),
    );
  }
}

const _copy = TextStyle(color: Color(0xFF475569), height: 1.4, fontSize: 14);

TextStyle _serif(double size) => TextStyle(fontFamily: 'serif', fontSize: size, fontWeight: FontWeight.w700, color: ResortTheme.inkDeep, height: 1.15);

List<Map<String, dynamic>> _maps(Object? value) {
  if (value is! List) return [];
  return value.whereType<Map>().map((row) => Map<String, dynamic>.from(row)).toList();
}

String _kind(Map<String, dynamic> unit) {
  final variant = unit['variant']?.toString() == 'premium' ? 'Deluxe' : (unit['variant']?.toString() == 'normal' ? 'Normal' : (unit['variant']?.toString() ?? ''));
  final type = unit['unit_type']?.toString() == 'cottage' ? 'Cottage' : 'Room';
  return '$type · $variant';
}

String _plain(Object? value) {
  final number = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
  return number.toStringAsFixed(0);
}
