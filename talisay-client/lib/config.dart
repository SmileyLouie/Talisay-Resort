import 'package:flutter/foundation.dart';

/// The phone app talks to the public site.
/// Chrome on this computer uses 127.0.0.1.
class AppConfig {
  static const lanHost = '192.168.0.115';
  static const publicBase = 'https://pose-webcast-valid-vat.trycloudflare.com';

  static String get apiBase {
    if (kIsWeb) return publicBase;
    if (defaultTargetPlatform == TargetPlatform.android || defaultTargetPlatform == TargetPlatform.iOS) {
      return publicBase;
    }
    return 'http://127.0.0.1:8000';
  }

  static const resortName = 'Talisay Beach Resort';
  static const location = 'Barangay Maslug, Baybay City, Leyte';
  static const tourPath = '/tour';
}
