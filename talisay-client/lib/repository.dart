import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:talisay_client/config.dart';

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

  Future<List<Map<String, dynamic>>> units() async {
    final body = await _get('/api/mobile/units', auth: false);
    return _list(body['units']);
  }

  Future<Map<String, dynamic>> checkStay({
    required int unitId,
    required DateTime checkIn,
    required DateTime checkOut,
  }) async {
    final query = 'check_in=${_date(checkIn)}&check_out=${_date(checkOut)}';
    return await _get('/api/mobile/units/$unitId/availability?$query', auth: false);
  }

  Future<Map<String, dynamic>> requestBooking({
    required int unitId,
    required DateTime checkIn,
    required DateTime checkOut,
    required int guests,
    required String paymentMethod,
    String? notes,
  }) async {
    return await _post('/api/bookings', {
      'accommodation_unit_id': unitId,
      'booking_date': _date(checkIn),
      'check_out_date': _date(checkOut),
      'guests_count': guests,
      'payment_method': paymentMethod,
      'special_requests': notes,
    });
  }

  Future<List<Map<String, dynamic>>> bookings() async {
    final body = await _get('/api/my-bookings');
    return _list(body['data']);
  }

  Future<void> cancelBooking(int id, String reason) async {
    await _post('/api/bookings/$id/cancel', {'cancellation_reason': reason});
  }

  Future<void> uploadProof({required int paymentId, required String filePath}) async {
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${AppConfig.apiBase}/api/payments/$paymentId/upload-proof'),
    );
    request.headers.addAll({
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    });
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
