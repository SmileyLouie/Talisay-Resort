import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:talisay_client/config.dart';
import 'package:talisay_client/repository.dart';
import 'package:talisay_client/site.dart';
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
  const HomePage({super.key, required this.repository, this.onProfile});
  final ResortRepository repository;
  final VoidCallback? onProfile;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  @override
  Widget build(BuildContext context) {
    return GuestLanding(
      repository: widget.repository,
      onNotify: () => _open(context, NotificationsPage(repository: widget.repository)),
      onUnit: (unit) => _open(context, StayDetailPage(repository: widget.repository, unit: unit)),
      onOpen: (section) => _openSection(context, section),
    );
  }

  void _open(BuildContext context, Widget page) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => page));
  }

  void _openSection(BuildContext context, String section) {
    switch (section) {
      case 'menu':
        _open(context, SiteMenuPage(onOpen: (next) => _openSection(context, next)));
      case 'about':
        _open(context, AboutPage(
          repository: widget.repository,
          onRooms: () => _openSection(context, 'rooms'),
          onCottages: () => _openSection(context, 'cottages'),
        ));
      case 'tour':
        openResortTour(context);
      case 'rooms':
        _open(context, StaysPage(repository: widget.repository, kind: 'room'));
      case 'cottages':
        _open(context, StaysPage(repository: widget.repository, kind: 'cottage'));
      case 'activities':
        _open(context, ActivitiesPage(repository: widget.repository, onBook: () => _openSection(context, 'rooms')));
      case 'exclusive':
        _open(context, SpecialResortPage(repository: widget.repository));
      case 'reviews':
        _open(context, PublicReviewsPage(repository: widget.repository));
      case 'contact':
        _open(context, ContactPage(repository: widget.repository, onTour: () => openResortTour(context)));
      case 'bookings':
        _open(context, TripsPage(repository: widget.repository));
      case 'profile':
        widget.onProfile?.call();
      case 'chat':
        _open(context, ChatPage(repository: widget.repository));
    }
  }
}

class UnitPoster extends StatelessWidget {
  const UnitPoster({super.key, required this.repository, required this.unit, required this.onTap, this.detailsLabel = 'Details & 360°'});
  final ResortRepository repository;
  final Map<String, dynamic> unit;
  final VoidCallback onTap;
  final String detailsLabel;

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
                          _Badge(text: _variantLabel(unit['variant']).toUpperCase(), color: premium ? const Color(0xFFF59E0B) : ResortTheme.ocean600),
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
                            Text('${_money.format(_shownHourly(unit))}/hour', style: const TextStyle(fontSize: 11, color: ResortTheme.muted, fontWeight: FontWeight.w700)),
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
                        Expanded(
                          child: OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              backgroundColor: const Color(0xFFF1F5F9),
                              foregroundColor: ResortTheme.ocean900,
                              side: const BorderSide(color: Color(0xFFE2E8F0)),
                              minimumSize: const Size(0, 40),
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                              textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
                            ),
                            onPressed: onTap,
                            icon: const Icon(Icons.visibility_outlined, size: 16),
                            label: Text(detailsLabel),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: FilledButton.icon(
                            style: FilledButton.styleFrom(
                              minimumSize: const Size(0, 40),
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                              textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800),
                            ),
                            onPressed: unit['is_available'] == true
                                ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => BookPage(repository: repository, unit: unit)))
                                : null,
                            icon: const Icon(Icons.calendar_month, size: 16),
                            label: const Text('Book Now'),
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
  const StaysPage({super.key, required this.repository, this.kind});
  final ResortRepository repository;
  final String? kind;

  @override
  State<StaysPage> createState() => _StaysPageState();
}

class _StaysPageState extends State<StaysPage> {
  late Future<List<Map<String, dynamic>>> _units = widget.repository.units();
  late String _type = widget.kind ?? 'all';
  String _variant = 'all';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_type == 'room' ? 'Our Rooms' : _type == 'cottage' ? 'Our Cottages' : 'Rooms & Cottages')),
      body: FutureBuilder<List<Map<String, dynamic>>>(
        future: _units,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(snapshot.error.toString())));
          }
          if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
          final all = snapshot.data ?? [];
          final listed = all.where((unit) => unit['is_available'] == true).toList();
          final units = listed.where((unit) {
            final typeOk = _type == 'all' || unit['unit_type'] == _type;
            final variantOk = _variant == 'all' || unit['variant'] == _variant;
            return typeOk && variantOk;
          }).toList();
          final rooms = listed.where((unit) => unit['unit_type'] == 'room').length;
          final cottages = listed.where((unit) => unit['unit_type'] == 'cottage').length;
          final inCategory = _type == 'room'
              ? listed.where((unit) => unit['unit_type'] == 'room')
              : _type == 'cottage'
                  ? listed.where((unit) => unit['unit_type'] == 'cottage')
                  : listed;
          final standard = inCategory.where((unit) => unit['variant'] == 'normal').length;
          final deluxe = inCategory.where((unit) => unit['variant'] == 'premium').length;
          final scope = _type == 'room' ? 'For rooms' : _type == 'cottage' ? 'For cottages' : 'For all units';
          return Column(
            children: [
              Container(
                width: double.infinity,
                color: ResortTheme.ocean900,
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('${listed.length} TOTAL RESORT UNITS', style: const TextStyle(color: ResortTheme.sky200, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.6)),
                    const SizedBox(height: 8),
                    const Text('Rooms & Beachfront Cottages', style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800, height: 1.1)),
                    const SizedBox(height: 8),
                    Text('Explore $rooms rooms and $cottages cottages, including standard and deluxe units.', style: const TextStyle(color: Color(0xFFE0F2FE), fontSize: 13, height: 1.35)),
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
                      _FilterChip(label: 'All Room & Cottage (${listed.length})', selected: _type == 'all', onTap: () => setState(() => _type = 'all')),
                      _FilterChip(label: 'Rooms ($rooms)', selected: _type == 'room', onTap: () => setState(() => _type = 'room')),
                      _FilterChip(label: 'Cottages ($cottages)', selected: _type == 'cottage', onTap: () => setState(() => _type = 'cottage')),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(scope, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: ResortTheme.muted)),
                      const SizedBox(height: 6),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _FilterChip(label: 'Normal ($standard)', selected: _variant == 'normal', onTap: () => setState(() => _variant = _variant == 'normal' ? 'all' : 'normal')),
                          _FilterChip(label: 'Deluxe ($deluxe)', selected: _variant == 'premium', amber: true, onTap: () => setState(() => _variant = _variant == 'premium' ? 'all' : 'premium')),
                        ],
                      ),
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
    final kind = '${_variantLabel(unit['variant'])} ${_title(unit['unit_type'])}'.trim();
    final amenities = unit['amenities'] is List ? unit['amenities'] as List : const [];
    return Scaffold(
      appBar: AppBar(title: Text(unit['unit_number']?.toString() ?? 'Stay')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (images is List && images.isNotEmpty)
            SizedBox(
              height: 220,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: images.length,
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemBuilder: (context, index) => ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Image.network(
                    images[index].toString(),
                    height: 220,
                    width: 320,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const SizedBox(height: 220, width: 320, child: ColoredBox(color: ResortTheme.ocean900)),
                  ),
                ),
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
          Text('Hourly rate  ${_money.format(_shownHourly(unit))}  ·  1 to 12 hours', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: ResortTheme.ocean800)),
          const Text('Tax and resort access included', style: TextStyle(fontSize: 12, color: ResortTheme.muted)),
          const SizedBox(height: 16),
          const Text('360° virtual tour', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          const Text('Walk through this unit and the resort before you reserve.', style: TextStyle(color: ResortTheme.muted, fontSize: 13)),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: () => openResortTour(context, videoUrl: unit['tour_video_url']?.toString()),
            icon: const Icon(Icons.play_circle_outline),
            label: const Text('Launch 360° Video Simulation'),
          ),
          const SizedBox(height: 18),
          const Text('Overview', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text(unit['description']?.toString().isNotEmpty == true ? unit['description'].toString() : 'No description has been added yet.', style: const TextStyle(color: ResortTheme.muted, height: 1.4)),
          const SizedBox(height: 14),
          const Text('UNIT SPECIFICATIONS', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5, color: ResortTheme.muted)),
          const SizedBox(height: 8),
          _Spec(label: 'Max occupancy', value: '${unit['max_occupancy']} guests'),
          if ((unit['bed_configuration'] ?? '').toString().isNotEmpty) _Spec(label: 'Beds', value: unit['bed_configuration'].toString()),
          _Spec(label: 'Floor area', value: unit['floor_area_sqm'] == null ? 'Spacious layout' : '${unit['floor_area_sqm']} sqm'),
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
          const SizedBox(height: 14),
          const Text('GOOD TO KNOW', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.5, color: ResortTheme.muted)),
          const SizedBox(height: 8),
          const Text('Open daily. Hourly stays start between 8:00 AM and 8:00 PM and last 1 to 12 hours.', style: TextStyle(color: ResortTheme.muted, height: 1.4)),
          const SizedBox(height: 4),
          const Text('Pay with GCash or card in full or with a down payment, or pay cash in full on arrival.', style: TextStyle(color: ResortTheme.muted, height: 1.4)),
          const SizedBox(height: 4),
          const Text('Full refund 48 hours before the stay. Show your booking reference and payment confirmation at reception.', style: TextStyle(color: ResortTheme.muted, height: 1.4)),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: unit['is_available'] == true
                ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => BookPage(repository: repository, unit: unit)))
                : null,
            child: Text('Reserve ${unit['unit_number'] ?? 'this stay'} Now'),
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

