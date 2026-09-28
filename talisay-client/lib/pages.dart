import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:talisay_client/config.dart';
import 'package:talisay_client/repository.dart';
import 'package:talisay_client/theme.dart';
import 'package:url_launcher/url_launcher.dart';

final _money = NumberFormat.currency(symbol: '₱', decimalDigits: 2);
final _day = DateFormat('MMM d, y');
final _memberSince = DateFormat('MMMM d, y');

Future<void> openResortTour(BuildContext context, {String? videoUrl}) async {
  final target = (videoUrl != null && videoUrl.isNotEmpty) ? videoUrl : '${AppConfig.apiBase}${AppConfig.tourPath}';
  final opened = await launchUrl(Uri.parse(target), mode: LaunchMode.externalApplication);
  if (!opened && context.mounted) {
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('The virtual tour could not be opened.')));
  }
}

class HomePage extends StatefulWidget {
  const HomePage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  late Future<List<Map<String, dynamic>>> _units = widget.repository.units();
  late Future<List<Map<String, dynamic>>> _trips = widget.repository.bookings();
  late Future<List<Map<String, dynamic>>> _reviews = widget.repository.reviews();

  @override
  Widget build(BuildContext context) {
    final name = widget.repository.user?['name']?.toString() ?? 'Guest';
    return Scaffold(
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(AppConfig.resortName, style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800, height: 1.1)),
            Text('GUEST WEB PORTAL', style: TextStyle(color: ResortTheme.sky200, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 0.8)),
          ],
        ),
        actions: [
          IconButton(
            onPressed: () => _open(context, NotificationsPage(repository: widget.repository)),
            icon: const Icon(Icons.notifications_none),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          setState(() {
            _units = widget.repository.units();
            _trips = widget.repository.bookings();
            _reviews = widget.repository.reviews();
          });
          await Future.wait([_units, _trips, _reviews]);
        },
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            Container(
              width: double.infinity,
              color: ResortTheme.ocean900,
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white24),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.wb_sunny_outlined, size: 14, color: Color(0xFFFBBF24)),
                        SizedBox(width: 6),
                        Text('Welcome to Paradise', style: TextStyle(color: ResortTheme.sky200, fontSize: 12, fontWeight: FontWeight.w700)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  Text('Hello, $name!', style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800, height: 1.1)),
                  const SizedBox(height: 8),
                  const Text(
                    'Manage your resort bookings, browse rooms & cottages, explore 360° virtual tours, and enjoy an unforgettable beach experience.',
                    style: TextStyle(color: Color(0xFFE0F2FE), height: 1.4, fontSize: 13),
                  ),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: ResortTheme.ocean900),
                    onPressed: () => _open(context, StaysPage(repository: widget.repository)),
                    icon: const Icon(Icons.apartment, size: 18),
                    label: const Text('Browse Room & Cottage'),
                  ),
                  const SizedBox(height: 8),
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.white,
                      side: const BorderSide(color: Colors.white38),
                      backgroundColor: const Color(0x330EA5E9),
                    ),
                    onPressed: () => _openTour(context),
                    icon: const Icon(Icons.videocam_outlined, size: 18),
                    label: const Text('360° Virtual Tour'),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
              child: FutureBuilder(
                future: Future.wait([_trips, _reviews]),
                builder: (context, snapshot) {
                  final trips = snapshot.data == null ? <Map<String, dynamic>>[] : snapshot.data![0];
                  final reviews = snapshot.data == null ? <Map<String, dynamic>>[] : snapshot.data![1];
                  final active = trips.where((trip) => ['pending', 'paid', 'checked_in'].contains(trip['status'])).length;
                  final completed = trips.where((trip) => ['checked_out', 'completed'].contains(trip['status'])).length;
                  return GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    childAspectRatio: 1.7,
                    children: [
                      _StatCard(icon: Icons.event_available, tint: ResortTheme.ocean50, color: ResortTheme.ocean600, label: 'ACTIVE STAYS', value: '$active', onTap: () => _open(context, TripsPage(repository: widget.repository))),
                      _StatCard(icon: Icons.check_circle_outline, tint: const Color(0xFFECFDF5), color: const Color(0xFF059669), label: 'COMPLETED', value: '$completed'),
                      _StatCard(icon: Icons.star, tint: const Color(0xFFFFFBEB), color: const Color(0xFFD97706), label: 'MY REVIEWS', value: '${reviews.length}', onTap: () => _open(context, ReviewsPage(repository: widget.repository))),
                      _StatCard(icon: Icons.videocam_outlined, tint: ResortTheme.ocean50, color: ResortTheme.ocean600, label: 'VIRTUAL TOUR', value: '360° Explore', onTap: () => _openTour(context)),
                    ],
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 22, 16, 8),
              child: Row(
                children: [
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Featured Rooms & Cottages', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: ResortTheme.ink)),
                        SizedBox(height: 2),
                        Text('Choose your accommodation and reserve your stay online instantly', style: TextStyle(fontSize: 12, color: ResortTheme.muted)),
                      ],
                    ),
                  ),
                  TextButton(onPressed: () => _open(context, StaysPage(repository: widget.repository)), child: const Text('View all')),
                ],
              ),
            ),
            FutureBuilder<List<Map<String, dynamic>>>(
              future: _units,
              builder: (context, snapshot) {
                final units = (snapshot.data ?? []).take(4).toList();
                if (snapshot.connectionState != ConnectionState.done) {
                  return const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator()));
                }
                if (units.isEmpty) return const Padding(padding: EdgeInsets.all(16), child: Text('No rooms or cottages are listed yet.'));
                return Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  child: Column(
                    children: [
                      for (final unit in units) ...[
                        UnitPoster(
                          repository: widget.repository,
                          unit: unit,
                          onTap: () => _open(context, StayDetailPage(repository: widget.repository, unit: unit)),
                        ),
                        const SizedBox(height: 14),
                      ],
                    ],
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _open(BuildContext context, Widget page) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => page));
  }

  Future<void> _openTour(BuildContext context) async {
    final opened = await launchUrl(Uri.parse('${AppConfig.apiBase}${AppConfig.tourPath}'), mode: LaunchMode.externalApplication);
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('The virtual tour could not be opened.')));
    }
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.icon, required this.tint, required this.color, required this.label, required this.value, this.onTap});
  final IconData icon;
  final Color tint;
  final Color color;
  final String label;
  final String value;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFF1F5F9)),
          ),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(color: tint, borderRadius: BorderRadius.circular(10)),
                child: Icon(icon, color: color, size: 18),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 0.4, color: ResortTheme.muted)),
                    Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: ResortTheme.ink)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class UnitPoster extends StatelessWidget {
  const UnitPoster({super.key, required this.repository, required this.unit, required this.onTap});
  final ResortRepository repository;
  final Map<String, dynamic> unit;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final premium = unit['variant']?.toString() == 'premium';
    final images = unit['images'];
    final image = images is List && images.isNotEmpty ? images.first.toString() : null;
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFF1F5F9)),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              SizedBox(
                height: 168,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    if (image != null)
                      Image.network(image, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: ResortTheme.ocean900))
                    else
                      const ColoredBox(
                        color: ResortTheme.ocean900,
                        child: Icon(Icons.door_front_door_outlined, color: Colors.white, size: 36),
                      ),
                    const DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [Color(0x00000000), Color(0x990F172A)],
                        ),
                      ),
                    ),
                    Positioned(
                      top: 12,
                      left: 12,
                      child: Row(
                        children: [
                          _Badge(text: (unit['variant'] ?? '').toString().toUpperCase(), color: premium ? const Color(0xFFF59E0B) : ResortTheme.ocean600),
                          const SizedBox(width: 6),
                          _Badge(text: (unit['unit_type'] ?? '').toString().toUpperCase(), color: const Color(0xCC0F172A)),
                        ],
                      ),
                    ),
                    Positioned(
                      top: 12,
                      right: 12,
                      child: _Badge(
                        text: unit['is_available'] == true ? 'Available' : 'Booked',
                        color: unit['is_available'] == true ? const Color(0xFF059669) : const Color(0xFFDC2626),
                      ),
                    ),
                    Positioned(
                      left: 12,
                      bottom: 12,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(color: Colors.black54, borderRadius: BorderRadius.circular(8)),
                        child: const Text('360° Video Tour', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
                      ),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(child: Text(unit['unit_number']?.toString() ?? 'Stay', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800))),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text('Rate', style: TextStyle(fontSize: 11, color: ResortTheme.muted, fontWeight: FontWeight.w700)),
                            Text(_money.format(_number(unit['price_per_night'])), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: ResortTheme.ocean800)),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      unit['description']?.toString().isNotEmpty == true ? unit['description'].toString() : 'Beach stay at Talisay Beach Resort.',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12, color: ResortTheme.muted, height: 1.35),
                    ),
                    const SizedBox(height: 10),
                    _UnitFacts(unit: unit),
                    if (unit['amenities'] is List && (unit['amenities'] as List).isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          for (final item in (unit['amenities'] as List).take(3))
                            Chip(label: Text(item.toString()), visualDensity: VisualDensity.compact, materialTapTargetSize: MaterialTapTargetSize.shrinkWrap),
                          if ((unit['amenities'] as List).length > 3)
                            Text('+${(unit['amenities'] as List).length - 3} more', style: const TextStyle(fontSize: 11, color: ResortTheme.muted, fontWeight: FontWeight.w700)),
                        ],
                      ),
                    ],
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(child: OutlinedButton(onPressed: onTap, child: const Text('Details & 360°'))),
                        const SizedBox(width: 8),
                        Expanded(
                          child: FilledButton(
                            onPressed: unit['is_available'] == true
                                ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => BookPage(repository: repository, unit: unit)))
                                : null,
                            child: const Text('Book Now'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({required this.text, required this.color});
  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(20)),
      child: Text(text, style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 0.3)),
    );
  }
}

