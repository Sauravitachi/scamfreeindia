<div class="card">
    <div class="card-body">
        <form action="{{ $action }}" method="{{ $method ?? 'POST' }}" id="app-ui-data-form" enctype="multipart/form-data">
            @csrf
            @if(isset($method) && $method == 'PUT')
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="name" class="form-label">Name (Identifier)</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $appUiData->name ?? old('name') }}" required placeholder="e.g., hero_section">
            </div>

            <div class="mb-3 {{ (isset($appUiData->name) && $appUiData->name === 'video_section') ? 'd-none' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="data" class="form-label mb-0">Data (JSON String)</label>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-info" id="format-json">Format JSON</button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle ms-1" type="button" data-bs-toggle="dropdown">
                                Templates
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item template-btn" href="javascript:;" data-template="hero">Hero Section</a></li>
                                <li><a class="dropdown-item template-btn" href="javascript:;" data-template="seo">SEO Meta</a></li>
                                <li><a class="dropdown-item template-btn" href="javascript:;" data-template="contact">Contact Info</a></li>
                                <li><a class="dropdown-item template-btn" href="javascript:;" data-template="video">Video Section</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <textarea class="form-control" id="data" name="data" rows="12" style="font-family: monospace; font-size: 13px;">{{ isset($appUiData) ? json_encode($appUiData->data, JSON_PRETTY_PRINT) : old('data') }}</textarea>
                <div class="form-text">Paste or type your JSON configuration. Click "Format" to prettify.</div>
            </div>

            <div class="mb-3">
                @if(isset($appUiData->name) && $appUiData->name === 'video_section')
                    @include('admin.app-ui-data.video_section')
                @endif
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary d-flex align-items-center">
                    <i class="ti ti-device-floppy me-1"></i> Save Configuration
                </button>
            </div>
        </form>
    </div>
</div>

@push('script')
    <script>
        const templates = {
            hero: {
                "hero_section_title": "Welcome to Our Portal",
                "hero_section_video": "https://example.com/video.mp4",
                "hero_section_description": "Building a safer India, one step at a time.",
                "hero_section_button_text": "Join Now",
                "hero_section_button_url": "/register"
            },
            seo: {
                "meta_title": "Home | ScamFreeIndia",
                "meta_description": "Protecting citizens from digital scams and fraud.",
                "og_image": "/assets/images/og-home.jpg"
            },
            contact: {
                "email": "support@scamfreeindia.com",
                "phone": "+91 1234567890",
                "address": "New Delhi, India"
            },
            video: {
                "video_section": {
                    "main_title": "Video Gallery",
                    "sub_title": "Watch our stories",
                    "cards": []
                }
            }
        };

        $(document).ready(function() {
            $('#format-json').on('click', function() {
                const dataStr = $('#data').val();
                if (!dataStr.trim()) return;
                try {
                    const obj = JSON.parse(dataStr);
                    $('#data').val(JSON.stringify(obj, null, 4));
                } catch (e) {
                    Popup.toast('danger', 'Invalid JSON format! Cannot prettify.');
                }
            });

            $('.template-btn').on('click', function() {
                const template = $(this).data('template');
                $('#data').val(JSON.stringify(templates[template], null, 4));
                $('#name').val($(this).text().toLowerCase().replace(' ', '_'));
            });

            $('#app-ui-data-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const method = form.attr('method');
                const formData = new FormData(this);

                // Validate JSON before sending
                const dataStr = $('#data').val();
                if (dataStr.trim()) {
                    try {
                        JSON.parse(dataStr);
                    } catch (e) {
                        Popup.toast('danger', 'Invalid JSON format! Please fix before saving.');
                        return;
                    }
                }

                runAjax({
                    url: url,
                    method: 'POST', // Use POST with _method spoofing if PUT
                    data: formData,
                    processData: false,
                    contentType: false,
                    handleToast: true,
                    handleRedirect: true,
                });
            });
        });
    </script>
@endpush
