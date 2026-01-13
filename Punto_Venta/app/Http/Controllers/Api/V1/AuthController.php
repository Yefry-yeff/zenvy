<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Auth\ApiAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private ApiAuthService $authService
    ) {}
    
    /**
     * Generar token JWT
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Datos de autenticación inválidos',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        try {
            $tokenData = $this->authService->generateToken(
                $request->api_key,
                $request->api_secret
            );

            return response()->json([
                'success' => true,
                'data' => $tokenData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_FAILED',
                    'message' => $e->getMessage()
                ]
            ], 401);
        }
    }
}