class _UnitFacts extends StatelessWidget {
  const _UnitFacts({required this.unit});
  final Map<String, dynamic> unit;

  @override
  Widget build(BuildContext context) {
    final area = unit['floor_area_sqm'];
    final bed = unit['bed_configuration']?.toString();
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFF1F5F9)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Max ${unit['max_occupancy']} guests', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
          if (area != null) Text('$area sqm', style: const TextStyle(fontSize: 12, color: ResortTheme.ink)),
          if (bed != null && bed.isNotEmpty) Text(bed, style: const TextStyle(fontSize: 12, color: ResortTheme.ink)),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.selected, required this.onTap, this.amber = false});
  final String label;
  final bool selected;
  final VoidCallback onTap;
  final bool amber;

  @override
  Widget build(BuildContext context) {
    final color = amber ? const Color(0xFFF59E0B) : ResortTheme.ocean600;
    return Material(
      color: selected ? color : const Color(0xFFF1F5F9),
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          child: Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: selected ? Colors.white : ResortTheme.ink)),
        ),
      ),
    );
  }
}

class StaysPage extends StatefulWidget {
  const StaysPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<StaysPage> createState() => _StaysPageState();
}

class _StaysPageState extends State<StaysPage> {
  late Future<List<Map<String, dynamic>>> _units = widget.repository.units();
  String _type = 'all';
  String _variant = 'all';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Rooms & Cottages')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _units,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(snapshot.error.toString())));
          }
          if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
          final all = snapshot.data ?? [];
          final units = all.where((unit) {
            final typeOk = _type == 'all' || unit['unit_type'] == _type;
            final variantOk = _variant == 'all' || unit['variant'] == _variant;
            return typeOk && variantOk;
          }).toList();
          final rooms = all.where((unit) => unit['unit_type'] == 'room').length;
          final cottages = all.where((unit) => unit['unit_type'] == 'cottage').length;
          return Column(
            children: [
              Container(
                width: double.infinity,
                color: ResortTheme.ocean900,
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('${all.length} TOTAL RESORT UNITS', style: const TextStyle(color: ResortTheme.sky200, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.6)),
                    const SizedBox(height: 8),
                    const Text('Rooms & Beachfront Cottages', style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800, height: 1.1)),
                    const SizedBox(height: 8),
                    Text('Explore $rooms rooms and $cottages cottages, including normal and premium units.', style: const TextStyle(color: Color(0xFFE0F2FE), fontSize: 13, height: 1.35)),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white38)),
                      onPressed: () => openResortTour(context),
                      icon: const Icon(Icons.videocam_outlined, size: 18),
                      label: const Text('Full 360° Virtual Tour'),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      _FilterChip(label: 'All (${all.length})', selected: _type == 'all' && _variant == 'all', onTap: () => setState(() { _type = 'all'; _variant = 'all'; })),
                      _FilterChip(label: 'Rooms ($rooms)', selected: _type == 'room' && _variant == 'all', onTap: () => setState(() { _type = 'room'; _variant = 'all'; })),
                      _FilterChip(label: 'Cottages ($cottages)', selected: _type == 'cottage' && _variant == 'all', onTap: () => setState(() { _type = 'cottage'; _variant = 'all'; })),
                      _FilterChip(label: 'Normal', selected: _variant == 'normal' && _type == 'all', onTap: () => setState(() { _type = 'all'; _variant = 'normal'; })),
                      _FilterChip(label: 'Premium', selected: _variant == 'premium' && _type == 'all', amber: true, onTap: () => setState(() { _type = 'all'; _variant = 'premium'; })),
                      _FilterChip(label: 'Premium rooms', selected: _type == 'room' && _variant == 'premium', amber: true, onTap: () => setState(() { _type = 'room'; _variant = 'premium'; })),
                      _FilterChip(label: 'Premium cottages', selected: _type == 'cottage' && _variant == 'premium', amber: true, onTap: () => setState(() { _type = 'cottage'; _variant = 'premium'; })),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                child: Align(alignment: Alignment.centerLeft, child: Text('Showing ${units.length} units', style: const TextStyle(fontSize: 12, color: ResortTheme.muted, fontWeight: FontWeight.w700))),
              ),
              Expanded(child: units.isEmpty
                  ? const Center(child: Text('No rooms or cottages are listed yet.'))
                  : RefreshIndicator(
            onRefresh: () async => setState(() => _units = widget.repository.units()),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: units.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final unit = units[index];
                return UnitPoster(
                  repository: widget.repository,
                  unit: unit,
                  onTap: () => Navigator.push(context, MaterialPageRoute(
                    builder: (_) => StayDetailPage(repository: widget.repository, unit: unit),
                  )),
                );
              },
            ),
          )),
            ],
          );
        },
      ),
    );
  }
}

