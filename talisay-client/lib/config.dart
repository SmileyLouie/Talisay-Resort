import 'package:flutter/foundation.dart';

/// The phone app talks to the public Laravel client portal API on port 8000.
///
/// Override at build/run time:
/// `flutter run --dart-define=API_BASE=http://192.168.x.x:8000`
class AppConfig {
  static const lanHost = '192.168.0.115';

  static const apiBaseOverride = String.fromEnvironment('API_BASE', defaultValue: '');

  static String get apiBase {
    if (apiBaseOverride.isNotEmpty) return apiBaseOverride;
    if (kIsWeb) return 'http://127.0.0.1:8000';
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return 'http://10.0.2.2:8000';
      case TargetPlatform.iOS:
        return 'http://127.0.0.1:8000';
      default:
        return 'http://127.0.0.1:8000';
    }
  }

  static const resortName = 'Talisay Beach Resort';
  static const location = 'Barangay Maslug, Baybay City, Leyte';
  static const tourPath = '/tour';
}
