<?php

namespace App\Services;

use App\Constants\Setting as SettingConstant;
use App\Http\Requests\Admin\BusinessSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SettingService extends Service
{
    public function set(string|SettingConstant $key, $value, string $tag): void
    {
        $key = is_string($key) ? $key : $key->value;
        Setting::updateOrCreate(['key' => $key, 'tag' => $tag], ['value' => $value]);
    }

    public function get(string|SettingConstant $key, $default = null)
    {
        $key = is_string($key) ? $key : $key->value;
        $settings = Setting::where('key', $key)->first(['value']);

        return $settings ? $settings->value : $default;
    }

    public function getMultiple(array $keys): Collection
    {
        $keys = array_map(fn ($key) => is_string($key) ? $key : $key->value, $keys);

        return Setting::whereIn('key', $keys)->get(['id', 'key', 'value'])->keyBy('key');
    }

    public function updatePanelLoginSetting(Request $request): bool
    {
        $status = (bool) $request->input('panel_login', true);
        $this->set('panel_login', $status, 'login');

        ActivityLogService::getInstance()->changedSetting($status ? 'Enabled Panel Login' : 'Disabled Panel Login');

        return true;
    }

    public function updateBusinessSetting(BusinessSettingsRequest $request): void
    {
        $keys = Setting::where('tag', 'business')->pluck('key')->toArray();
        $keys = array_unique(array_merge($keys, array_keys($request->validated())));

        foreach ($keys as $key) {
            $newValue = $request->validated($key);
            if (is_array($newValue)) {
                $newValue = json_encode($newValue);
            }
            $this->set($key, $newValue, 'business');
        }
    }
}
