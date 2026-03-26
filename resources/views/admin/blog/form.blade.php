<form action="{{ $action }}" method="POST" id="blog-form">
    @csrf
    @isset($blog)
        @method('PUT')
    @endisset

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label required">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ $blog->title ?? '' }}" placeholder="Enter blog title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (Optional)</label>
                        <input type="text" name="slug" class="form-control" value="{{ $blog->slug ?? '' }}" placeholder="auto-generated-from-title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Summary</label>
                        <textarea name="summary" class="form-control" rows="3" placeholder="Brief summary of the post">{{ $blog->summary ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Content</label>
                        <textarea name="content" id="blog-content" class="form-control">{{ $blog->content ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SEO Metadata</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="{{ $blog->meta_title ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2">{{ $blog->meta_description ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control" value="{{ $blog->meta_keywords ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Publishing</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" {{ (isset($blog) && $blog->status == 'draft') ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ (isset($blog) && $blog->status == 'published') ? 'selected' : '' }}>Published</option>
                            <option value="scheduled" {{ (isset($blog) && $blog->status == 'scheduled') ? 'selected' : '' }}>Scheduled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Published At</label>
                        <input type="datetime-local" name="published_at" class="form-control" value="{{ isset($blog) && $blog->published_at ? $blog->published_at->format('Y-m-d\TH:i') : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" {{ (isset($blog) && $blog->is_featured) ? 'checked' : '' }}>
                            <span class="form-check-label">Is Featured?</span>
                        </label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('admin.blog.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Blog</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Featured Image</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="text" name="featured_image" class="form-control" value="{{ $blog->featured_image ?? '' }}" placeholder="https://example.com/image.jpg">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('style')
<style>
    .ck-editor__editable_inline {
        min-height: 400px;
    }
</style>
@endpush

@push('script')
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
    $(document).ready(function() {
        let editor;
        ClassicEditor
            .create(document.querySelector('#blog-content'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
            })
            .then(newEditor => {
                editor = newEditor;
            })
            .catch(error => {
                console.error(error);
            });

        // Auto-slug generation
        $('input[name="title"]').on('keyup', function() {
            let title = $(this).val();
            let slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove non-word chars
                .replace(/[\s_]+/g, '-')   // Replace spaces/underscores with -
                .replace(/^-+|-+$/g, '');  // Trim leading/trailing -
            $('input[name="slug"]').val(slug);
        });

        $('#blog-form').on('submit', function(e) {
            e.preventDefault();
            
            // Sync editor data to textarea
            if (editor) {
                $('#blog-content').val(editor.getData());
            }
            
            runAjax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                data: $(this).serialize(),
                handleToast: true,
                success: function(response) {
                    if (response.redirectTo) {
                        window.location.href = response.redirectTo;
                    }
                }
            });
        });
    });
</script>
@endpush
