<?php

namespace App\Http\Controllers;

use App\Enums\NoteColor;
use Illuminate\Http\JsonResponse;

class ColorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Color options retrieved successfully.',
            'data' => NoteColor::options(),
        ]);
    }
}
