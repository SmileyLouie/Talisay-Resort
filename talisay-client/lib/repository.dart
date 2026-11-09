import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:talisay_client/config.dart';
import 'package:url_launcher/url_launcher.dart';

class ApiException implements Exception {
  ApiException(this.message);
  final String message;
  @override
  String toString() => message;
}

class ResortRepository {
  ResortRepository(this.token);

  String? token;
  Map<String, dynamic>? user;

  static const _tokenKey = 'guest_token';
  static const rememberedEmailKey = 'talisay.rememberedEmail';

  static Future<String?> rememberedEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(rememberedEmailKey);
  }

  static Future<void> rememberEmail(String? email) async {
    final prefs = await SharedPreferences.getInstance();
    final value = email?.trim() ?? '';
    if (value.isEmpty) {
      await prefs.remove(rememberedEmailKey);
    } else {
      await prefs.setString(rememberedEmailKey, value);
    }
  }

  static Future<ResortRepository> load() async {
    final prefs = await SharedPreferences.getInstance();
    final repo = ResortRepository(prefs.getString(_tokenKey));
    if (repo.token != null) {
      try {
        repo.user = await repo.me();
      } catch (_) {
        await repo.clear();
      }
    }
    return repo;
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    final body = await _post('/api/mobile/register', {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
      'password_confirmation': password,
    }, auth: false);
    await _storeSession(body);
  }

  Future<void> signIn(String email, String password) async {
    final body = await _post('/api/mobile/login', {
      'email': email,
      'password': password,
    }, auth: false);
    await _storeSession(body);
  }

  Future<void> signInWithProvider(String provider) async {
    final start = await _post('/api/mobile/auth/$provider/start', {}, auth: false);
    final attempt = start['attempt']?.toString();
    final url = start['url']?.toString();
    if (attempt == null || url == null) {
      throw ApiException('Could not start sign-in.');
    }
    final opened = await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    if (!opened) {
      throw ApiException('Could not open the sign-in page.');
    }
    for (var i = 0; i < 45; i++) {
      await Future.delayed(const Duration(seconds: 2));
      final status = await _get('/api/mobile/auth/social/$attempt', auth: false);
      final state = status['status']?.toString();
      if (state == 'ok') {
        await _storeSession(status);
        return;
      }
      if (state == 'error') {
        throw ApiException(status['message']?.toString() ?? 'Sign-in failed.');
      }
      if (state == 'expired') {
        throw ApiException('Sign-in expired. Try again.');
      }
    }
    throw ApiException('Sign-in timed out. Finish it in the browser, then try again.');
  }

  Future<void> signOut() async {
    try {
      await _post('/api/logout', {});
    } catch (_) {}
    await clear();
  }

  Future<void> clear() async {
    token = null;
    user = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<Map<String, dynamic>> me() async {
    final body = await _get('/api/user');
    user = body;
    return body;
  }

  Future<void> saveProfile({
    required String name,
    required String email,
    String? phone,
    String? avatarPath,
  }) async {
    final Map<String, dynamic> body;
    if (avatarPath == null) {
      body = await _put('/api/profile', {
        'name': name,
        'email': email,
        'phone': phone,
      });
    } else {
      final request = http.MultipartRequest('POST', Uri.parse('${AppConfig.apiBase}/api/profile'));
      request.headers.addAll({
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      });
      request.fields.addAll({
        '_method': 'PUT',
        'name': name,
        'email': email,
        'phone': phone ?? '',
      });
      request.files.add(await http.MultipartFile.fromPath('avatar', avatarPath));
      final streamed = await request.send();
      body = _decode(await http.Response.fromStream(streamed));
    }
    if (body['user'] is Map) user = Map<String, dynamic>.from(body['user'] as Map);
  }

  Future<void> updatePassword({
    required String currentPassword,
    required String password,
  }) async {
    await _put('/api/password', {
      'current_password': currentPassword,
      'password': password,
      'password_confirmation': password,
    });
  }

  Future<Map<String, dynamic>> paymentInfo() async {
    return await _get('/api/mobile/payment-info', auth: false);
  }

  Future<Map<String, dynamic>> site() async {
    return await _get('/api/mobile/site', auth: false);
  }

  Future<List<Map<String, dynamic>>> units() async {
    final body = await _get('/api/mobile/units', auth: false);
    return _list(body['units']);
  }

  Future<Map<String, dynamic>> checkStay({
    required int unitId,
    required DateTime checkIn,
    DateTime? checkOut,
    String stayType = 'night',
    int? hours,
    String? hourStart,
  }) async {
    final params = <String, String>{
      'check_in': _date(checkIn),
      'stay_type': stayType,
      if (checkOut != null) 'check_out': _date(checkOut),
      if (hours != null) 'hours_count': '$hours',
      if (hourStart != null) 'hour_start': hourStart,
    };
    final query = params.entries.map((entry) => '${entry.key}=${Uri.encodeQueryComponent(entry.value)}').join('&');
    return await _get('/api/mobile/units/$unitId/availability?$query', auth: false);
  }

  Future<Map<String, dynamic>> requestBooking({
    required int unitId,
    required DateTime checkIn,
    DateTime? checkOut,
    required int guests,
    required String paymentMethod,
    String stayType = 'night',
    int? hours,
    String? hourStart,
    String paymentPlan = 'full',
    String gcashMethod = 'qr',
    String? notes,
    String? cardLastFour,
    List<int> activityIds = const [],
  }) async {
    return await _post('/api/bookings', {
      'accommodation_unit_id': unitId,
      'booking_date': _date(checkIn),
      if (checkOut != null) 'check_out_date': _date(checkOut),
      'stay_type': stayType,
      if (stayType == 'hour') 'hours_count': hours,
      if (stayType == 'hour') 'hour_start': hourStart,
      'guests_count': guests,
      'payment_method': paymentMethod,
      'payment_plan': paymentPlan,
      'gcash_method': gcashMethod,
      'special_requests': notes,
      if (cardLastFour != null) 'card_last_four': cardLastFour,
      if (activityIds.isNotEmpty) 'activity_ids': activityIds,
    });
  }

  Future<Map<String, dynamic>> requestSpecialResort({
    required DateTime checkIn,
    required DateTime checkOut,
    required int guests,
    required String paymentMethod,
    required String notes,
    String? cardLastFour,
    String paymentPlan = 'full',
    String gcashMethod = 'qr',
    List<int> activityIds = const [],
  }) async {
    return await _post('/api/bookings/special-resort', {
      'check_in_date': _date(checkIn),
      'check_out_date': _date(checkOut),
      'guests_count': guests,
      'payment_method': paymentMethod,
      'payment_plan': paymentPlan,
      'gcash_method': gcashMethod,
      'special_requests': notes,
      if (cardLastFour != null) 'card_last_four': cardLastFour,
      if (activityIds.isNotEmpty) 'activity_ids': activityIds,
    });
  }

  Future<Map<String, dynamic>> modifyBooking({
    required int bookingId,
    required int unitId,
    String? notes,
  }) async {
    return await _post('/api/bookings/$bookingId/modify', {
      'new_accommodation_unit_id': unitId,
      'modification_notes': notes,
    });
  }

  Future<List<Map<String, dynamic>>> bookings() async {
    final body = await _get('/api/my-bookings');
    return _list(body['data']);
  }

  Future<String> cancellationPreview(int id) async {
    final body = await _get('/api/bookings/$id/cancellation-preview');
    return body['message']?.toString() ?? '';
  }

  Future<String> cancelBooking(int id, String reason) async {
    final body = await _post('/api/bookings/$id/cancel', {'cancellation_reason': reason});
    return body['message']?.toString() ?? 'Booking cancelled.';
  }

  Future<void> uploadProof({
    required int paymentId,
    String? filePath,
    String? referenceNumber,
  }) async {
    final reference = (referenceNumber ?? '').replaceAll(RegExp(r'\D'), '');
    if (!RegExp(r'^\d{13}$').hasMatch(reference) || filePath == null || filePath.isEmpty) {
      throw ApiException('Enter the 13-digit GCash transaction reference and upload the receipt screenshot.');
    }
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${AppConfig.apiBase}/api/payments/$paymentId/upload-proof'),
    );
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    });
    if (reference.isNotEmpty) request.fields['reference_number'] = reference;
    request.files.add(await http.MultipartFile.fromPath('proof', filePath));
    final streamed = await request.send();
    _decode(await http.Response.fromStream(streamed));
  }

  Future<List<Map<String, dynamic>>> reviews() async {
    final body = await _get('/api/my-reviews');
    return _list(body['data']);
  }

  Future<void> submitReview({
    required int bookingId,
    required int rating,
    required String comment,
  }) async {
    await _post('/api/reviews', {
      'booking_id': bookingId,
      'rating': rating,
      'comment': comment,
    });
  }

  Future<List<Map<String, dynamic>>> notifications() async {
    final body = await _get('/api/notifications');
    if (body['data'] is List) return _list(body['data']);
    if (body['notifications'] is List) return _list(body['notifications']);
    return _list(body.values.firstWhere((value) => value is List, orElse: () => []));
  }

  Future<String> chat(String message) async {
    final body = await _post('/api/mobile/chat', {'message': message});
    return body['response']?.toString() ?? 'No answer was returned.';
  }

  Future<List<Map<String, dynamic>>> tourScenes() async {
    final response = await http.get(Uri.parse('${AppConfig.apiBase}/api/tour'));
    final decoded = jsonDecode(response.body);
    if (decoded is List) {
      return decoded.map((item) => Map<String, dynamic>.from(item as Map)).toList();
    }
    return [];
  }

  Future<void> _storeSession(Map<String, dynamic> body) async {
    token = body['token']?.toString();
    user = body['user'] is Map ? Map<String, dynamic>.from(body['user'] as Map) : null;
    final prefs = await SharedPreferences.getInstance();
    if (token != null) await prefs.setString(_tokenKey, token!);
  }

  Future<Map<String, dynamic>> _get(String path, {bool auth = true}) async {
    final response = await http.get(Uri.parse('${AppConfig.apiBase}$path'), headers: _headers(auth));
    return _decode(response);
  }

  Future<Map<String, dynamic>> _post(String path, Map<String, dynamic> payload, {bool auth = true}) async {
    final response = await http.post(
      Uri.parse('${AppConfig.apiBase}$path'),
      headers: _headers(auth),
      body: jsonEncode(payload),
    );
    return _decode(response);
  }

  Future<Map<String, dynamic>> _put(String path, Map<String, dynamic> payload) async {
    final response = await http.put(
      Uri.parse('${AppConfig.apiBase}$path'),
      headers: _headers(true),
      body: jsonEncode(payload),
    );
    return _decode(response);
  }

  Map<String, String> _headers(bool auth) {
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (auth && token != null) 'Authorization': 'Bearer $token',
    };
  }

  Map<String, dynamic> _decode(http.Response response) {
    final decoded = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body);
    final body = decoded is Map ? Map<String, dynamic>.from(decoded) : <String, dynamic>{'data': decoded};
    if (response.statusCode >= 400) {
      final errors = body['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) throw ApiException(first.first.toString());
      }
      throw ApiException(body['message']?.toString() ?? body['error']?.toString() ?? 'Request failed.');
    }
    return body;
  }

  List<Map<String, dynamic>> _list(Object? value) {
    if (value is List) {
      return value.map((item) => Map<String, dynamic>.from(item as Map)).toList();
    }
    return [];
  }

  String _date(DateTime value) {
    final month = value.month.toString().padLeft(2, '0');
    final day = value.day.toString().padLeft(2, '0');
    return '${value.year}-$month-$day';
  }
}