class StayDetailPage extends StatelessWidget {
  const StayDetailPage({super.key, required this.repository, required this.unit});
  final ResortRepository repository;
  final Map<String, dynamic> unit;

  @override
  Widget build(BuildContext context) {
    final images = unit['images'];
    final premium = unit['variant']?.toString() == 'premium';
    final kind = '${_title(unit['variant'])} ${_title(unit['unit_type'])}'.trim();
    final amenities = unit['amenities'] is List ? unit['amenities'] as List : const [];
    return Scaffold(
      appBar: AppBar(title: Text(unit['unit_number']?.toString() ?? 'Stay')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (images is List && images.isNotEmpty)
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Image.network(
                images.first.toString(),
                height: 200,
                width: double.infinity,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => const SizedBox(height: 160, child: ColoredBox(color: ResortTheme.ocean900)),
              ),
            ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _Badge(text: kind.toUpperCase(), color: premium ? const Color(0xFFF59E0B) : ResortTheme.ocean600),
              _Badge(text: unit['is_available'] == true ? 'Available for booking' : 'Currently reserved', color: unit['is_available'] == true ? const Color(0xFF059669) : const Color(0xFFDC2626)),
            ],
          ),
          const SizedBox(height: 10),
          Text(unit['unit_number']?.toString() ?? 'Stay', style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text('Nightly rate  ${_money.format(_number(unit['price_per_night']))}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: ResortTheme.ocean900)),
          const Text('Tax and resort access included', style: TextStyle(fontSize: 12, color: ResortTheme.muted)),
          const SizedBox(height: 16),
          const Text('360° virtual tour', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          const Text('Walk through this unit and the resort before you reserve.', style: TextStyle(color: ResortTheme.muted, fontSize: 13)),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: () => openResortTour(context, videoUrl: unit['tour_video_url']?.toString()),
            icon: const Icon(Icons.videocam_outlined),
            label: const Text('Open 360° tour'),
          ),
          const SizedBox(height: 18),
          const Text('Overview', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text(unit['description']?.toString().isNotEmpty == true ? unit['description'].toString() : 'No description has been added yet.', style: const TextStyle(color: ResortTheme.muted, height: 1.4)),
          const SizedBox(height: 14),
          const Text('UNIT SPECIFICATIONS', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5, color: ResortTheme.muted)),
          const SizedBox(height: 8),
          _Spec(label: 'Max occupancy', value: '${unit['max_occupancy']} guests'),
          _Spec(label: 'Floor area', value: unit['floor_area_sqm'] == null ? 'Spacious layout' : '${unit['floor_area_sqm']} sqm'),
          _Spec(label: 'Bed configuration', value: unit['bed_configuration']?.toString().isNotEmpty == true ? unit['bed_configuration'].toString() : 'Comfortable setup'),
          const _Spec(label: 'Location', value: AppConfig.location),
          if (amenities.isNotEmpty) ...[
            const SizedBox(height: 14),
            const Text('INCLUDED AMENITIES', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5, color: ResortTheme.muted)),
            const SizedBox(height: 8),
            for (final item in amenities)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Text('•  $item', style: const TextStyle(fontWeight: FontWeight.w600)),
              ),
          ],
          const SizedBox(height: 12),
          _Spec(label: 'Base rate', value: _money.format(_number(unit['price_per_night']))),
          const _Spec(label: 'Resort and beach access', value: 'Free'),
          const _Spec(label: '360° orientation', value: 'Included'),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: unit['is_available'] == true
                ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => BookPage(repository: repository, unit: unit)))
                : null,
            child: Text('Reserve ${unit['unit_number'] ?? 'this stay'}'),
          ),
        ],
      ),
    );
  }
}

