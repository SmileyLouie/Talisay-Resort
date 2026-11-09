<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

#[Fillable(['code', 'name', 'kind', 'instructions', 'account_name', 'account_number', 'icon', 'is_system', 'is_active', 'sort_order'])]

class PaymentMethod extends Model
{
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public static function ready(): bool
    {
        return Schema::hasTable((new static)->getTable());
    }

    /**
     * Methods a guest can choose. A transfer stays off the booking form until it has an account.
     */
    public static function catalog(): \Illuminate\Support\Collection
    {
        if (! static::ready()) {
            return static::catalogFallback();
        }

        $rows = static::active()->get()->filter(fn (self $row) => $row->offeredToGuests())->values();

        return $rows->isEmpty() ? static::catalogFallback() : $rows;
    }

    public static function lookup(?string $code): ?self
    {
        if (! static::ready() || $code === null || $code === '') {
            return null;
        }

        return static::query()->where('code', $code)->first();
    }

    public static function labels(): array
    {
        if (! static::ready()) {
            return static::catalogFallback()->mapWithKeys(fn ($row) => [$row->code => $row->name])->all();
        }

        return static::query()->orderBy('sort_order')->pluck('name', 'code')->all();
    }

    public function offeredToGuests(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (in_array($this->kind, ['cash', 'card'], true)) {
            return true;
        }

        return $this->receivingNumber() !== '';
    }

    public function receivingNumber(): string
    {
        $number = trim((string) $this->account_number);
        if ($number === '' && $this->code === 'gcash') {
            $number = trim((string) SystemSetting::get('gcash_number', ''));
        }

        return $number;
    }

    public function receivingName(): string
    {
        $name = trim((string) $this->account_name);
        if ($name === '' && $this->code === 'gcash') {
            $name = trim((string) SystemSetting::get('gcash_name', ''));
        }

        return $name;
    }

    public static function details(object $method): array
    {
        if ($method instanceof self) {
            return [
                'name' => $method->receivingName(),
                'number' => $method->receivingNumber(),
                'instructions' => (string) ($method->instructions ?? ''),
            ];
        }

        $name = trim((string) ($method->account_name ?? ''));
        $number = trim((string) ($method->account_number ?? ''));
        if (($method->code ?? '') === 'gcash') {
            $name = $name !== '' ? $name : trim((string) SystemSetting::get('gcash_name', ''));
            $number = $number !== '' ? $number : trim((string) SystemSetting::get('gcash_number', ''));
        }

        return [
            'name' => $name,
            'number' => $number,
            'instructions' => (string) ($method->instructions ?? ''),
        ];
    }

    public static function kindLabel(?string $kind): string
    {
        return match ($kind) {
            'cash' => 'Cash at the resort',
            'ewallet' => 'E-wallet',
            'bank' => 'Bank transfer',
            'card' => 'Debit or credit card',
            'online' => 'Other online payment',
            default => 'Payment',
        };
    }

