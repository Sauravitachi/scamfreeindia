<?php

namespace App\Services;

use App\Models\AppUiData;
use Illuminate\Support\Facades\Cache;

class AppUiService extends Service
{
    /**
     * Get UI data by name and optionally a key within that data.
     *
     * @param string $name The unique identifier for the UI section (e.g., 'hero_section')
     * @param string|null $key The specific key within the JSON data
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $name, ?string $key = null, $default = null)
    {
        // Cache the data to avoid frequent DB hits
        $uiData = Cache::rememberForever("app_ui_data_{$name}", function () use ($name) {
            return AppUiData::where('name', $name)->first();
        });

        if (!$uiData) {
            return $default;
        }

        $data = $uiData->data ?? [];

        if ($key) {
            return data_get($data, $key, $default);
        }

        return $data;
    }

    /**
     * Retrieve the hero section data with defaults.
     *
     * @return array
     */
    public function getHeroSection()
    {
        return $this->get('hero_section', null, [
            'hero_section_title' => null,
            'hero_section_video' => null,
            'hero_section_description' => null,
            'hero_section_button_text' => null,
            'hero_section_button_url' => null,
        ]);
    }

    /**
     * Clear cache for a specific UI section.
     * Use this when updating UI data in the controller.
     *
     * @param string $name
     */
    public function clearCache(string $name): void
    {
        Cache::forget("app_ui_data_{$name}");
    }
}