String _variantLabel(Object? value) {
  switch (value?.toString()) {
    case 'premium':
      return 'Deluxe';
    case 'normal':
      return 'Normal';
    default:
      return _title(value);
  }
}

String _title(Object? value) {
  final text = value?.toString() ?? '';
  if (text.isEmpty) return '';
  return text[0].toUpperCase() + text.substring(1);
}

class ResortPayDraft {
  String method = 'cash';
  String kind = 'cash';
  String plan = 'full';
  String gcashMethod = 'qr';
  final reference = TextEditingController();
  final cardLastFourField = TextEditingController();
  String? proofPath;
  String? proofName;
  List<Map<String, dynamic>> methods = const [];
  int downPercent = 50;

  void dispose() {
    reference.dispose();
    cardLastFourField.dispose();
  }

  void applyInfo(Map<String, dynamic> info) {
    final percent = info['down_payment_percent'];
    if (percent is num) downPercent = percent.toInt().clamp(0, 100);
    if (!allowsDown) plan = 'full';
    applyMethods(info['methods'] is List ? info['methods'] as List : const []);
  }

  void applyMethods(List<dynamic> rows) {
    methods = rows.whereType<Map>().map((row) => Map<String, dynamic>.from(row)).toList();
    if (methods.isEmpty) return;
    Map<String, dynamic>? current;
    for (final row in methods) {
      if (row['code']?.toString() == method) current = row;
    }
    final chosen = current ?? methods.first;
    method = chosen['code']?.toString() ?? method;
    kind = chosen['kind']?.toString() ?? kind;
  }

  void choose(Map<String, dynamic> row) {
    method = row['code']?.toString() ?? method;
    kind = row['kind']?.toString() ?? 'ewallet';
  }

  Map<String, dynamic>? get selected {
    for (final row in methods) {
      if (row['code']?.toString() == method) return row;
    }
    return null;
  }

  bool get online => kind != 'cash';

  bool get allowsDown => online && downPercent > 0 && downPercent < 100;

  double dueFor(double total) {
    if (allowsDown && plan == 'down') return (total * downPercent).roundToDouble() / 100;
    return total;
  }

  bool get referenceReady => RegExp(r'^\d{13}$').hasMatch(reference.text.replaceAll(RegExp(r'\D'), ''));

  bool get receiptReady => proofPath != null && proofPath!.isNotEmpty;

  String? validate() {
    if (needsProofUpload) {
      if (method == 'gcash' && !referenceReady) return 'The transaction reference must be 13 digits, like 1234 567 890123.';
      if (method != 'gcash' && reference.text.trim().length < 6) return 'Enter the transaction reference from your receipt.';
      if (!receiptReady) return 'Upload the payment receipt screenshot.';
    }
    if (kind == 'card' && !RegExp(r'^\d{4}$').hasMatch(cardLastFourField.text.trim())) {
      return 'Enter the last 4 digits of the card.';
    }
    return null;
  }

  String? lastFour() {
    if (kind != 'card') return null;
    final digits = cardLastFourField.text.replaceAll(RegExp(r'\D'), '');
    return digits.length == 4 ? digits : null;
  }

  bool get needsProofUpload => kind == 'ewallet' || kind == 'bank' || kind == 'online';
}

class ResortPaymentFields extends StatelessWidget {
  const ResortPaymentFields({super.key, required this.draft, required this.stayTotal, required this.onChanged, required this.onPickProof, this.gcashName = '', this.gcashNumber = ''});
  final ResortPayDraft draft;
  final double stayTotal;
  final VoidCallback onChanged;
  final VoidCallback onPickProof;
  final String gcashName;
  final String gcashNumber;

