<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductMediaRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductMediaController extends Controller
{
    public function store(StoreProductMediaRequest $request, Product $product, MarketplaceContext $context): JsonResponse
    {
        $context->assertMatches($product->country_id);
        $this->authorize('update', $product);
        if (! in_array($product->status, ['draft', 'rejected'], true)) return response()->json(['message' => 'Media may only be changed while the product is a draft.'], 422);
        $file = $request->file('file');
        $type = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video';
        $path = $file->store("products/{$product->id}", ['disk' => config('filesystems.default'), 'visibility' => 'private']);
        $media = $product->media()->create(['disk' => config('filesystems.default'), 'path' => $path, 'media_type' => $type, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sort_order' => $product->media()->count()]);
        return response()->json(['media' => $media], 201);
    }
}