class _Spec extends StatelessWidget {
  const _Spec({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFF1F5F9)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 11, color: ResortTheme.muted, fontWeight: FontWeight.w700)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }
}

String _title(Object? value) {
  final text = value?.toString() ?? '';
  if (text.isEmpty) return '';
  return text[0].toUpperCase() + text.substring(1);
}

class BookPage extends StatefulWidget {
  const BookPage({super.key, required this.repository, required this.unit});
  final ResortRepository repository;
  final Map<String, dynamic> unit;

  @override
  State<BookPage> createState() => _BookPageState();
}

class _BookPageState extends State<BookPage> {
  DateTime? _checkIn;
  DateTime? _checkOut;
  int _guests = 1;
  String _method = 'gcash';
  final _notes = TextEditingController();
  bool _busy = false;
  bool _checking = false;
  Map<String, dynamic>? _quote;

  @override
  void dispose() {
    _notes.dispose();
    super.dispose();
  }

  Future<void> _pick(bool checkIn) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: now.add(const Duration(days: 365)),
      initialDate: checkIn ? (_checkIn ?? now) : (_checkOut ?? now.add(const Duration(days: 1))),
    );
    if (picked == null) return;
    setState(() {
      if (checkIn) {
        _checkIn = picked;
      } else {
        _checkOut = picked;
      }
      _quote = null;
    });
  }

  Future<void> _checkDates() async {
    if (_checkIn == null || _checkOut == null) return;
    setState(() => _checking = true);
    try {
      final quote = await widget.repository.checkStay(
        unitId: (widget.unit['id'] as num).toInt(),
        checkIn: _checkIn!,
        checkOut: _checkOut!,
      );
      setState(() => _quote = quote);
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  Future<void> _submit() async {
    if (_checkIn == null || _checkOut == null || _quote?['available'] != true) return;
    setState(() => _busy = true);
    try {
      final result = await widget.repository.requestBooking(
        unitId: (widget.unit['id'] as num).toInt(),
        checkIn: _checkIn!,
        checkOut: _checkOut!,
        guests: _guests,
        paymentMethod: _method,
        notes: _notes.text,
      );
      final booking = result['booking'] is Map ? Map<String, dynamic>.from(result['booking'] as Map) : result;
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Reservation received'),
          content: Text('Reference ${booking['reference_no']}. Complete ${_method.toUpperCase()} payment so the resort can confirm the stay.'),
          actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close'))],
        ),
      );
      if (mounted) Navigator.pop(context);
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final nights = (_checkIn != null && _checkOut != null) ? _checkOut!.difference(_checkIn!).inDays : 0;
    final total = nights > 0 ? nights * _number(widget.unit['price_per_night']) : 0.0;
    return Scaffold(
      appBar: AppBar(title: const Text('Reserve')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(widget.unit['unit_number']?.toString() ?? '', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
          const SizedBox(height: 16),
          OutlinedButton(onPressed: () => _pick(true), child: Text(_checkIn == null ? 'Check in' : 'Check in ${_day.format(_checkIn!)}')),
          const SizedBox(height: 8),
          OutlinedButton(onPressed: () => _pick(false), child: Text(_checkOut == null ? 'Check out' : 'Check out ${_day.format(_checkOut!)}')),
          const SizedBox(height: 8),
          Row(
            children: [
              const Text('Guests'),
              const Spacer(),
              IconButton(onPressed: _guests > 1 ? () => setState(() => _guests--) : null, icon: const Icon(Icons.remove)),
              Text('$_guests'),
              IconButton(
                onPressed: _guests < (widget.unit['max_occupancy'] as num? ?? 10) ? () => setState(() => _guests++) : null,
                icon: const Icon(Icons.add),
              ),
            ],
          ),
          DropdownButtonFormField<String>(
            initialValue: _method,
            decoration: const InputDecoration(labelText: 'Payment'),
            items: const [
              DropdownMenuItem(value: 'gcash', child: Text('GCash')),
              DropdownMenuItem(value: 'cash', child: Text('Cash on arrival')),
              DropdownMenuItem(value: 'paypal', child: Text('PayPal')),
              DropdownMenuItem(value: 'card', child: Text('Card')),
            ],
            onChanged: (value) => setState(() => _method = value ?? 'gcash'),
          ),
          const SizedBox(height: 12),
          TextField(controller: _notes, maxLines: 3, decoration: const InputDecoration(labelText: 'Notes for the front desk')),
          const SizedBox(height: 16),
          Text(nights > 0 ? 'Amount due ${_money.format(total)} for $nights night(s)' : 'Choose both dates to see the amount.', style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          OutlinedButton(onPressed: _checking || nights < 1 ? null : _checkDates, child: Text(_checking ? 'Checking dates' : 'Check these dates')),
          if (_quote != null) ...[
            const SizedBox(height: 8),
            Text(_quote!['message']?.toString() ?? '', style: TextStyle(fontWeight: FontWeight.w700, color: _quote!['available'] == true ? const Color(0xFF047857) : const Color(0xFFB91C1C))),
          ],
          const SizedBox(height: 8),
          const Text('Payment stays pending until staff verify it.', style: TextStyle(color: ResortTheme.muted)),
          const SizedBox(height: 16),
          FilledButton(onPressed: _busy || _quote?['available'] != true ? null : _submit, child: Text(_busy ? 'Sending' : 'Send reservation')),
        ],
      ),
    );
  }
}

class TripsPage extends StatefulWidget {
  const TripsPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<TripsPage> createState() => _TripsPageState();
}

class _TripsPageState extends State<TripsPage> {
  late Future<List<Map<String, dynamic>>> _trips = widget.repository.bookings();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Bookings')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _trips,
        builder: (context, snapshot) {
          if (snapshot.hasError) return Center(child: Text(snapshot.error.toString()));
          if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
          final trips = snapshot.data ?? [];
          if (trips.isEmpty) return const Center(child: Text('You have no reservations yet.'));
          return RefreshIndicator(
            onRefresh: () async => setState(() => _trips = widget.repository.bookings()),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: trips.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final trip = trips[index];
                final unit = trip['accommodation_unit'] is Map ? trip['accommodation_unit']['unit_number'] : 'Stay';
                final payment = trip['payment'] is Map ? trip['payment']['status'] : 'unpaid';
                return Card(
                  child: ListTile(
                    title: Text(trip['reference_no']?.toString() ?? 'Reservation'),
                    subtitle: Text('$unit\n${_stayDate(trip['check_in_date'] ?? trip['booking_date'])} to ${_stayDate(trip['check_out_date'])}\nStay: ${trip['status']} · Payment: $payment'),
                    isThreeLine: true,
                    trailing: Text(_money.format(_number(trip['total_amount']))),
                    onTap: () async {
                      final changed = await Navigator.push<bool>(context, MaterialPageRoute(
                        builder: (_) => TripPage(repository: widget.repository, trip: trip),
                      ));
                      if (changed == true && mounted) setState(() => _trips = widget.repository.bookings());
                    },
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class TripPage extends StatefulWidget {
  const TripPage({super.key, required this.repository, required this.trip});
  final ResortRepository repository;
  final Map<String, dynamic> trip;

  @override
  State<TripPage> createState() => _TripPageState();
}

class _TripPageState extends State<TripPage> {
  final _reason = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _cancel() async {
    setState(() => _busy = true);
    try {
      await widget.repository.cancelBooking((widget.trip['id'] as num).toInt(), _reason.text.trim());
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _proof(int paymentId) async {
    final photo = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (photo == null) return;
    setState(() => _busy = true);
    try {
      await widget.repository.uploadProof(paymentId: paymentId, filePath: photo.path);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Receipt sent. Staff will verify the payment.')));
        Navigator.pop(context, true);
      }
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final trip = widget.trip;
    final unit = trip['accommodation_unit'] is Map ? trip['accommodation_unit']['unit_number']?.toString() : 'Stay';
    final payment = trip['payment'] is Map ? Map<String, dynamic>.from(trip['payment'] as Map) : null;
    final status = trip['status']?.toString() ?? '';
    final method = payment?['payment_method']?.toString() ?? '';
    final canCancel = status == 'pending' || status == 'paid';
    final needsProof = payment != null && method != 'cash' && payment['status'] != 'success' && status != 'cancelled';

    return Scaffold(
      appBar: AppBar(title: Text(trip['reference_no']?.toString() ?? 'Reservation')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(unit ?? 'Stay', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Text('${_stayDate(trip['check_in_date'] ?? trip['booking_date'])} to ${_stayDate(trip['check_out_date'])}'),
          const SizedBox(height: 8),
          Text('${trip['guests_count']} guests · ${_money.format(_number(trip['total_amount']))}'),
          const SizedBox(height: 8),
          Text('Stay: $status'),
          Text('Payment: ${payment?['status'] ?? 'unpaid'}${method.isEmpty ? '' : ' · ${method.toUpperCase()}'}'),
          if (method == 'cash') ...[
            const SizedBox(height: 12),
            const Text('Pay at the front desk and show this reference number.', style: TextStyle(color: ResortTheme.muted)),
          ],
          if (needsProof) ...[
            const SizedBox(height: 16),
            const Text('Upload a photo of your GCash, PayPal, or card receipt. Staff confirm the payment on the resort system.', style: TextStyle(color: ResortTheme.muted)),
            const SizedBox(height: 12),
            FilledButton(onPressed: _busy ? null : () => _proof((payment['id'] as num).toInt()), child: const Text('Upload receipt')),
          ],
          if (canCancel) ...[
            const SizedBox(height: 20),
            TextField(controller: _reason, decoration: const InputDecoration(labelText: 'Reason for cancelling')),
            const SizedBox(height: 12),
            OutlinedButton(onPressed: _busy ? null : _cancel, child: const Text('Cancel reservation')),
          ],
        ],
      ),
    );
  }
}

class AccountPage extends StatefulWidget {
  const AccountPage({super.key, required this.repository, required this.onSignedOut});
  final ResortRepository repository;
  final VoidCallback onSignedOut;

  @override
  State<AccountPage> createState() => _AccountPageState();
}

class _AccountPageState extends State<AccountPage> {
  late final TextEditingController _name;
  late final TextEditingController _email;
  late final TextEditingController _phone;
  final _currentPassword = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    final user = widget.repository.user;
    _name = TextEditingController(text: user?['name']?.toString() ?? '');
    _email = TextEditingController(text: user?['email']?.toString() ?? '');
    _phone = TextEditingController(text: user?['phone']?.toString() ?? '');
    _refresh();
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _currentPassword.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    try {
      final user = await widget.repository.me();
      if (!mounted) return;
      setState(() {
        _name.text = user['name']?.toString() ?? _name.text;
        _email.text = user['email']?.toString() ?? _email.text;
        _phone.text = user['phone']?.toString() ?? '';
      });
    } catch (_) {}
  }

  Future<void> _save({String? avatarPath}) async {
    setState(() => _busy = true);
    try {
      await widget.repository.saveProfile(
        name: _name.text.trim(),
        email: _email.text.trim(),
        phone: _phone.text.trim(),
        avatarPath: avatarPath,
      );
      if (mounted) {
        setState(() {});
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated.')));
      }
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _changePassword() async {
    if (_password.text != _confirm.text) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('The new passwords do not match.')));
      return;
    }
    setState(() => _busy = true);
    try {
      await widget.repository.updatePassword(currentPassword: _currentPassword.text, password: _password.text);
      _currentPassword.clear();
      _password.clear();
      _confirm.clear();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Password updated.')));
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = widget.repository.user ?? {};
    final name = user['name']?.toString() ?? 'Guest';
    final initial = name.isEmpty ? 'G' : name[0].toUpperCase();
    final avatar = user['avatar_url']?.toString();
    final created = DateTime.tryParse(user['created_at']?.toString() ?? '');
    final active = user['is_active'] != false && user['account_status'] != 'inactive' && user['account_status'] != 'suspended';
    return Scaffold(
      appBar: AppBar(title: const Text('My Profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('ACCOUNT PROFILE', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.6, color: ResortTheme.ocean800)),
          const SizedBox(height: 4),
          const Text('My Profile Settings', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          const Text('Manage your name, phone, photo, and password.', style: TextStyle(color: ResortTheme.muted)),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 36,
                    backgroundColor: ResortTheme.ocean600,
                    backgroundImage: avatar != null && avatar.isNotEmpty ? NetworkImage(avatar) : null,
                    child: avatar == null || avatar.isEmpty ? Text(initial, style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800)) : null,
                  ),
                  const SizedBox(height: 10),
                  Text(name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                  Text(user['email']?.toString() ?? '', style: const TextStyle(color: ResortTheme.muted, fontSize: 12)),
                  const SizedBox(height: 8),
                  const _Badge(text: 'TOURIST GUEST', color: ResortTheme.ocean600),
                  const SizedBox(height: 14),
                  _Spec(label: 'Phone number', value: (user['phone']?.toString().isNotEmpty == true) ? user['phone'].toString() : 'Not set'),
                  _Spec(label: 'Account status', value: active ? 'Active' : 'Inactive'),
                  _Spec(label: 'Member since', value: created == null ? '—' : _memberSince.format(created)),
                  OutlinedButton(
                    onPressed: _busy
                        ? null
                        : () async {
                            final photo = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
                            if (photo != null) await _save(avatarPath: photo.path);
                          },
                    child: const Text('Change photo'),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Text('Personal information', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          TextField(controller: _name, decoration: const InputDecoration(labelText: 'Full name')),
          const SizedBox(height: 8),
          TextField(controller: _email, decoration: const InputDecoration(labelText: 'Email address')),
          const SizedBox(height: 8),
          TextField(controller: _phone, decoration: const InputDecoration(labelText: 'Phone number')),
          const SizedBox(height: 12),
          FilledButton(onPressed: _busy ? null : () => _save(), child: const Text('Save profile details')),
          const SizedBox(height: 22),
          const Text('Security and password', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          TextField(controller: _currentPassword, obscureText: true, decoration: const InputDecoration(labelText: 'Current password')),
          const SizedBox(height: 8),
          TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'New password')),
          const SizedBox(height: 8),
          TextField(controller: _confirm, obscureText: true, decoration: const InputDecoration(labelText: 'Confirm new password')),
          const SizedBox(height: 12),
          OutlinedButton(onPressed: _busy ? null : _changePassword, child: const Text('Update password')),
          const SizedBox(height: 16),
          ListTile(
            leading: const Icon(Icons.star_outline),
            title: const Text('My Reviews'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ReviewsPage(repository: widget.repository))),
          ),
          ListTile(
            leading: const Icon(Icons.notifications_none),
            title: const Text('Alerts'),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => NotificationsPage(repository: widget.repository))),
          ),
          ListTile(
            leading: const Icon(Icons.logout),
            title: const Text('Log out'),
            onTap: () async {
              await widget.repository.signOut();
              widget.onSignedOut();
            },
          ),
        ],
      ),
    );
  }
}

class ReviewsPage extends StatefulWidget {
  const ReviewsPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<ReviewsPage> createState() => _ReviewsPageState();
}

class _ReviewsPageState extends State<ReviewsPage> {
  late Future<List<Map<String, dynamic>>> _reviews = widget.repository.reviews();
  late Future<List<Map<String, dynamic>>> _trips = widget.repository.bookings();
  int _rating = 5;
  int? _bookingId;
  final _comment = TextEditingController();

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    if (_bookingId == null || _comment.text.trim().isEmpty) return;
    try {
      await widget.repository.submitReview(bookingId: _bookingId!, rating: _rating, comment: _comment.text.trim());
      _comment.clear();
      setState(() => _reviews = widget.repository.reviews());
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reviews')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          FutureBuilder<List<Map<String, dynamic>>>(
            future: _trips,
            builder: (context, snapshot) {
              final trips = (snapshot.data ?? []).where((trip) => ['paid', 'checked_in', 'checked_out', 'completed'].contains(trip['status'])).toList();
              return DropdownButtonFormField<int>(
                initialValue: _bookingId,
                decoration: const InputDecoration(labelText: 'Stay'),
                items: trips.map((trip) => DropdownMenuItem(value: (trip['id'] as num).toInt(), child: Text(trip['reference_no']?.toString() ?? 'Stay'))).toList(),
                onChanged: (value) => setState(() => _bookingId = value),
              );
            },
          ),
          Row(
            children: List.generate(5, (index) {
              final filled = index < _rating;
              return IconButton(
                onPressed: () => setState(() => _rating = index + 1),
                icon: Icon(filled ? Icons.star : Icons.star_border, color: const Color(0xFFD97706)),
              );
            }),
          ),
          TextField(controller: _comment, maxLines: 3, decoration: const InputDecoration(labelText: 'Comment')),
          const SizedBox(height: 12),
          FilledButton(onPressed: _send, child: const Text('Submit review')),
          const SizedBox(height: 16),
          FutureBuilder<List<Map<String, dynamic>>>(
            future: _reviews,
            builder: (context, snapshot) {
              final reviews = snapshot.data ?? [];
              if (reviews.isEmpty) return const Text('No reviews yet.');
              return Column(
                children: reviews.map((review) {
                  final hidden = review['is_comment_blocked'] == true;
                  return ListTile(
                    leading: Icon(Icons.star, color: const Color(0xFFD97706)),
                    title: Text(hidden ? 'Comment hidden by the resort' : review['comment']?.toString() ?? ''),
                    subtitle: Text('${review['rating']} stars'),
                  );
                }).toList(),
              );
            },
          ),
        ],
      ),
    );
  }
}

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  late Future<List<Map<String, dynamic>>> _items = widget.repository.notifications();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Alerts')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _items,
        builder: (context, snapshot) {
          if (snapshot.hasError) return Center(child: Text(snapshot.error.toString()));
          final items = snapshot.data ?? [];
          if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
          if (items.isEmpty) return const Center(child: Text('No alerts yet.'));
          return ListView.builder(
            itemCount: items.length,
            itemBuilder: (context, index) {
              final item = items[index];
              return ListTile(
                title: Text(item['title']?.toString() ?? ''),
                subtitle: Text(item['message']?.toString() ?? item['body']?.toString() ?? ''),
              );
            },
          );
        },
      ),
    );
  }
}

