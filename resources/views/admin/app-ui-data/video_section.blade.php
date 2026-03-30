<div class="card mb-4 border-primary">
    <div class="card-header bg-primary-lt">
        <h3 class="card-title">General Section Info</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Section Main Title</label>
                <input type="text" name="video_section[main_title]" class="form-control" value="{{ $appUiData->data['video_section']['main_title'] ?? '' }}" placeholder="e.g., Our Video Gallery">
            </div>
            <div class="col-md-6">
                <label class="form-label">Section Sub Title</label>
                <input type="text" name="video_section[sub_title]" class="form-control" value="{{ $appUiData->data['video_section']['sub_title'] ?? '' }}" placeholder="e.g., Watch our latest awareness videos">
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 border-info">
    <div class="card-header d-flex justify-content-between align-items-center bg-info-lt">
        <h3 class="card-title">Video Cards / Items</h3>
        <button type="button" class="btn btn-sm btn-info" id="add-video-card">
            <i class="ti ti-plus"></i> Add New Item
        </button>
    </div>
    <div class="card-body p-0">
        <div id="video-cards-container" class="p-3">
            {{-- This will be populated by JavaScript --}}
        </div>
        <div id="no-cards-message" class="text-center p-5 d-none">
            <div class="text-secondary mb-2"><i class="ti ti-video-off fs-1"></i></div>
            <p class="text-muted">No video cards added yet. Click "Add New Item" to start.</p>
        </div>
    </div>
</div>