  @override
  Widget build(BuildContext context) {
    final due = draft.dueFor(stayTotal);
    final balance = ((stayTotal - due) * 100).roundToDouble() / 100;
    final name = gcashName.trim().isEmpty ? 'Talisay Beach Resort' : gcashName.trim();
    final number = gcashNumber.trim();
    final qrText = [
      'Talisay Beach Resort',
      'GCash: ${number.isEmpty ? 'Not set' : number}',
      'Name: $name',
      'Amount: PHP ${due.toStringAsFixed(2)}',
      due < stayTotal ? 'Down payment ${draft.downPercent}%' : 'Full payment',
      'This amount is fixed.',
    ].join('\n');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Select Payment Channel', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: ResortTheme.ink)),
        const SizedBox(height: 8),
        ..._methodChoices(),
        if (draft.allowsDown) ...[
          const SizedBox(height: 12),
          const Text('How much to pay now', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(child: _PayChoice(label: 'Full payment', selected: draft.plan == 'full', onTap: () { draft.plan = 'full'; onChanged(); })),
              const SizedBox(width: 8),
              Expanded(child: _PayChoice(label: 'Down payment (${draft.downPercent}%)', selected: draft.plan == 'down', onTap: () { draft.plan = 'down'; onChanged(); })),
            ],
          ),
          const SizedBox(height: 8),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFF1F5F9))),
            child: Text('Pay now: ${_money.format(due)}\nBalance at the resort: ${_money.format(balance)}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
          ),
        ],
        if (draft.needsProofUpload) ...[
          const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFBAE6FD))),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(draft.method == 'gcash' ? 'GCash QR for the fixed amount' : '${draft.selected?['name'] ?? 'Online payment'} account', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF0C4A6E))),
                  if (draft.method == 'gcash') ...[
                    const SizedBox(height: 12),
                    Center(
                      child: Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFE2E8F0))),
                        child: QrImageView(data: qrText, size: 180, backgroundColor: Colors.white),
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  const Text('FIXED AMOUNT', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 0.4)),
                  Text(_money.format(due), style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: ResortTheme.ocean900)),
                  const SizedBox(height: 8),
                  Text(draft.method == 'gcash' ? 'GCASH NUMBER' : 'ACCOUNT', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 0.4)),
                  Text(_accountNumber.isEmpty ? 'Not set in File Maintenance' : _accountNumber, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Color(0xFF075985))),
                  Text(_accountName, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 6),
                  Text(draft.method == 'gcash' ? 'Scan this code, or send the amount to the number above. Then upload the receipt.' : 'Send the amount to the account above, then upload the receipt.', style: const TextStyle(fontSize: 11, height: 1.35, color: ResortTheme.muted)),
                ],
              ),
            ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: ResortTheme.line)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Payment proof for the admin', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                const Text('Both are required before the reservation can be sent.', style: TextStyle(fontSize: 11, color: ResortTheme.muted)),
                const SizedBox(height: 10),
                TextField(
                  controller: draft.reference,
                  maxLength: draft.method == 'gcash' ? 16 : 40,
                  keyboardType: draft.method == 'gcash' ? TextInputType.number : TextInputType.text,
                  onChanged: (_) => onChanged(),
                  decoration: const InputDecoration(
                    label: Text.rich(TextSpan(children: [
                      TextSpan(text: 'Transaction reference number '),
                      TextSpan(text: '*', style: TextStyle(color: Color(0xFFDC2626), fontWeight: FontWeight.w800)),
                    ])),
                    hintText: '1234 567 890123',
                    counterText: '',
                  ),
                ),
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: onPickProof,
                  icon: const Icon(Icons.upload_file),
                  label: Text.rich(TextSpan(children: [
                    TextSpan(text: draft.proofName ?? 'Receipt screenshot '),
                    if (draft.proofName == null) const TextSpan(text: '*', style: TextStyle(color: Color(0xFFDC2626), fontWeight: FontWeight.w800)),
                  ])),
                ),
              ],
            ),
          ),
        ],
        if (draft.kind == 'card') ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: const Color(0xFF020617), borderRadius: BorderRadius.circular(16)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(draft.selected?['name']?.toString() ?? 'Card payment', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                const Text('The resort keeps only the last 4 digits. Do not enter the full card number.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                const SizedBox(height: 10),
                TextField(
                  controller: draft.cardLastFourField,
                  keyboardType: TextInputType.number,
                  maxLength: 4,
                  style: const TextStyle(color: Colors.white),
                  onChanged: (_) => onChanged(),
                  decoration: const InputDecoration(
                    counterText: '',
                    label: Text.rich(TextSpan(children: [
                      TextSpan(text: 'Last 4 digits '),
                      TextSpan(text: '*', style: TextStyle(color: Color(0xFFDC2626), fontWeight: FontWeight.w800)),
                    ])),
                    hintText: '1234',
                    labelStyle: TextStyle(color: Color(0xFFCBD5E1)),
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  String get _accountName {
    final saved = draft.selected?['account_name']?.toString().trim() ?? '';
    if (saved.isNotEmpty) return saved;
    if (draft.method == 'gcash' && gcashName.trim().isNotEmpty) return gcashName.trim();
    return 'Talisay Beach Resort';
  }

  String get _accountNumber {
    final saved = draft.selected?['account_number']?.toString().trim() ?? '';
    if (saved.isNotEmpty) return saved;
    if (draft.method == 'gcash') return gcashNumber.trim();
    return '';
  }

  List<Widget> _methodChoices() {
    final rows = draft.methods.isEmpty
        ? [
            {'code': 'gcash', 'name': 'GCash', 'kind': 'ewallet'},
            {'code': 'cash', 'name': 'Cash on arrival', 'kind': 'cash'},
            {'code': 'card', 'name': 'Card', 'kind': 'card'},
          ]
        : draft.methods;
    return [
      for (final row in rows) ...[
        _PayChoice(
          label: row['name']?.toString() ?? 'Payment',
          selected: draft.method == row['code']?.toString(),
          onTap: () {
            draft.choose(Map<String, dynamic>.from(row));
            onChanged();
          },
        ),
        const SizedBox(height: 8),
      ],
    ];
  }
}

class _PayChoice extends StatelessWidget {
  const _PayChoice({required this.label, required this.selected, required this.onTap});
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? const Color(0xFFF0F9FF) : Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: selected ? const Color(0xFF0284C7) : const Color(0xFFE2E8F0)),
          ),
          child: Row(
            children: [
              Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off, size: 18, color: const Color(0xFF0284C7)),
              const SizedBox(width: 8),
              Expanded(child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800))),
            ],
          ),
        ),
      ),
    );
  }
}

class BookPage extends StatefulWidget {
  const BookPage({super.key, required this.repository, required this.unit});
  final ResortRepository repository;
  final Map<String, dynamic> unit;

  @override
  State<BookPage> createState() => _BookPageState();
}

class _BookPageState extends State<BookPage> {
  String _stay = 'night';
  DateTime? _checkIn;
  DateTime? _checkOut;
  int _hourStart = 14;
  final _hours = TextEditingController(text: '1');
  final _guests = TextEditingController(text: '1');
  final _notes = TextEditingController();
  final _pay = ResortPayDraft();
  bool _busy = false;
  bool _checking = false;
  Map<String, dynamic>? _quote;
  String _gcashName = '';
  String _gcashNumber = '';
  bool _hourlyAllowed = true;
  List<Map<String, dynamic>> _activities = [];
  final Set<int> _activityIds = {};

  double get _nightly => _number(widget.unit['price_per_night']);
  double get _hourRate => widget.unit['hourly_rate'] == null ? _hourlyRate(widget.unit['price_per_night']) : _number(widget.unit['hourly_rate']);
  int get _maxGuests => (widget.unit['max_occupancy'] as num?)?.toInt() ?? 10;
  int get _guestCount => int.tryParse(_guests.text.trim()) ?? 0;
  int get _hourCount => int.tryParse(_hours.text.trim()) ?? 0;
  bool get _hourly => _stay == 'hour';
  bool get _guestsAllowed => _guestCount >= 1 && _guestCount <= _maxGuests;
  int get _nights => (_checkIn != null && _checkOut != null) ? _checkOut!.difference(_checkIn!).inDays : 0;
  double get _activityExtra => _activities.where((row) => _activityIds.contains((row['id'] as num?)?.toInt())).fold(0, (sum, row) => sum + _number(row['price']));
  double get _total => (_hourly ? (_hourCount >= 1 && _hourCount <= 12 ? _hourRate * _hourCount : 0) : (_nights > 0 ? _nights * _nightly : 0)) + _activityExtra;

