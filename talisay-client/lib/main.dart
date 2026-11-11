import 'package:flutter/material.dart';
import 'package:talisay_client/config.dart';
import 'package:talisay_client/pages.dart';
import 'package:talisay_client/repository.dart';
import 'package:talisay_client/theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final repository = await ResortRepository.load();
  runApp(ResortApp(repository: repository));
}

class ResortApp extends StatefulWidget {
  const ResortApp({super.key, required this.repository});
  final ResortRepository repository;

  @override
  State<ResortApp> createState() => _ResortAppState();
}

class _ResortAppState extends State<ResortApp> {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: AppConfig.resortName,
      debugShowCheckedModeBanner: false,
      theme: ResortTheme.light(),
      home: widget.repository.token == null
          ? LoginScreen(repository: widget.repository, onSignedIn: _refresh)
          : ResortShell(repository: widget.repository, onSignedOut: _refresh),
    );
  }

  void _refresh() => setState(() {});
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.repository, required this.onSignedIn});
  final ResortRepository repository;
  final VoidCallback onSignedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  bool _rememberEmail = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    ResortRepository.rememberedEmail().then((saved) {
      if (!mounted || saved == null || saved.isEmpty) return;
      setState(() {
        _email.text = saved;
        _rememberEmail = true;
      });
    });
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _storeEmail() async {
    await ResortRepository.rememberEmail(_rememberEmail ? _email.text : null);
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await _storeEmail();
      await widget.repository.signIn(_email.text.trim(), _password.text);
      widget.onSignedIn();
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } catch (_) {
      setState(() => _error = 'Cannot reach the resort server at ${AppConfig.apiBase}.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _social(String provider) async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.repository.signInWithProvider(provider);
      widget.onSignedIn();
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    } catch (_) {
      setState(() => _error = 'Cannot reach the resort server at ${AppConfig.apiBase}.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            Container(
              width: double.infinity,
              color: ResortTheme.ocean900,
              padding: const EdgeInsets.fromLTRB(24, 36, 24, 28),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('GUEST PORTAL', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1.1, color: ResortTheme.sky200)),
                  SizedBox(height: 8),
                  Text(AppConfig.resortName, style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: Colors.white)),
                  SizedBox(height: 6),
                  Text(AppConfig.location, style: TextStyle(color: Color(0xFFE0F2FE))),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 24, 24, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
            const Text('Sign in', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: ResortTheme.ink)),
            const SizedBox(height: 16),
            TextField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Email'),
              onChanged: (_) {
                if (_rememberEmail) _storeEmail();
              },
            ),
            const SizedBox(height: 12),
            TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Password')),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              controlAffinity: ListTileControlAffinity.leading,
              value: _rememberEmail,
              onChanged: (value) async {
                setState(() => _rememberEmail = value ?? false);
                await _storeEmail();
              },
              title: const Text('Remember email'),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C))),
            ],
            const SizedBox(height: 20),
            FilledButton(onPressed: _busy ? null : _submit, child: Text(_busy ? 'Signing in' : 'Sign in')),
            const SizedBox(height: 18),
            const Text("Don't have an account yet?", textAlign: TextAlign.center, style: TextStyle(color: ResortTheme.muted)),
            const SizedBox(height: 8),
            _SocialButtons(
              busy: _busy,
              onGoogle: () => _social('google'),
              onFacebook: () => _social('facebook'),
            ),
            TextButton(
              onPressed: () => Navigator.push(context, MaterialPageRoute(
                builder: (_) => RegisterScreen(repository: widget.repository, onSignedIn: widget.onSignedIn),
              )),
              child: const Text('Register'),
            ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key, required this.repository, required this.onSignedIn});
  final ResortRepository repository;
  final VoidCallback onSignedIn;

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _first = TextEditingController();
  final _last = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  int? _month;
  int? _day;
  int? _year;
  String? _gender;
  bool _busy = false;
  bool _hidePassword = true;
  bool _hideConfirm = true;
  String? _message;

  static const _months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  @override
  void dispose() {
    _first.dispose();
    _last.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final first = _first.text.trim();
    final last = _last.text.trim();
    if (first.isEmpty || last.isEmpty) {
      setState(() => _message = 'Enter your first name and last name.');
      return;
    }
    if (_email.text.trim().isEmpty) {
      setState(() => _message = 'Enter your email address.');
      return;
    }
    if (_password.text.length < 8) {
      setState(() => _message = 'Password must be at least 8 characters.');
      return;
    }
    if (_password.text != _confirm.text) {
      setState(() => _message = 'Password and confirm password do not match.');
      return;
    }
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await widget.repository.register(
        name: '$first $last',
        email: _email.text.trim(),
        password: _password.text,
        phone: _phone.text.trim(),
      );
      widget.onSignedIn();
      if (mounted) Navigator.pop(context);
    } on ApiException catch (error) {
      setState(() => _message = error.message);
    } catch (_) {
      setState(() => _message = 'Cannot reach the resort server at ${AppConfig.apiBase}.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _social(String provider) async {
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await widget.repository.signInWithProvider(provider);
      widget.onSignedIn();
      if (mounted) Navigator.pop(context);
    } on ApiException catch (error) {
      setState(() => _message = error.message);
    } catch (_) {
      setState(() => _message = 'Cannot reach the resort server at ${AppConfig.apiBase}.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final yearNow = DateTime.now().year;
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: ResortTheme.ink,
        elevation: 0,
        title: const Text(''),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(22, 0, 22, 28),
        children: [
          Align(
            alignment: Alignment.centerRight,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(color: ResortTheme.ocean50, borderRadius: BorderRadius.circular(20)),
              child: const Text('Talisay Beach Resort', style: TextStyle(color: ResortTheme.ocean800, fontWeight: FontWeight.w700, fontSize: 12)),
            ),
          ),
          const SizedBox(height: 16),
          const Text('Talisay Beach Resort', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
          const Text('SMART TOURISM SYSTEM', style: TextStyle(fontSize: 11, letterSpacing: 0.4, color: ResortTheme.muted, fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          const Text('Get started on Talisay Beach Resort', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: ResortTheme.ink)),
          const SizedBox(height: 6),
          const Text(
            'An account lets you reserve cottages, track bookings, and explore 360° virtual tours easily and securely.',
            style: TextStyle(color: ResortTheme.muted, height: 1.35),
          ),
          const SizedBox(height: 18),
          _SocialButtons(
            busy: _busy,
            onGoogle: () => _social('google'),
            onFacebook: () => _social('facebook'),
          ),
          const _FieldLabel('Name'),
          Row(
            children: [
              Expanded(child: TextField(controller: _first, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(hintText: 'First name'))),
              const SizedBox(width: 10),
              Expanded(child: TextField(controller: _last, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(hintText: 'Last name'))),
            ],
          ),
          const SizedBox(height: 14),
          const _FieldLabel('Birthday'),
          Row(
            children: [
              Expanded(child: _menu<int>(value: _month, hint: 'Month', items: [for (var i = 0; i < 12; i++) (i + 1, _months[i])], onChanged: (v) => setState(() => _month = v))),
              const SizedBox(width: 8),
              Expanded(child: _menu<int>(value: _day, hint: 'Day', items: [for (var d = 1; d <= 31; d++) (d, '$d')], onChanged: (v) => setState(() => _day = v))),
              const SizedBox(width: 8),
              Expanded(child: _menu<int>(value: _year, hint: 'Year', items: [for (var y = yearNow - 12; y >= yearNow - 95; y--) (y, '$y')], onChanged: (v) => setState(() => _year = v))),
            ],
          ),
          const SizedBox(height: 14),
          const _FieldLabel('Gender'),
          Row(
            children: [
              for (final choice in const [('female', 'Female'), ('male', 'Male'), ('other', 'Custom')]) ...[
                Expanded(child: _GenderChoice(label: choice.$2, selected: _gender == choice.$1, onTap: () => setState(() => _gender = choice.$1))),
                if (choice.$1 != 'other') const SizedBox(width: 8),
              ],
            ],
          ),
          const SizedBox(height: 14),
          const _FieldLabel('Email address'),
          TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(hintText: 'Email address')),
          const SizedBox(height: 14),
          const _FieldLabel('Mobile number'),
          TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(hintText: 'Mobile number (09xx-xxx-xxxx)')),
          const SizedBox(height: 4),
          const Text('You may receive SMS updates about your cottage bookings and check-in confirmation.', style: TextStyle(fontSize: 12, color: ResortTheme.muted)),
          const SizedBox(height: 14),
          const _FieldLabel('Password'),
          TextField(
            controller: _password,
            obscureText: _hidePassword,
            decoration: InputDecoration(
              hintText: 'New password (min. 8 characters)',
              suffixIcon: IconButton(
                onPressed: () => setState(() => _hidePassword = !_hidePassword),
                icon: Icon(_hidePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined),
              ),
            ),
          ),
          const SizedBox(height: 14),
          const _FieldLabel('Confirm Password'),
          TextField(
            controller: _confirm,
            obscureText: _hideConfirm,
            decoration: InputDecoration(
              hintText: 'Re-type password',
              suffixIcon: IconButton(
                onPressed: () => setState(() => _hideConfirm = !_hideConfirm),
                icon: Icon(_hideConfirm ? Icons.visibility_outlined : Icons.visibility_off_outlined),
              ),
            ),
          ),
          const SizedBox(height: 12),
          const Text(
            'By clicking Submit, you agree to the resort Terms of Service and Privacy Policy.',
            style: TextStyle(fontSize: 12, color: ResortTheme.muted, height: 1.35),
          ),
          if (_message != null) ...[
            const SizedBox(height: 12),
            Text(_message!, style: const TextStyle(color: Color(0xFFB91C1C))),
          ],
          const SizedBox(height: 16),
          FilledButton(onPressed: _busy ? null : _submit, child: Text(_busy ? 'Submitting' : 'Submit')),
        ],
      ),
    );
  }

  Widget _menu<T>({required T? value, required String hint, required List<(T, String)> items, required ValueChanged<T?> onChanged}) {
    return DropdownButtonFormField<T>(
      initialValue: value,
      isExpanded: true,
      decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12)),
      hint: Text(hint, style: const TextStyle(fontSize: 13, color: ResortTheme.muted)),
      items: [for (final item in items) DropdownMenuItem<T>(value: item.$1, child: Text(item.$2, overflow: TextOverflow.ellipsis))],
      onChanged: onChanged,
    );
  }
}

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(text, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: ResortTheme.ink)),
    );
  }
}

