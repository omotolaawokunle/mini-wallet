<?php

namespace App\Http\Traits;

use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function success(
        string $message = 'Operation successful',
        mixed $data = null,
        int $statusCode = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        return ResponseService::success($message, $data, $statusCode, $meta);
    }

    /**
     * Error response
     */
    protected function error(
        string $message = 'An error occurred',
        mixed $errors = null,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $meta = []
    ): JsonResponse {
        return ResponseService::error($message, $errors, $statusCode, $meta);
    }

    /**
     * Validation error response
     */
    protected function validationError(
        ValidationException $exception,
        string $message = 'Validation failed'
    ): JsonResponse {
        return ResponseService::validationError($exception, $message);
    }

    /**
     * Not found response
     */
    protected function notFound(
        string $message = 'Resource not found',
        mixed $errors = null
    ): JsonResponse {
        return ResponseService::notFound($message, $errors);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized(
        string $message = 'Unauthorized access',
        mixed $errors = null
    ): JsonResponse {
        return ResponseService::unauthorized($message, $errors);
    }

    /**
     * Forbidden response
     */
    protected function forbidden(
        string $message = 'Access forbidden',
        mixed $errors = null
    ): JsonResponse {
        return ResponseService::forbidden($message, $errors);
    }

    /**
     * Created response
     */
    protected function created(
        string $message = 'Resource created successfully',
        mixed $data = null,
        array $meta = []
    ): JsonResponse {
        return ResponseService::created($message, $data, $meta);
    }

    /**
     * No content response
     */
    protected function noContent(string $message = 'Operation completed'): JsonResponse
    {
        return ResponseService::noContent($message);
    }

    /**
     * Paginated response
     */
    protected function paginated(
        $paginator,
        string $message = 'Data retrieved successfully',
        array $additionalMeta = [],
        ?string $collectionClass = null
    ): JsonResponse {
        return ResponseService::paginated($paginator, $message, $additionalMeta, $collectionClass);
    }

    /**
     * Server error response
     */
    protected function serverError(
        string $message = 'Internal server error',
        mixed $errors = null
    ): JsonResponse {
        return ResponseService::serverError($message, $errors);
    }
}
