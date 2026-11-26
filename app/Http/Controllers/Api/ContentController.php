<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\StoreContentRequest;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Http\Resources\ContentResource;
use App\Models\Content;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ContentController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(ContentResource::collection(Content::with('plans')->latest()->get()));
    }

    public function show(Content $content): JsonResponse
    {
        return $this->success(new ContentResource($content->load('plans')));
    }

    public function store(StoreContentRequest $request): JsonResponse
    {
        $content = Content::create($request->safe()->except('plan_ids'));
        $content->plans()->sync($request->validated('plan_ids'));

        return $this->success(new ContentResource($content->load('plans')), 'Konten dibuat.', 201);
    }

    public function update(UpdateContentRequest $request, Content $content): JsonResponse
    {
        $content->update($request->safe()->except('plan_ids'));

        if ($request->has('plan_ids')) {
            $content->plans()->sync($request->validated('plan_ids'));
        }

        return $this->success(new ContentResource($content->load('plans')), 'Konten diperbarui.');
    }

    public function destroy(Content $content): JsonResponse
    {
        $content->delete();

        return $this->success(null, 'Konten dihapus.');
    }

    // Hanya berhasil jika paket aktif user termasuk dalam content_plan; selain itu 403
    public function watch(Content $content): JsonResponse
    {
        Gate::authorize('watch', $content);

        return $this->success([
            'content' => new ContentResource($content),
            'stream_url' => 'https://stream.example.test/watch/'.$content->id, // simulasi
        ], 'Selamat menonton.');
    }
}
