<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseCodes;

class ResponseService
{
    /**
     * Success response with data
     */
    public static function success(
        string $message = 'Operation successful',
        mixed $data = null,
        int $statusCode = ResponseCodes::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    public static function error(
        string $message = 'An error occurred',
        mixed $errors = null,
        int $statusCode = ResponseCodes::HTTP_BAD_REQUEST,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(
        ValidationException $exception,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error(
            $message,
            $exception->errors(),
            ResponseCodes::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * Not found response
     */
    public static function notFound(
        string $message = 'Resource not found',
        mixed $errors = null
    ): JsonResponse {
        return self::error($message, $errors, ResponseCodes::HTTP_NOT_FOUND);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized access',
        mixed $errors = null
    ): JsonResponse {
        return self::error($message, $errors, ResponseCodes::HTTP_UNAUTHORIZED);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(
        string $message = 'Access forbidden',
        mixed $errors = null
    ): JsonResponse {
        return self::error($message, $errors, ResponseCodes::HTTP_FORBIDDEN);
    }

    /**
     * Internal server error response
     */
    public static function serverError(
        string $message = 'Internal server error',
        mixed $errors = null
    ): JsonResponse {
        return self::error($message, $errors, ResponseCodes::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Created response
     */
    public static function created(
        string $message = 'Resource created successfully',
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        return self::success($message, $data, ResponseCodes::HTTP_CREATED, $meta);
    }

    /**
     * No content response
     */
    public static function noContent(string $message = 'Operation completed'): JsonResponse
    {
        return self::success($message, null, ResponseCodes::HTTP_NO_CONTENT);
    }

    /**
     * Paginated response
     */
    public static function paginated(
        $paginator,
        string $message = 'Data retrieved successfully',
        array $additionalMeta = [],
        ?string $collectionClass = null
    ): JsonResponse {
        $meta = array_merge([
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ], $additionalMeta);

        if ($collectionClass && class_exists($collectionClass)) {
            if (method_exists($collectionClass, 'collection')) {
                $data = $collectionClass::collection($paginator->items());
            } else {
                $data = new $collectionClass($paginator->items());
            }
        } else {
            $data = $paginator->items();
        }

        return self::success(
            $message,
            $data,
            ResponseCodes::HTTP_OK,
            $meta
        );
    }

    /**
     * Custom response with specific status code
     */
    public static function custom(
        bool $success,
        string $message,
        mixed $data = null,
        int $statusCode = ResponseCodes::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }
}