  @override
  void initState() {
    super.initState();
    widget.repository.paymentInfo().then((info) {
      if (!mounted) return;
      setState(() {
        _gcashName = info['gcash_name']?.toString() ?? '';
        _gcashNumber = info['gcash_number']?.toString() ?? '';
        _pay.applyInfo(info);
        _hourlyAllowed = info['hourly_stays_enabled'] != false;
        if (!_hourlyAllowed) _stay = 'night';
        final rows = info['activities'];
        _activities = rows is List ? rows.whereType<Map>().map((row) => Map<String, dynamic>.from(row)).toList() : [];
      });
    }).catchError((_) {});
  }

  @override
  void dispose() {
    _hours.dispose();
    _guests.dispose();
    _notes.dispose();
    _pay.dispose();
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
    if (_checkIn == null || (!_hourly && _checkOut == null)) return;
    setState(() => _checking = true);
    try {
      final quote = await widget.repository.checkStay(
        unitId: (widget.unit['id'] as num).toInt(),
        checkIn: _checkIn!,
        checkOut: _hourly ? null : _checkOut,
        stayType: _stay,
        hours: _hourly ? _hourCount : null,
        hourStart: _hourly ? _clock(_hourStart) : null,
      );
      setState(() => _quote = quote);
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _checking = false);
    }
  }

  Future<void> _submit() async {
    if (_checkIn == null || _quote?['available'] != true || !_guestsAllowed) return;
    if (!_hourly && _checkOut == null) return;
    setState(() => _busy = true);
    try {
      final problem = _pay.validate();
      if (problem != null) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(problem)));
        return;
      }
      final result = await widget.repository.requestBooking(
        unitId: (widget.unit['id'] as num).toInt(),
        checkIn: _checkIn!,
        checkOut: _hourly ? null : _checkOut,
        guests: _guestCount,
        paymentMethod: _pay.method,
        stayType: _stay,
        hours: _hourly ? _hourCount : null,
        hourStart: _hourly ? _clock(_hourStart) : null,
        paymentPlan: _pay.online ? _pay.plan : 'full',
        gcashMethod: _pay.gcashMethod,
        notes: _notes.text,
        cardLastFour: _pay.lastFour(),
        activityIds: _activityIds.toList(),
      );
      final booking = result['booking'] is Map ? Map<String, dynamic>.from(result['booking'] as Map) : result;
      await _sendProof(booking);
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Reservation received'),
          content: Text('Reference ${booking['reference_no']}. The resort will confirm the stay after the payment is checked.'),
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

  Future<void> _pickProof() async {
    final photo = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (photo == null) return;
    setState(() {
      _pay.proofPath = photo.path;
      _pay.proofName = photo.name;
    });
  }

  Future<void> _sendProof(Map<String, dynamic> booking) async {
    if (!_pay.needsProofUpload) return;
    final payment = booking['payment'];
    if (payment is! Map || payment['id'] == null) return;
    await widget.repository.uploadProof(
      paymentId: (payment['id'] as num).toInt(),
      filePath: _pay.proofPath,
      referenceNumber: _pay.reference.text,
    );
  }

  @override
  Widget build(BuildContext context) {
    final ready = _hourly ? (_checkIn != null && _hourCount >= 1 && _hourCount <= 12) : _nights > 0;
    final stayLabel = _hourly
        ? '${_hourCount >= 1 && _hourCount <= 12 ? _hourCount : 0} hour${_hourCount == 1 ? '' : 's'}'
        : (_nights > 0 ? '$_nights night${_nights == 1 ? '' : 's'}' : '');
    return Scaffold(
      appBar: AppBar(title: Text('Reservation · ${widget.unit['unit_number'] ?? ''}')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('Book this room', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(child: _PayChoice(label: 'Per night', selected: !_hourly, onTap: () => setState(() { _stay = 'night'; _quote = null; }))),
              if (_hourlyAllowed) ...[
                const SizedBox(width: 8),
                Expanded(child: _PayChoice(label: 'Per hour', selected: _hourly, onTap: () => setState(() { _stay = 'hour'; _quote = null; }))),
              ],
            ],
          ),
          if (_hourly) ...[
            const SizedBox(height: 8),
            Text('The hourly rate is ${_money.format(_hourRate)}. A stay can be 1 to 12 hours.', style: const TextStyle(fontSize: 11, color: ResortTheme.muted)),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _DateBox(label: _hourly ? 'Stay date' : 'Check-in Date', value: _checkIn == null ? 'Select date' : _day.format(_checkIn!), onTap: () => _pick(true))),
              if (!_hourly) ...[
                const SizedBox(width: 10),
                Expanded(child: _DateBox(label: 'Check-out Date', value: _checkOut == null ? 'Select date' : _day.format(_checkOut!), onTap: () => _pick(false))),
              ],
            ],
          ),
          if (_hourly) ...[
            const SizedBox(height: 12),
            const Text('Start time', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            DropdownButtonFormField<int>(
              initialValue: _hourStart,
              items: [
                for (var hour = 8; hour <= 20; hour++)
                  DropdownMenuItem(value: hour, child: Text(_ampm(hour))),
              ],
              onChanged: (value) => setState(() { _hourStart = value ?? 14; _quote = null; }),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _hours,
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(labelText: 'Hours'),
              onChanged: (_) => setState(() => _quote = null),
            ),
          ],
          const SizedBox(height: 10),
          if (_checking) const Text('Checking date availability...', style: TextStyle(color: ResortTheme.muted, fontSize: 12)),
          if (_quote != null)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _quote!['available'] == true ? const Color(0xFFECFDF5) : const Color(0xFFFEF2F2),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: _quote!['available'] == true ? const Color(0xFFA7F3D0) : const Color(0xFFFECACA)),
              ),
              child: Text(_quote!['message']?.toString() ?? (_quote!['available'] == true ? 'Unit is available for your stay.' : 'These dates are not available.'), style: TextStyle(fontWeight: FontWeight.w700, color: _quote!['available'] == true ? const Color(0xFF065F46) : const Color(0xFF991B1B), fontSize: 12)),
            ),
          const SizedBox(height: 8),
          OutlinedButton(onPressed: _checking || !ready ? null : _checkDates, child: Text(_checking ? 'Checking dates' : 'Check availability')),
          const SizedBox(height: 12),
          Text('Number of Guests (Pax)', style: Theme.of(context).textTheme.labelLarge),
          const SizedBox(height: 6),
          TextField(
            controller: _guests,
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: const InputDecoration(labelText: 'Guests'),
            onChanged: (_) => setState(() {}),
          ),
          const SizedBox(height: 6),
          Text('This room or cottage allows up to $_maxGuests guests.', style: const TextStyle(fontSize: 11, color: ResortTheme.muted)),
          if (_guestCount > _maxGuests) ...[
            const SizedBox(height: 8),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(color: const Color(0xFFFFF1F2), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFFECDD3))),
              child: Text('This room only allows $_maxGuests guests.', style: const TextStyle(color: Color(0xFF9F1239), fontSize: 12, fontWeight: FontWeight.w800)),
            ),
          ],
          const SizedBox(height: 12),
          TextField(controller: _notes, maxLines: 2, decoration: const InputDecoration(labelText: 'Special Requests (optional)', hintText: 'e.g. Extra pillows, early arrival...')),
          if (_activities.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text('Add an activity', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            for (final activity in _activities)
              CheckboxListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                value: _activityIds.contains((activity['id'] as num?)?.toInt()),
                title: Text(activity['title']?.toString() ?? 'Activity', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800)),
                subtitle: Text('${activity['schedule'] ?? 'By arrangement'}${(activity['duration'] ?? '').toString().isEmpty ? '' : ' · ${activity['duration']}'}${activity['price'] == null ? '' : ' · ${_money.format(_number(activity['price']))}'}', style: const TextStyle(fontSize: 11)),
                onChanged: (checked) {
                  final id = (activity['id'] as num?)?.toInt();
                  if (id == null) return;
                  setState(() {
                    if (checked == true) {
                      _activityIds.add(id);
                    } else {
                      _activityIds.remove(id);
                    }
                  });
                },
              ),
          ],
          const SizedBox(height: 16),
          ResortPaymentFields(
            draft: _pay,
            stayTotal: _total,
            onChanged: () => setState(() {}),
            onPickProof: _pickProof,
            gcashName: _gcashName,
            gcashNumber: _gcashNumber,
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFF1F5F9))),
            child: Row(
              children: [
                Expanded(child: Text(stayLabel.isEmpty ? 'Total Stay Cost' : 'Total Stay Cost ($stayLabel)', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700))),
                Text(_total > 0 ? _money.format(_total) : '—', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: ResortTheme.ocean900)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: OutlinedButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel'))),
              const SizedBox(width: 10),
              Expanded(child: FilledButton(onPressed: _busy || _quote?['available'] != true || !_guestsAllowed || _pay.validate() != null ? null : _submit, child: Text(_busy ? 'Sending' : 'Confirm Booking'))),
            ],
          ),
        ],
      ),
    );
  }
}

