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
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
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
            TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email')),
            const SizedBox(height: 12),
            TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Password')),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: Color(0xFFB91C1C))),
            ],
            const SizedBox(height: 20),
            FilledButton(onPressed: _busy ? null : _submit, child: Text(_busy ? 'Signing in' : 'Sign in')),
            TextButton(
              onPressed: () => Navigator.push(context, MaterialPageRoute(
                builder: (_) => RegisterScreen(repository: widget.repository, onSignedIn: widget.onSignedIn),
              )),
              child: const Text('Create an account'),
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
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  String? _message;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _message = null;
    });
    try {
      await widget.repository.register(
        name: _name.text.trim(),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create account')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          TextField(controller: _name, decoration: const InputDecoration(labelText: 'Full name')),
          const SizedBox(height: 12),
          TextField(controller: _email, decoration: const InputDecoration(labelText: 'Email')),
          const SizedBox(height: 12),
          TextField(controller: _phone, decoration: const InputDecoration(labelText: 'Mobile number')),
          const SizedBox(height: 12),
          TextField(controller: _password, obscureText: true, decoration: const InputDecoration(labelText: 'Password (8 characters)')),
          if (_message != null) ...[
            const SizedBox(height: 12),
            Text(_message!),
          ],
          const SizedBox(height: 20),
          FilledButton(onPressed: _busy ? null : _submit, child: const Text('Register')),
        ],
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
      HomePage(repository: widget.repository),
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
          NavigationDestination(icon: Icon(Icons.calendar_month_outlined), selectedIcon: Icon(Icons.calendar_month), label: 'Bookings'),
          NavigationDestination(icon: Icon(Icons.apartment_outlined), selectedIcon: Icon(Icons.apartment), label: 'Rooms'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}
