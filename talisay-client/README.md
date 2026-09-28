# Talisay client app

Android and iOS app for resort guests. It talks to the existing PHP resort server. The server address is written in `lib/config.dart`:

- Android emulator: `http://10.0.2.2:8000`
- iOS simulator and desktop: `http://127.0.0.1:8000`

Start the resort API first (`php artisan serve` in `talisay-resort`). The app signs in as a guest, loads rooms and cottages, sends reservations, and opens the 360 tour. Admin and staff accounts cannot use this login.

## Run

Install Flutter, then from this folder:

```bash
flutter pub get
flutter run
```

On Android, cleartext HTTP is already allowed so the emulator can reach `php artisan serve`. An iOS build has to be made on a Mac.
