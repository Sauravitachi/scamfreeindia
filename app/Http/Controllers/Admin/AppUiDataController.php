<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permission;
use App\Http\Controllers\Controller;
use App\Models\AppUiData;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\DTO\Toast;
use App\Services\AppUiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppUiDataController extends \App\Foundation\Controller
{
    public function __construct(
        protected ResponseService $responseService,
        protected AppUiService $appUiService,
    ) {}

    public static function middleware(): array
    {
        return [
            permit(Permission::APP_UI_DATA_LIST, only: ['index']),
            permit(Permission::APP_UI_DATA_CREATE, only: ['create', 'store']),
            permit(Permission::APP_UI_DATA_UPDATE, only: ['edit', 'update']),
            permit(Permission::APP_UI_DATA_DELETE, only: ['destroy']),
        ];
    }

    public function index()
    {
        $data = AppUiData::all();
        return view('admin.app-ui-data.index', compact('data'));
    }

    public function create()
    {
        return view('admin.app-ui-data.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:app_ui_data,name',
            'data' => 'nullable|string',
        ]);

        $data = [];
        if ($request->filled('data')) {
            $data = json_decode($request->data, true) ?? [];
        }

        // Handle Structured Video Section
        if ($request->has('video_section')) {
            $videoSection = $request->input('video_section');
            if (isset($videoSection['cards'])) {
                foreach ($videoSection['cards'] as $index => &$card) {
                    // Handle Image Upload
                    if ($request->hasFile("video_section.cards.$index.image")) {
                        $card['image_url'] = $this->saveFile($request->file("video_section.cards.$index.image"), 'app_ui_data/video_section/images');
                    }
                    unset($card['image']);

                    // Handle PDF Upload
                    if ($request->hasFile("video_section.cards.$index.pdf")) {
                        $card['pdf_url'] = $this->saveFile($request->file("video_section.cards.$index.pdf"), 'app_ui_data/video_section/pdfs');
                    }
                    unset($card['pdf']);
                }
            }
            $data['video_section'] = $videoSection;
        }

        AppUiData::create([
            'name' => $validated['name'],
            'data' => $data,
        ]);

        // Clear cache for the new data
        $this->appUiService->clearCache($validated['name']);

        return $this->responseService->json(
            success: true, 
            toast: new Toast('success', 'UI Data created successfully!'),
            redirect: route('admin.app-ui-data.index')
        );
    }

    public function edit(AppUiData $appUiData)
    {
        return view('admin.app-ui-data.edit', compact('appUiData'));
    }

    public function update(Request $request, AppUiData $appUiData)
    {
        $validated = $request->validate([
            'name' => 'required|unique:app_ui_data,name,' . $appUiData->id,
            'data' => 'nullable|string',
        ]);

        $data = [];
        if ($request->filled('data')) {
            $data = json_decode($request->data, true) ?? [];
        } else {
            $data = $appUiData->data ?? [];
        }

        // Handle Structured Video Section
        if ($request->has('video_section')) {
            $videoSection = $request->input('video_section');
            $existingVideoSection = $appUiData->data['video_section'] ?? [];
            
            if (isset($videoSection['cards'])) {
                foreach ($videoSection['cards'] as $index => &$card) {
                    $oldCard = $existingVideoSection['cards'][$index] ?? [];
                    
                    // Handle Image Upload
                    if ($request->hasFile("video_section.cards.$index.image")) {
                        $this->deleteFile($oldCard['image_url'] ?? null);
                        $card['image_url'] = $this->saveFile($request->file("video_section.cards.$index.image"), 'app_ui_data/video_section/images');
                    } else {
                        $card['image_url'] = $videoSection['cards'][$index]['image_url'] ?? $oldCard['image_url'] ?? null;
                    }
                    unset($card['image']);

                    // Handle PDF Upload
                    if ($request->hasFile("video_section.cards.$index.pdf")) {
                        $this->deleteFile($oldCard['pdf_url'] ?? null);
                        $card['pdf_url'] = $this->saveFile($request->file("video_section.cards.$index.pdf"), 'app_ui_data/video_section/pdfs');
                    } else {
                        $card['pdf_url'] = $videoSection['cards'][$index]['pdf_url'] ?? $oldCard['pdf_url'] ?? null;
                    }
                    unset($card['pdf']);
                }
            }
            $data['video_section'] = $videoSection;
        }

        $appUiData->name = $validated['name'];
        $appUiData->data = $data;
        $appUiData->save();

        // Clear cache for the updated data
        $this->appUiService->clearCache($appUiData->name);
        if ($appUiData->getOriginal('name') !== $appUiData->name) {
            $this->appUiService->clearCache($appUiData->getOriginal('name'));
        }

        return $this->responseService->json(
            success: true, 
            toast: new Toast('success', 'UI Data updated successfully!'),
            redirect: route('admin.app-ui-data.index')
        );
    }

    protected function saveFile($file, $directory)
    {
        if (!$file) return null;
        $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = "public/{$directory}";
        $fullPath = $file->storeAs($path, $fileName);
        return Storage::url($fullPath);
    }

    protected function deleteFile($url)
    {
        if (empty($url)) return;
        $path = str_replace(Storage::url(''), 'public', $url);
        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    public function destroy(AppUiData $appUiData)
    {
        $name = $appUiData->name;
        $appUiData->delete();

        // Clear cache
        $this->appUiService->clearCache($name);

        return $this->responseService->json(
            success: true, 
            toast: new Toast('success', 'UI Data deleted successfully!')
        );
    }
}
