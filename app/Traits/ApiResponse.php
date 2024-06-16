<?php

namespace App\Traits;


use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Create a standardized JSON response for successful operations.
     *
     * @param mixed $data Data to return, typically your resource data
     * @param string $message Success message
     * @param int $status HTTP status code, default is 200
     * @return JsonResponse
     */
    protected function successResponse(mixed $data, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Create a standardized JSON response for failed operations.
     *
     * @param string $message Error message
     * @param array $errors Details about the error(s), if applicable
     * @param int $status HTTP status code, default is 400
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Operation failed!', array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
