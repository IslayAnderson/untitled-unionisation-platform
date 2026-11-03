<?php

namespace App\Http\Controllers;

use App\Exceptions\ImageRejected;
use App\Services\ImageUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Image uploads from the rich text editor. Responds in the shape TinyMCE expects.
 */
class ImageUploadController extends Controller
{
    public function store(Request $request, ImageUploads $uploads): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:'.config('uploads.images.max_upload_kb')],
        ]);

        try {
            $image = $uploads->store($request->user()->contact, $request->file('file'));
        } catch (ImageRejected $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['location' => $image->url()], 201);
    }
}
