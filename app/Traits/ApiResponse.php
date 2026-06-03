<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success response
     */
    public function success($data = null, $message = '', $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Error response
     */
    public function error($message = '', $errorCode = 'ERROR', $statusCode = 400, $data = null)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Paginated response
     */
    public function paginated($items, $total, $page, $limit, $message = '')
    {
        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit),
            ],
            'message' => $message,
        ], 200);
    }

    /**
     * Validation error response
     */
    public function validationError($errors, $message = 'Validation failed')
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $message,
                'details' => $errors,
            ],
        ], 422);
    }

    /**
     * Not found response
     */
    public function notFound($message = 'Resource not found')
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => $message,
            ],
        ], 404);
    }

    /**
     * Unauthorized response
     */
    public function unauthorized($message = 'Unauthorized')
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
            ],
        ], 401);
    }

    /**
     * Forbidden response
     */
    public function forbidden($message = 'Forbidden')
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => $message,
            ],
        ], 403);
    }

    /**
     * Server error response
     */
    public function serverError($message = 'Internal server error')
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => $message,
            ],
        ], 500);
    }

    /**
     * Created response
     */
    public function created($data = null, $message = 'Resource created successfully')
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], 201);
    }

    /**
     * No content response
     */
    public function noContent()
    {
        return response()->json(null, 204);
    }
}