class SpecialResortPage extends StatefulWidget {
  const SpecialResortPage({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<SpecialResortPage> createState() => _SpecialResortPageState();
}

class _SpecialResortPageState extends State<SpecialResortPage> {
  DateTime _checkIn = DateTime.now();
  DateTime _checkOut = DateTime.now().add(const Duration(days: 1));
  int _guests = 30;
  final _notes = TextEditingController();
  final _pay = ResortPayDraft();
  bool _busy = false;
  double _nightly = 0;
  int _unitCount = 0;
  String _gcashName = '';
  String _gcashNumber = '';
  double? _exclusiveRate;
  String _exclusiveTitle = 'Exclusive full resort';
  String _exclusiveDescription = '';
  String _exclusiveInformation = '';
  int _visitorCap = 100;
  List<String> _occasions = const [];
  String _occasion = '';
  List<Map<String, dynamic>> _activities = [];
  final Set<int> _activityIds = {};

  @override
  void initState() {
    super.initState();
    widget.repository.paymentInfo().then((info) {
      if (!mounted) return;
      setState(() {
        _gcashName = info['gcash_name']?.toString() ?? '';
        _gcashNumber = info['gcash_number']?.toString() ?? '';
        _pay.applyInfo(info);
        final rate = _number(info['exclusive_nightly_rate']);
        if (rate > 0) _exclusiveRate = rate;
        final title = info['exclusive_title']?.toString().trim() ?? '';
        if (title.isNotEmpty) _exclusiveTitle = title;
        _exclusiveDescription = info['exclusive_description']?.toString().trim() ?? '';
        _exclusiveInformation = info['exclusive_information']?.toString().trim() ?? '';
        final cap = _number(info['visitor_cap']).round();
        if (cap > 0) {
          _visitorCap = cap;
          if (_guests > cap) _guests = cap;
        }
        final occasions = info['occasions'];
        _occasions = occasions is List ? occasions.map((item) => item.toString()).where((item) => item.trim().isNotEmpty).toList() : [];
        final rows = info['activities'];
        _activities = rows is List ? rows.whereType<Map>().map((row) => Map<String, dynamic>.from(row)).toList() : [];
      });
    }).catchError((_) {});
    widget.repository.units().then((units) {
      if (!mounted) return;
      final open = units.where((unit) => unit['is_available'] == true).toList();
      final total = open.fold<double>(0, (sum, unit) => sum + _number(unit['price_per_night']));
      setState(() {
        _nightly = total;
        _unitCount = open.length;
      });
    }).catchError((_) {});
  }

  @override
  void dispose() {
    _notes.dispose();
    _pay.dispose();
    super.dispose();
  }

  Future<void> _pick(bool checkIn) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: now.add(const Duration(days: 365)),
      initialDate: checkIn ? _checkIn : _checkOut,
    );
    if (picked == null) return;
    setState(() {
      if (checkIn) {
        _checkIn = picked;
        if (!_checkOut.isAfter(_checkIn)) _checkOut = _checkIn.add(const Duration(days: 1));
      } else {
        _checkOut = picked;
      }
    });
  }

  Future<void> _pickProof() async {
    final photo = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (photo == null) return;
    setState(() {
      _pay.proofPath = photo.path;
      _pay.proofName = photo.name;
    });
  }

