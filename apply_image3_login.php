<?php
$content = file_get_contents('exact_image3_login.blade.php');

$search = "                <button type=\"submit\" class=\"auth-submit-pill\">\n                    <i class=\"bi bi-box-arrow-in-right\"></i>\n                    <span>Log In</span>\n                </button>\n            </form>\n\n\n\n            {{-- Register Prompt";

$replace = "                <button type=\"submit\" class=\"auth-submit-pill\">\n                    <i class=\"bi bi-box-arrow-in-right\"></i>\n                    <span>Log In</span>\n                </button>\n            </form>\n\n            {{-- Portal Switcher Link --}}\n            @if(\$currentPortal === 'admin')\n            <div class=\"portal-switch-box\">\n                <span>Not an administrator?</span>\n                <a href=\"{{ route('login', ['portal' => 'client']) }}\">\n                    <i class=\"bi bi-arrow-right-circle\"></i> Switch to Guest & Staff Login\n                </a>\n            </div>\n            @else\n            <div class=\"portal-switch-box\">\n                <span>Are you an administrator?</span>\n                <a href=\"{{ route('login', ['portal' => 'admin']) }}\">\n                    <i class=\"bi bi-arrow-right-circle\"></i> Switch to Admin Login\n                </a>\n            </div>\n            @endif\n\n            {{-- Register Prompt";

if (strpos($content, $search) !== false) {
    $content = str_replace($search, $replace, $content);
    echo "Replaced with portal switcher link successfully!\n";
} else {
    echo "Warning: search pattern not found directly, checking variations...\n";
    // Let's do regex replacement
    $pattern = '/(<button type="submit" class="auth-submit-pill">.*?<\/form>)(.*?)(<\!--|{{--\s*Register Prompt)/s';
    $replacement = "$1\n\n            {{-- Portal Switcher Link --}}\n            @if(\$currentPortal === 'admin')\n            <div class=\"portal-switch-box\">\n                <span>Not an administrator?</span>\n                <a href=\"{{ route('login', ['portal' => 'client']) }}\">\n                    <i class=\"bi bi-arrow-right-circle\"></i> Switch to Guest & Staff Login\n                </a>\n            </div>\n            @else\n            <div class=\"portal-switch-box\">\n                <span>Are you an administrator?</span>\n                <a href=\"{{ route('login', ['portal' => 'admin']) }}\">\n                    <i class=\"bi bi-arrow-right-circle\"></i> Switch to Admin Login\n                </a>\n            </div>\n            @endif\n\n            $3";
    $content = preg_replace($pattern, $replacement, $content);
}

file_put_contents('resources/views/auth/login.blade.php', $content);
echo "login.blade.php updated successfully! Bytes: " . strlen($content) . "\n";