class _GenderChoice extends StatelessWidget {
  const _GenderChoice({required this.label, required this.selected, required this.onTap});
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        height: 48,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(
          color: selected ? ResortTheme.ocean50 : Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: selected ? ResortTheme.ocean600 : ResortTheme.line),
        ),
        child: Row(
          children: [
            Expanded(child: Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600))),
            Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off, size: 18, color: selected ? ResortTheme.ocean600 : ResortTheme.muted),
          ],
        ),
      ),
    );
  }
}

class ResortShell extends StatefulWidget {
  const ResortShell({super.key, required this.repository, required this.onSignedOut});
  final ResortRepository repository;
  final VoidCallback onSignedOut;

  @override
  State<ResortShell> createState() => _ResortShellState();
}

class _ResortShellState extends State<ResortShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomePage(repository: widget.repository, onProfile: () => setState(() => _index = 3)),
      TripsPage(repository: widget.repository),
      StaysPage(repository: widget.repository),
      AccountPage(repository: widget.repository, onSignedOut: widget.onSignedOut),
    ];
    return Scaffold(
      body: pages[_index],
      floatingActionButton: FloatingActionButton(
        onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ChatPage(repository: widget.repository))),
        backgroundColor: ResortTheme.ocean600,
        foregroundColor: Colors.white,
        child: const Icon(Icons.chat_bubble_outline),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.calendar_month_outlined), selectedIcon: Icon(Icons.calendar_month), label: 'My Bookings'),
          NavigationDestination(icon: Icon(Icons.apartment_outlined), selectedIcon: Icon(Icons.apartment), label: 'Rooms'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}

class _SocialButtons extends StatelessWidget {
  const _SocialButtons({required this.busy, required this.onGoogle, required this.onFacebook});

  final bool busy;
  final VoidCallback onGoogle;
  final VoidCallback onFacebook;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        OutlinedButton.icon(
          onPressed: busy ? null : onGoogle,
          icon: const _GoogleMark(),
          label: const Text('Continue with Google'),
        ),
        const SizedBox(height: 8),
        FilledButton.icon(
          onPressed: busy ? null : onFacebook,
          style: FilledButton.styleFrom(backgroundColor: const Color(0xFF1877F2), foregroundColor: Colors.white),
          icon: const Icon(Icons.facebook),
          label: const Text('Continue with Facebook'),
        ),
        const SizedBox(height: 14),
        const Row(
          children: [
            Expanded(child: Divider()),
            Padding(padding: EdgeInsets.symmetric(horizontal: 10), child: Text('OR', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: ResortTheme.muted))),
            Expanded(child: Divider()),
          ],
        ),
        const SizedBox(height: 14),
      ],
    );
  }
}

class _GoogleMark extends StatelessWidget {
  const _GoogleMark();

  @override
  Widget build(BuildContext context) {
    return const Text('G', style: TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF4285F4)));
  }
}