  Future<void> _submit() async {
    if (_notes.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tell us about the event so the resort can review the request.')));
      return;
    }
    final problem = _pay.validate();
    if (problem != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(problem)));
      return;
    }
    setState(() => _busy = true);
    try {
      final result = await widget.repository.requestSpecialResort(
        checkIn: _checkIn,
        checkOut: _checkOut,
        guests: _guests,
        paymentMethod: _pay.method,
        notes: _occasion.isEmpty ? _notes.text.trim() : 'Occasion: $_occasion\n\n${_notes.text.trim()}',
        cardLastFour: _pay.lastFour(),
        paymentPlan: _pay.online ? _pay.plan : 'full',
        gcashMethod: _pay.gcashMethod,
        activityIds: _activityIds.toList(),
      );
      final booking = result['booking'] is Map ? Map<String, dynamic>.from(result['booking'] as Map) : result;
      if (_pay.needsProofUpload) {
        final payment = booking['payment'];
        if (payment is Map && payment['id'] != null) {
          await widget.repository.uploadProof(
            paymentId: (payment['id'] as num).toInt(),
            filePath: _pay.proofPath,
            referenceNumber: _pay.reference.text,
          );
        }
      }
      if (!mounted) return;
      await showDialog<void>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Request sent'),
          content: Text('Exclusive booking ${booking['reference_no']} is waiting for management to confirm the full resort.'),
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
    final nights = _checkOut.difference(DateTime(_checkIn.year, _checkIn.month, _checkIn.day)).inDays;
    final safeNights = nights < 1 ? 1 : nights;
    final nightly = (_exclusiveRate != null && _exclusiveRate! > 0) ? _exclusiveRate! : _nightly;
    final activityExtra = _activities.where((row) => _activityIds.contains((row['id'] as num?)?.toInt())).fold<double>(0, (sum, row) => sum + _number(row['price']));
    final total = nightly * safeNights + activityExtra;
    return Scaffold(
      appBar: AppBar(title: Text(_exclusiveTitle)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(color: const Color(0xFFFEF3C7), borderRadius: BorderRadius.circular(6)),
            child: const Text('EXCLUSIVE RESERVATION', style: TextStyle(color: Color(0xFFB45309), fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 0.6)),
          ),
          const SizedBox(height: 8),
          Text(_exclusiveDescription.isEmpty
              ? 'Book the entire Talisay Beach Resort (${_unitCount == 1 ? '1 unit' : '$_unitCount units'}) for private events'
              : _exclusiveDescription, style: const TextStyle(color: ResortTheme.muted, fontSize: 12)),
          if (_exclusiveInformation.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(_exclusiveInformation, style: const TextStyle(fontSize: 13, height: 1.4)),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _DateBox(label: 'Check-in Date', value: _day.format(_checkIn), onTap: () => _pick(true))),
              const SizedBox(width: 10),
              Expanded(child: _DateBox(label: 'Check-out Date', value: _day.format(_checkOut), onTap: () => _pick(false))),
            ],
          ),
          const SizedBox(height: 12),
          Text('Estimated Guest Count (Up to $_visitorCap guests)', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
          Row(
            children: [
              IconButton(onPressed: _guests > 1 ? () => setState(() => _guests--) : null, icon: const Icon(Icons.remove_circle_outline)),
              Text('$_guests', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
              IconButton(onPressed: _guests < _visitorCap ? () => setState(() => _guests++) : null, icon: const Icon(Icons.add_circle_outline)),
            ],
          ),
          const SizedBox(height: 12),
          if (_occasions.isNotEmpty) ...[
            DropdownButtonFormField<String>(
              initialValue: _occasion.isEmpty ? null : _occasion,
              decoration: const InputDecoration(labelText: 'What is this booking for?'),
              items: [for (final choice in _occasions) DropdownMenuItem(value: choice, child: Text(choice))],
              onChanged: (value) => setState(() => _occasion = value ?? ''),
            ),
            const SizedBox(height: 12),
          ],
          TextField(
            controller: _notes,
            maxLines: 3,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(
              label: Text.rich(TextSpan(children: [
                TextSpan(text: 'Event details '),
                TextSpan(text: '*', style: TextStyle(color: Color(0xFFDC2626), fontWeight: FontWeight.w800)),
              ])),
              hintText: 'Schedule, setup, food, or anything the group needs.',
            ),
          ),
          if (_activities.isNotEmpty) ...[
            const SizedBox(height: 12),
            const Text('Add an activity', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
            for (final activity in _activities)
              CheckboxListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                value: _activityIds.contains((activity['id'] as num?)?.toInt()),
                title: Text(activity['title']?.toString() ?? 'Activity', style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800)),
                subtitle: Text('${activity['schedule'] ?? 'By arrangement'}${(activity['duration'] ?? '').toString().isEmpty ? '' : ' · ${activity['duration']}'}${activity['price'] == null ? '' : ' · ${_money.format(_number(activity['price']))}'}', style: const TextStyle(fontSize: 11)),
                onChanged: (checked) {
                  final id = (activity['id'] as num?)?.toInt();
                  if (id == null) return;
                  setState(() => checked == true ? _activityIds.add(id) : _activityIds.remove(id));
                },
              ),
          ],
          const SizedBox(height: 16),
          ResortPaymentFields(draft: _pay, stayTotal: total, onChanged: () => setState(() {}), onPickProof: _pickProof, gcashName: _gcashName, gcashNumber: _gcashNumber),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(color: const Color(0xFFFFFBEB), borderRadius: BorderRadius.circular(16), border: Border.all(color: const Color(0xFFFDE68A))),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Exclusive Rate ($safeNights Night${safeNights == 1 ? '' : 's'})', style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF78350F), fontSize: 12)),
                      Text('Includes all $_unitCount open ${_unitCount == 1 ? 'room or cottage' : 'rooms and cottages'}', style: const TextStyle(fontSize: 10, color: Color(0xFFB45309))),
                    ],
                  ),
                ),
                Text(_nightly == 0 ? '—' : _money.format(total), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: Color(0xFF78350F))),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: OutlinedButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel'))),
              const SizedBox(width: 10),
              Expanded(
                child: FilledButton(
                  style: FilledButton.styleFrom(backgroundColor: const Color(0xFFF59E0B)),
                  onPressed: _busy || _notes.text.trim().isEmpty || _pay.validate() != null ? null : _submit,
                  child: Text(_busy ? 'Sending' : 'Request Reservation'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _DateBox extends StatelessWidget {
  const _DateBox({required this.label, required this.value, required this.onTap});
  final String label;
  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: InputDecorator(
        decoration: InputDecoration(labelText: label),
        child: Text(value, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
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
  String _gcashName = '';
  String _gcashNumber = '';

  @override
  void initState() {
    super.initState();
    widget.repository.paymentInfo().then((info) {
      if (!mounted) return;
      setState(() {
        _gcashName = info['gcash_name']?.toString() ?? '';
        _gcashNumber = info['gcash_number']?.toString() ?? '';
      });
    }).catchError((_) {});
  }

  void _reload() => setState(() => _trips = widget.repository.bookings());

  Future<void> _changeUnit(Map<String, dynamic> trip) async {
    final units = await widget.repository.units();
    if (!mounted) return;
    final currentId = trip['accommodation_unit_id'];
    final choices = units.where((unit) => unit['id'] != currentId && unit['is_available'] == true).toList();
    if (choices.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('No other room or cottage is available.')));
      return;
    }
    int selected = (choices.first['id'] as num).toInt();
    final notes = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Change room or cottage'),
        content: StatefulBuilder(
          builder: (context, setLocal) => Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<int>(
                initialValue: selected,
                items: [
                  for (final unit in choices)
                    DropdownMenuItem(value: (unit['id'] as num).toInt(), child: Text('${unit['unit_number']} · ${_money.format(_number(unit['price_per_night']))}/night')),
                ],
                onChanged: (value) => setLocal(() => selected = value ?? selected),
              ),
              const SizedBox(height: 8),
              TextField(controller: notes, decoration: const InputDecoration(labelText: 'Modification note (optional)')),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Confirm Change')),
        ],
      ),
    );
    if (confirmed != true) {
      notes.dispose();
      return;
    }
    try {
      await widget.repository.modifyBooking(bookingId: (trip['id'] as num).toInt(), unitId: selected, notes: notes.text);
      notes.dispose();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Room or cottage updated.')));
        _reload();
      }
    } on ApiException catch (error) {
      notes.dispose();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  Future<void> _sendProof(Map<String, dynamic> trip) async {
    final payment = trip['payment'];
    if (payment is! Map || payment['id'] == null) return;
    final photo = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (photo == null) return;
    try {
      await widget.repository.uploadProof(paymentId: (payment['id'] as num).toInt(), filePath: photo.path);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Payment proof sent. Admin or staff will approve it.')));
      _reload();
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  Future<void> _cancelTrip(Map<String, dynamic> trip) async {
    String note = 'This releases the room or cottage for other guests.';
    try {
      final preview = await widget.repository.cancellationPreview((trip['id'] as num).toInt());
      if (preview.isNotEmpty) note = preview;
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
      return;
    }
    if (!mounted) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Cancel ${trip['reference_no'] ?? 'reservation'}'),
        content: Text(note),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Keep booking')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Cancel reservation')),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    try {
      final message = await widget.repository.cancelBooking((trip['id'] as num).toInt(), '');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
      _reload();
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Reservations')),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            color: ResortTheme.ocean900,
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Track bookings, manage check-in dates, and upload payment proofs', style: TextStyle(color: Color(0xFFE0F2FE), fontSize: 12)),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    FilledButton.icon(
                      style: FilledButton.styleFrom(backgroundColor: Colors.white, foregroundColor: ResortTheme.ocean900, minimumSize: const Size(0, 40)),
                      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => StaysPage(repository: widget.repository))),
                      icon: const Icon(Icons.add_circle, size: 16),
                      label: const Text('New Reservation'),
                    ),
                    FilledButton.icon(
                      style: FilledButton.styleFrom(backgroundColor: const Color(0xFFF59E0B), foregroundColor: Colors.white, minimumSize: const Size(0, 40)),
                      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => SpecialResortPage(repository: widget.repository))),
                      icon: const Icon(Icons.auto_awesome, size: 16),
                      label: const Text('Special Full-Resort Booking'),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            child: FutureBuilder<List<Map<String, dynamic>>>(
              future: _trips,
              builder: (context, snapshot) {
                if (snapshot.hasError) return Center(child: Text(snapshot.error.toString()));
                if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
                final trips = snapshot.data ?? [];
                if (trips.isEmpty) {
                  return const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('No Reservations Found\nYou have not made any resort reservations yet.', textAlign: TextAlign.center)));
                }
                return RefreshIndicator(
                  onRefresh: () async => _reload(),
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: trips.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      final trip = trips[index];
                      return _ReservationCard(
                        trip: trip,
                        gcashName: _gcashName,
                        gcashNumber: _gcashNumber,
                        onOpen: () async {
                          final changed = await Navigator.push<bool>(context, MaterialPageRoute(
                            builder: (_) => TripPage(repository: widget.repository, trip: trip),
                          ));
                          if (changed == true && mounted) _reload();
                        },
                        onChangeUnit: () => _changeUnit(trip),
                        onSendProof: () => _sendProof(trip),
                        onCancel: () => _cancelTrip(trip),
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _ReservationCard extends StatelessWidget {
  const _ReservationCard({
    required this.trip,
    required this.gcashName,
    required this.gcashNumber,
    required this.onOpen,
    required this.onChangeUnit,
    required this.onSendProof,
    required this.onCancel,
  });

  final Map<String, dynamic> trip;
  final String gcashName;
  final String gcashNumber;
  final VoidCallback onOpen;
  final VoidCallback onChangeUnit;
  final VoidCallback onSendProof;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final exclusive = trip['booking_type'] == 'special_resort';
    final unitMap = trip['accommodation_unit'] is Map ? Map<String, dynamic>.from(trip['accommodation_unit'] as Map) : null;
    final unit = exclusive
        ? 'Exclusive Full-Resort Private Reservation'
        : (unitMap?['unit_number']?.toString() ?? 'Room / Cottage Reservation');
    final kind = exclusive ? '' : '${_variantLabel(unitMap?['variant'])} ${_title(unitMap?['unit_type'])}'.trim();
    final status = trip['status']?.toString() ?? '';
    final payment = trip['payment'] is Map ? Map<String, dynamic>.from(trip['payment'] as Map) : null;
    final method = payment?['gateway']?.toString() ?? payment?['payment_channel']?.toString() ?? '';
    final hourly = trip['stay_type'] == 'hour';
    final canChange = !exclusive && ['pending', 'paid', 'checked_in'].contains(status);
    final canCancel = status == 'pending' || status == 'paid';
    final needsProof = status == 'pending' && (method == 'gcash' || method == 'paypal') && !_guestSentProof(payment);
    final cash = payment?['is_cash_on_arrival'] == true || method == 'cash';
    final waiting = status == 'pending' && (method == 'gcash' || method == 'paypal') && _guestSentProof(payment);

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onOpen,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Wrap(
                spacing: 6,
                runSpacing: 6,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(color: ResortTheme.ocean50, borderRadius: BorderRadius.circular(6), border: Border.all(color: const Color(0xFFBAE6FD))),
                    child: Text(trip['reference_no']?.toString() ?? 'Reservation', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: ResortTheme.ocean900)),
                  ),
                  _StatusPill(status: status),
                  if (method.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(6), border: Border.all(color: const Color(0xFFE2E8F0))),
                      child: Text(method.toUpperCase(), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: ResortTheme.muted)),
                    ),
                ],
              ),
              const SizedBox(height: 8),
              Text(unit, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
              if (kind.isNotEmpty) Text('($kind)', style: const TextStyle(fontSize: 12, color: ResortTheme.muted, fontWeight: FontWeight.w600)),
              const SizedBox(height: 6),
              Text(
                hourly
                    ? '${_stayDate(trip['check_in_date'] ?? trip['booking_date'])} · ${trip['time_slot'] ?? 'Hourly stay'}'
                    : '${_stayDate(trip['check_in_date'] ?? trip['booking_date'])} to ${_stayDate(trip['check_out_date'])}',
                style: const TextStyle(fontSize: 12, color: ResortTheme.muted),
              ),
              Text('${trip['guests_count']} Guests', style: const TextStyle(fontSize: 12, color: ResortTheme.muted)),
              const SizedBox(height: 8),
              const Text('Total Cost', style: TextStyle(fontSize: 11, color: ResortTheme.muted, fontWeight: FontWeight.w700)),
              Text(_money.format(_number(trip['total_amount'])), style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: ResortTheme.ocean900)),
              if (status == 'pending' && method == 'gcash') ...[
                const SizedBox(height: 8),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(color: const Color(0xFFF0F9FF), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFBAE6FD))),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('SEND GCASH TO', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFF0369A1), letterSpacing: 0.4)),
                      Text(gcashName.trim().isEmpty ? 'Talisay Beach Resort' : gcashName, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800)),
                      Text(gcashNumber.trim().isEmpty ? 'GCash number is not set in System Settings yet' : gcashNumber, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: ResortTheme.ocean800)),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  if (canChange)
                    _MiniButton(label: 'Change Unit', color: const Color(0xFF0369A1), background: const Color(0xFFF0F9FF), border: const Color(0xFFBAE6FD), onTap: onChangeUnit),
                  if (needsProof)
                    _MiniButton(label: 'Send payment proof', color: Colors.white, background: const Color(0xFFF59E0B), border: const Color(0xFFF59E0B), onTap: onSendProof),
                  if (waiting)
                    const _MiniButton(label: 'Waiting for admin approval', color: Color(0xFF065F46), background: Color(0xFFECFDF5), border: Color(0xFFA7F3D0)),
                  if (cash)
                    const _MiniButton(label: 'Pay on Arrival', color: Color(0xFFB45309), background: Color(0xFFFFFBEB), border: Color(0xFFFDE68A)),
                  if (canCancel)
                    _MiniButton(label: 'Cancel', color: ResortTheme.muted, background: const Color(0xFFF1F5F9), border: const Color(0xFFE2E8F0), onTap: onCancel),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

