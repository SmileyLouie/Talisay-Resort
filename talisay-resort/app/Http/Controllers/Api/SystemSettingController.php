<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $request->validate(SystemSetting::validationRules(), SystemSetting::validationMessages());

        foreach ($request->settings as $key => $value) {
            if ($value === null) {
                continue;
            }
            if ($key === 'ui_locale' && ! \App\Support\UiLocales::allows((string) $value)) {
                continue;
            }
            SystemSetting::set($key, $value);
        }

        if (array_key_exists('daily_visitor_cap', $request->settings)) {
            \App\Models\CapacitySchedule::query()->update([
                'max_capacity' => \App\Models\CapacitySchedule::visitorCap(),
            ]);
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}