    /**
     * Philippine options an admin can turn on. The kind decides what the guest is asked to do.
     */
    public static function presets(): array
    {
        $ewallet = 'Send the amount to this account, then upload the reference number and the receipt.';
        $bank = 'Transfer with InstaPay or PESONet, then upload the reference number and the receipt.';
        $online = 'Pay with this service, then upload the reference number and the receipt.';

        return [
            ['group' => 'E-wallets', 'code' => 'gcash', 'name' => 'GCash', 'kind' => 'ewallet', 'icon' => 'bi-phone-fill', 'sort_order' => 10, 'instructions' => 'Pay the resort GCash account, then upload the 13-digit reference and receipt.'],
            ['group' => 'E-wallets', 'code' => 'maya', 'name' => 'Maya', 'kind' => 'ewallet', 'icon' => 'bi-phone-fill', 'sort_order' => 11, 'instructions' => $ewallet],
            ['group' => 'E-wallets', 'code' => 'grabpay', 'name' => 'GrabPay', 'kind' => 'ewallet', 'icon' => 'bi-phone-fill', 'sort_order' => 12, 'instructions' => $ewallet],
            ['group' => 'E-wallets', 'code' => 'shopeepay', 'name' => 'ShopeePay', 'kind' => 'ewallet', 'icon' => 'bi-phone-fill', 'sort_order' => 13, 'instructions' => $ewallet],
            ['group' => 'E-wallets', 'code' => 'coinsph', 'name' => 'Coins.ph', 'kind' => 'ewallet', 'icon' => 'bi-phone-fill', 'sort_order' => 14, 'instructions' => $ewallet],
            ['group' => 'Banks', 'code' => 'bdo', 'name' => 'BDO', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 20, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'bpi', 'name' => 'BPI', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 21, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'metrobank', 'name' => 'Metrobank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 22, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'unionbank', 'name' => 'UnionBank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 23, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'landbank', 'name' => 'Landbank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 24, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'securitybank', 'name' => 'Security Bank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 25, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'rcbc', 'name' => 'RCBC', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 26, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'pnb', 'name' => 'PNB', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 27, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'chinabank', 'name' => 'China Bank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 28, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'eastwest', 'name' => 'EastWest', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 29, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'psbank', 'name' => 'PSBank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 30, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'aub', 'name' => 'Asia United Bank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 31, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'cimb', 'name' => 'CIMB', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 32, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'seabank', 'name' => 'SeaBank', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 33, 'instructions' => $bank],
            ['group' => 'Banks', 'code' => 'gotyme', 'name' => 'GoTyme', 'kind' => 'bank', 'icon' => 'bi-bank', 'sort_order' => 34, 'instructions' => $bank],
            ['group' => 'Cards and online', 'code' => 'card', 'name' => 'Debit or credit card', 'kind' => 'card', 'icon' => 'bi-credit-card-fill', 'sort_order' => 40, 'instructions' => 'The resort records the last 4 digits of the card. The full number is not stored.'],
            ['group' => 'Cards and online', 'code' => 'paypal', 'name' => 'PayPal', 'kind' => 'online', 'icon' => 'bi-globe2', 'sort_order' => 41, 'instructions' => $online],
            ['group' => 'Cards and online', 'code' => 'qrph', 'name' => 'QR Ph', 'kind' => 'online', 'icon' => 'bi-qr-code', 'sort_order' => 42, 'instructions' => 'Pay the resort QR Ph code, then upload the reference number and the receipt.'],
            ['group' => 'At the resort', 'code' => 'cash', 'name' => 'Cash on arrival', 'kind' => 'cash', 'icon' => 'bi-cash-stack', 'sort_order' => 50, 'instructions' => 'Pay in full at the front desk when you check in.'],
        ];
    }

    public static function preset(string $code): ?array
    {
        foreach (static::presets() as $preset) {
            if ($preset['code'] === $code) {
                return $preset;
            }
        }

        return null;
    }

    private static function catalogFallback(): \Illuminate\Support\Collection
    {
        return collect([
            (object) ['code' => 'gcash', 'name' => 'GCash', 'kind' => 'ewallet', 'icon' => 'bi-phone-fill', 'account_name' => null, 'account_number' => null, 'instructions' => null],
            (object) ['code' => 'cash', 'name' => 'Cash on arrival', 'kind' => 'cash', 'icon' => 'bi-cash-stack', 'account_name' => null, 'account_number' => null, 'instructions' => null],
            (object) ['code' => 'card', 'name' => 'Card', 'kind' => 'card', 'icon' => 'bi-credit-card-fill', 'account_name' => null, 'account_number' => null, 'instructions' => null],
        ]);
    }

    public static function activeCodes(): array
    {
        $codes = static::catalog()->pluck('code')->all();

        return $codes ?: ['cash', 'gcash', 'card'];
    }

    public static function kindOf(?string $code): string
    {
        $code = (string) $code;
        $row = static::lookup($code);
        if ($row) {
            return $row->kind;
        }

        return match ($code) {
            'cash' => 'cash',
            'card' => 'card',
            'gcash', 'maya', 'grabpay', 'shopeepay', 'coinsph' => 'ewallet',
            'paypal', 'qrph' => 'online',
            'bdo', 'bpi', 'metrobank', 'unionbank', 'landbank', 'securitybank', 'rcbc', 'pnb', 'chinabank', 'eastwest', 'psbank', 'aub', 'cimb', 'seabank', 'gotyme' => 'bank',
            default => 'cash',
        };
    }

    public static function isCash(?string $code): bool
    {
        return static::kindOf($code) === 'cash';
    }

    public static function isCard(?string $code): bool
    {
        return static::kindOf($code) === 'card';
    }

    public static function needsProof(?string $code): bool
    {
        return in_array(static::kindOf($code), ['ewallet', 'bank', 'online'], true);
    }

    public static function allowsDownPayment(?string $code): bool
    {
        return ! static::isCash($code);
    }

    public static function methodRule(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', Rule::in(static::activeCodes())];
    }

    public static function extraRules(?string $method): array
    {
        return [
            'payment_plan' => 'nullable|in:full,down',
            'gcash_method' => 'nullable|in:qr,receipt',
            'card_last_four' => [
                Rule::requiredIf(fn () => static::isCard($method)),
                'nullable',
                'digits:4',
            ],
            'reference_number' => [
                Rule::requiredIf(fn () => static::needsProof($method)),
                'nullable',
                'string',
                'max:40',
            ],
            'proof_image' => [
                Rule::requiredIf(fn () => static::needsProof($method)),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public static function proofMessages(): array
    {
        return [
            'reference_number.required' => 'Enter the transaction reference from your receipt.',
            'reference_number.required_if' => 'Enter the transaction reference from your receipt.',
            'proof_image.required' => 'Upload the payment receipt screenshot.',
            'proof_image.required_if' => 'Upload the payment receipt screenshot.',
            'proof_image.image' => 'The receipt screenshot must be a JPG, PNG, or WEBP image.',
        ];
    }

    public static function proofError(Request $request): ?string
    {
        $method = (string) $request->input('payment_method');
        if (! static::needsProof($method)) {
            return null;
        }

        $raw = trim((string) $request->input('reference_number'));
        $digits = preg_replace('/\D+/', '', $raw);
        if ($method === 'gcash' && ! preg_match('/^\d{13}$/', (string) $digits)) {
            return 'Enter the 13-digit GCash transaction reference number.';
        }
        if ($method !== 'gcash' && strlen($raw) < 6) {
            return 'Enter the transaction reference from your receipt.';
        }
        if (! $request->hasFile('proof_image')) {
            return 'Upload the payment receipt screenshot.';
        }

        return null;
    }
}