{{-- Template for a Video Card --}}
<template id="video-card-template">
    <div class="card mb-3 video-card shadow-sm border-0 bg-light-gray">
        <div class="card-header p-2 d-flex justify-content-between bg-dark text-white rounded-top">
            <span class="fw-bold fs-5 ms-2 mt-1 card-index-label">Item #1</span>
            <button type="button" class="btn btn-sm btn-link text-danger remove-video-card p-0 me-2" title="Remove Item">
                <i class="ti ti-x fs-2"></i>
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="video_section[cards][INDEX][title]" class="form-control form-control-lg" required placeholder="Enter item title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Video Link (YouTube / Vimeo / Direct)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-brand-youtube text-danger"></i></span>
                            <input type="url" name="video_section[cards][INDEX][video_url]" class="form-control" placeholder="https://youtube.com/...">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="video_section[cards][INDEX][description]" class="form-control resize-none" rows="3" placeholder="Tell more about this video..."></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Cover / Thumbnail Image</label>
                        <div class="image-upload-wrapper border-dashed rounded p-2 text-center bg-white position-relative">
                            <input type="file" class="form-control image-input position-absolute opacity-0 w-100 h-100 top-0 left-0 cursor-pointer" accept="image/*" name="video_section[cards][INDEX][image]" style="z-index: 2;">
                            <div class="image-placeholder py-3">
                                <i class="ti ti-photo-plus fs-1 text-secondary"></i>
                                <p class="small text-muted mb-0">Click or drag image</p>
                            </div>
                            <div class="mt-2 image-preview-container d-none position-relative" style="z-index: 1;">
                                <img src="" class="img-fluid rounded shadow-sm" style="max-height: 180px;">
                                <div class="mt-1 small text-info"><i class="ti ti-check"></i> Image selected</div>
                            </div>
                            <input type="hidden" name="video_section[cards][INDEX][image_url]" class="existing-image-url">
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label fw-bold">Attached Document (PDF)</label>
                        <div class="pdf-upload-wrapper border border-dashed rounded p-3 bg-white">
                            <input type="file" class="form-control pdf-input mb-2" accept="application/pdf" name="video_section[cards][INDEX][pdf]">
                            <div class="pdf-preview-container d-none mt-2">
                                <div class="p-2 border rounded bg-light-blue d-flex align-items-center">
                                    <i class="ti ti-file-type-pdf text-danger fs-3 me-2"></i>
                                    <div class="flex-fill overflow-hidden">
                                        <small class="d-block fw-bold text-truncate pdf-filename">filename.pdf</small>
                                        <div class="d-flex gap-2 mt-1">
                                            <a href="#" target="_blank" class="btn btn-ghost-primary btn-xs view-pdf-link">
                                                <i class="ti ti-external-link"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 pdf-embed-viewer d-none rounded border overflow-hidden shadow-sm">
                                    <embed src="" type="application/pdf" width="100%" height="200px" class="pdf-embed">
                                </div>
                            </div>
                            <input type="hidden" name="video_section[cards][INDEX][pdf_url]" class="existing-pdf-url">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
    .bg-light-gray { background-color: #f8fafc; }
    .bg-light-blue { background-color: #f0f7ff; }
    .btn-xs { padding: 0.15rem 0.4rem; font-size: 0.75rem; }
    .border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #cbd5e1 !important; }
    .cursor-pointer { cursor: pointer; }
    .image-upload-wrapper:hover { border-color: var(--tblr-primary) !important; background-color: #f1f5f9; }
</style>

@push('script')
<script>
$(document).ready(function() {
    let cardCount = 0;
    const container = $('#video-cards-container');
    const template = $('#video-card-template').html();

    // Load existing data if available
    const rawData = @json($appUiData->data['video_section'] ?? []);
    const existingCards = rawData.cards || [];
    
    if (existingCards.length > 0) {
        existingCards.forEach(data => addCard(data));
    } else {
        $('#no-cards-message').removeClass('d-none');
    }

    $('#add-video-card').on('click', function() {
        addCard();
        $('#no-cards-message').addClass('d-none');
    });

    function addCard(data = null) {
        const index = cardCount++;
        let cardHtml = template.replace(/INDEX/g, index);
        const $card = $(cardHtml);
        
        container.append($card);
        updateLabels();

        if (data) {
            $card.find('[name$="[title]"]').val(data.title || '');
            $card.find('[name$="[video_url]"]').val(data.video_url || '');
            $card.find('[name$="[description]"]').val(data.description || '');
            
            if (data.image_url) {
                $card.find('.existing-image-url').val(data.image_url);
                $card.find('.image-preview-container').removeClass('d-none').find('img').attr('src', data.image_url);
                $card.find('.image-placeholder').addClass('d-none');
            }
            
            if (data.pdf_url) {
                $card.find('.existing-pdf-url').val(data.pdf_url);
                $card.find('.pdf-preview-container').removeClass('d-none');
                $card.find('.pdf-filename').text(data.pdf_url.split('/').pop());
                $card.find('.view-pdf-link').attr('href', data.pdf_url);
                $card.find('.pdf-embed').attr('src', data.pdf_url);
                $card.find('.pdf-embed-viewer').removeClass('d-none');
            }
        }
    }

    $(document).on('click', '.remove-video-card', function() {
        const $card = $(this).closest('.video-card');
        Popup.askConfirmation({
            variant: 'danger',
            message: 'Remove this video item? This action is not reversible until you save the form.',
            onConfirm: function() {
                $card.fadeOut(300, function() {
                    $(this).remove();
                    updateLabels();
                    if ($('.video-card').length === 0) {
                        $('#no-cards-message').removeClass('d-none');
                    }
                });
            }
        });
    });

    function updateLabels() {
        $('.video-card').each(function(i) {
            $(this).find('.card-index-label').html('<i class="ti ti-video me-1"></i> Item #' + (i + 1));
        });
    }

    // Image Preview with Placeholder swap
    $(document).on('change', '.image-input', function(e) {
        const file = e.target.files[0];
        const $wrapper = $(this).closest('.image-upload-wrapper');
        const $preview = $wrapper.find('.image-preview-container');
        const $placeholder = $wrapper.find('.image-placeholder');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $preview.removeClass('d-none').find('img').attr('src', e.target.result);
                $placeholder.addClass('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

    // Dedicated PDF Preview FIX
    $(document).on('change', '.pdf-input', function(e) {
        const file = e.target.files[0];
        const $container = $(this).siblings('.pdf-preview-container');
        const $filename = $container.find('.pdf-filename');
        const $viewLink = $container.find('.view-pdf-link');
        const $embedViewer = $container.find('.pdf-embed-viewer');
        const $embed = $container.find('.pdf-embed');
        
        if (file) {
            if (file.type !== 'application/pdf') {
                Popup.toast('warning', 'Please select a valid PDF file.');
                $(this).val('');
                return;
            }

            const fileUrl = URL.createObjectURL(file);
            $container.removeClass('d-none');
            $filename.text(file.name);
            $viewLink.attr('href', fileUrl);
            $embedViewer.removeClass('d-none');
            $embed.attr('src', fileUrl);
        }
    });
});
</script>
@endpush
