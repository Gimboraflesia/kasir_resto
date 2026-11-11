<?php

namespace App\Http\Controllers;

use App\Services\MidtransNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransNotificationService $notifications): JsonResponse
    {
        if (! $notifications->handle($request->all())) {
            return response()->json(['message' => 'Invalid notification'], 403);
        }

        return response()->json(['message' => 'OK']);
    }
}
