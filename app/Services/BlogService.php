<?php

namespace App\Services;

use App\Http\Requests\Admin\BlogRequest;
use App\Models\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

class BlogService extends Service
{
    public function dataTable(): EloquentDataTable
    {
        $query = Blog::with('author');

        $table = datatables()->eloquent($query);

        $table->editColumn('status', function (Blog $blog) {
            $class = match($blog->status) {
                'published' => 'bg-success',
                'draft' => 'bg-warning',
                'scheduled' => 'bg-info',
                default => 'bg-secondary'
            };
            return '<span class="badge ' . $class . '">' . ucfirst($blog->status) . '</span>';
        });

        $table->editColumn('is_featured', function (Blog $blog) {
            return $blog->is_featured ? '<span class="badge bg-primary">Yes</span>' : '<span class="badge bg-secondary">No</span>';
        });

        $table->editColumn('published_at', function (Blog $blog) {
            return $blog->published_at ? $blog->published_at->format('Y-m-d H:i') : 'N/A';
        });

        $table->rawColumns(['status', 'is_featured']);

        return $table;
    }

    public function create(BlogRequest $request): Blog
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        $data['author_id'] = auth()->id();

        return Blog::create($data);
    }

    public function update(Blog $blog, BlogRequest $request): Blog|bool
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $blog->fill($data);
        if ($blog->isDirty()) {
            $blog->save();
            return $blog;
        }

        return false;
    }

    public function delete(Blog $blog): ?bool
    {
        return $blog->delete();
    }
}