bool _guestSentProof(Map<String, dynamic>? payment) {
  if (payment == null) return false;
  final proof = payment['proof_path']?.toString() ?? '';
  if (proof.isNotEmpty) return true;
  final reference = payment['transaction_id']?.toString() ?? '';
  if (reference.isEmpty) return false;
  return !RegExp(r'^(TXN|CASH|MNL)-').hasMatch(reference);
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final colors = switch (status) {
      'pending' => (const Color(0xFFFEF3C7), const Color(0xFF92400E), const Color(0xFFFDE68A)),
      'paid' => (const Color(0xFFECFDF5), const Color(0xFF065F46), const Color(0xFFA7F3D0)),
      'checked_in' => (const Color(0xFFF0F9FF), const Color(0xFF075985), const Color(0xFFBAE6FD)),
      'cancelled' => (const Color(0xFFFEF2F2), const Color(0xFF991B1B), const Color(0xFFFECACA)),
      _ => (const Color(0xFFF1F5F9), const Color(0xFF334155), const Color(0xFFE2E8F0)),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: colors.$1, borderRadius: BorderRadius.circular(20), border: Border.all(color: colors.$3)),
      child: Text(status.replaceAll('_', ' ').toUpperCase(), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 0.4, color: colors.$2)),
    );
  }
}

