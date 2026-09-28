import 'dart:io';

/// Development addresses for the existing resort API.
/// A real Android phone uses this computer's Wi-Fi address.
/// Chrome on this computer uses 127.0.0.1.
class AppConfig {
  static const lanHost = '192.168.1.9';

  static String get apiBase {
    if (Platform.isAndroid) return 'http://$lanHost:8000';
    return 'http://127.0.0.1:8000';
  }

  static const resortName = 'Talisay Beach Resort';
  static const location = 'Barangay Maslug, Baybay City, Leyte';
  static const tourPath = '/tour';
}