class ChatPage extends StatefulWidget {
  const ChatPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<ChatPage> createState() => _ChatPageState();
}

class _ChatPageState extends State<ChatPage> {
  final _input = TextEditingController();
  final _messages = <Map<String, String>>[];
  bool _busy = false;

  @override
  void dispose() {
    _input.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty) return;
    setState(() {
      _busy = true;
      _messages.add({'role': 'user', 'text': text});
      _input.clear();
    });
    try {
      final reply = await widget.repository.chat(text);
      setState(() => _messages.add({'role': 'bot', 'text': reply}));
    } on ApiException catch (error) {
      setState(() => _messages.add({'role': 'bot', 'text': error.message}));
    } catch (_) {
      setState(() => _messages.add({'role': 'bot', 'text': 'Cannot reach the resort server at ${AppConfig.apiBase}. Stay on the same Wi-Fi as this computer.'}));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Resort assistant')),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _messages.length,
              itemBuilder: (context, index) {
                final message = _messages[index];
                final mine = message['role'] == 'user';
                return Align(
                  alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
                  child: Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.all(12),
                    constraints: const BoxConstraints(maxWidth: 280),
                    decoration: BoxDecoration(
                      color: mine ? ResortTheme.ocean600 : ResortTheme.ocean100,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: ResortTheme.line),
                    ),
                    child: Text(message['text'] ?? '', style: TextStyle(color: mine ? Colors.white : ResortTheme.ocean800, fontWeight: FontWeight.w600)),
                  ),
                );
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(child: TextField(controller: _input, decoration: const InputDecoration(hintText: 'Ask about rates, hours, or booking'))),
                IconButton(onPressed: _busy ? null : _send, icon: const Icon(Icons.send)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

double _number(Object? value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

String _stayDate(Object? value) {
  final text = value?.toString() ?? '';
  if (text.length >= 10) return text.substring(0, 10);
  return text.isEmpty ? '—' : text;
}