class _MiniButton extends StatelessWidget {
  const _MiniButton({required this.label, required this.color, required this.background, required this.border, this.onTap});
  final String label;
  final Color color;
  final Color background;
  final Color border;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: background,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(12), border: Border.all(color: border)),
          child: Text(label, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: color)),
        ),
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
  final _reference = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _reason.dispose();
    _reference.dispose();
    super.dispose();
  }

  Future<void> _cancel() async {
    setState(() => _busy = true);
    String note = 'This releases the room or cottage for other guests.';
    try {
      final preview = await widget.repository.cancellationPreview((widget.trip['id'] as num).toInt());
      if (preview.isNotEmpty) note = preview;
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
      if (mounted) setState(() => _busy = false);
      return;
    }
    if (!mounted) return;
    setState(() => _busy = false);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel reservation'),
        content: Text(note),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Keep booking')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Cancel reservation')),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;
    setState(() => _busy = true);
    try {
      final message = await widget.repository.cancelBooking((widget.trip['id'] as num).toInt(), _reason.text.trim());
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
      Navigator.pop(context, true);
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _proof(int paymentId, {bool pickFile = true}) async {
    String? path;
    if (pickFile) {
      final photo = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
      if (photo == null && _reference.text.trim().isEmpty) return;
      path = photo?.path;
    }
    setState(() => _busy = true);
    try {
      await widget.repository.uploadProof(paymentId: paymentId, filePath: path, referenceNumber: _reference.text);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Payment proof sent. Admin or staff will approve it.')));
        Navigator.pop(context, true);
      }
    } on ApiException catch (error) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _modify() async {
    final units = await widget.repository.units();
    if (!mounted) return;
    final currentId = widget.trip['accommodation_unit_id'];
    final choices = units.where((unit) => unit['id'] != currentId && unit['is_available'] == true).toList();
    if (choices.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('No other room or cottage is available.')));
      return;
    }
    int selected = (choices.first['id'] as num).toInt();
    final notes = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Change room or cottage'),
        content: StatefulBuilder(
          builder: (context, setLocal) => Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<int>(
                initialValue: selected,
                items: [
                  for (final unit in choices)
                    DropdownMenuItem(value: (unit['id'] as num).toInt(), child: Text('${unit['unit_number']} · ${_money.format(_number(unit['price_per_night']))}/night')),
                ],
                onChanged: (value) => setLocal(() => selected = value ?? selected),
              ),
              const SizedBox(height: 8),
              TextField(controller: notes, decoration: const InputDecoration(labelText: 'Modification note (optional)')),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Confirm Change')),
        ],
      ),
    );
    if (confirmed != true) {
      notes.dispose();
      return;
    }
    setState(() => _busy = true);
    try {
      await widget.repository.modifyBooking(bookingId: (widget.trip['id'] as num).toInt(), unitId: selected, notes: notes.text);
      notes.dispose();
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (error) {
      notes.dispose();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final trip = widget.trip;
    final exclusive = trip['booking_type'] == 'special_resort';
    final unit = exclusive
        ? 'Exclusive Full-Resort Private Reservation'
        : (trip['accommodation_unit'] is Map ? trip['accommodation_unit']['unit_number']?.toString() : 'Room / Cottage');
    final payment = trip['payment'] is Map ? Map<String, dynamic>.from(trip['payment'] as Map) : null;
    final status = trip['status']?.toString() ?? '';
    final method = payment?['gateway']?.toString() ?? payment?['payment_channel']?.toString() ?? payment?['payment_method']?.toString() ?? '';
    final canCancel = status == 'pending' || status == 'paid';
    final needsProof = payment != null && (method == 'gcash' || method == 'paypal') && payment['status'] != 'success' && status != 'cancelled';

    return Scaffold(
      appBar: AppBar(title: Text(trip['reference_no']?.toString() ?? 'Reservation')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(unit ?? 'Stay', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          Text(trip['stay_type'] == 'hour'
              ? '${_stayDate(trip['check_in_date'] ?? trip['booking_date'])} · ${trip['time_slot'] ?? 'Hourly stay'}'
              : '${_stayDate(trip['check_in_date'] ?? trip['booking_date'])} to ${_stayDate(trip['check_out_date'])}'),
          const SizedBox(height: 8),
          Text('${trip['guests_count']} guests · ${_money.format(_number(trip['total_amount']))}'),
          const SizedBox(height: 8),
          Text('Stay: $status'),
          Text('Payment: ${payment?['status'] ?? 'unpaid'}${method.isEmpty ? '' : ' · ${method.toUpperCase()}'}'),
          if (payment?['metadata'] is Map && payment!['metadata']['payment_plan'] == 'down') ...[
            const SizedBox(height: 8),
            Text('Pay now ${_money.format(_number(payment['amount']))}. Balance at the resort ${_money.format(_number(payment['metadata']['balance']))}.'),
          ],
          if (method == 'cash') ...[
            const SizedBox(height: 12),
            const Text('Pay at the front desk and show this reference number.', style: TextStyle(color: ResortTheme.muted)),
          ],
          if (needsProof) ...[
            const SizedBox(height: 16),
            const Text('Upload a receipt photo or enter the transaction reference number. Admin or staff will approve the payment.', style: TextStyle(color: ResortTheme.muted)),
            const SizedBox(height: 12),
            TextField(controller: _reference, decoration: const InputDecoration(labelText: 'Transaction reference number', hintText: 'Example: 1234 567 890123')),
            const SizedBox(height: 8),
            FilledButton(onPressed: _busy ? null : () => _proof((payment['id'] as num).toInt()), child: const Text('Send receipt')),
            const SizedBox(height: 8),
            OutlinedButton(onPressed: _busy ? null : () => _proof((payment['id'] as num).toInt(), pickFile: false), child: const Text('Send reference number')),
          ],
          if (!exclusive && canCancel) ...[
            const SizedBox(height: 12),
            OutlinedButton(onPressed: _busy ? null : _modify, child: const Text('Change room or cottage')),
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
      setState(() => _messages.add({'role': 'bot', 'text': 'Cannot reach the resort server at ${AppConfig.apiBase}. Check your internet connection and try again.'}));
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

double _hourlyRate(Object? nightly) => (_number(nightly) / 12 * 100).roundToDouble() / 100;

double _shownHourly(Map unit) {
  final stored = unit['hourly_rate'];
  if (stored != null) return _number(stored);
  return _hourlyRate(unit['price_per_night']);
}

String _clock(int hour) => '${hour.toString().padLeft(2, '0')}:00';

String _ampm(int hour) {
  final suffix = hour >= 12 ? 'PM' : 'AM';
  final face = hour % 12 == 0 ? 12 : hour % 12;
  return '$face:00 $suffix';
}

String _stayDate(Object? value) {
  final text = value?.toString() ?? '';
  if (text.length >= 10) return text.substring(0, 10);
  return text.isEmpty ? '—' : text;
}
