<?php

namespace App\Exceptions;

use Dotenv\Exception\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            switch (true) {
                case $e instanceof ClientError:
                    return response()->json([
                        "code" => $e->getCode(),
                        "status" => "fail",
                        "message" => $e->getMessage(),
                    ], $e->getCode());
                    break;
                case $e instanceof ValidationException:
                    return response()->json([
                        "code" => 400,
                        "status" => "fail",
                        "message" => $e->validator->errors()->first(),
                    ], 400);
                    break;
                case $e instanceof NotFoundHttpException:
                    return response()->json([
                        "code" => 404,
                        "status" => "fail",
                        "message" => "URL Not Found",
                    ], 404);
                    break;
                case $e instanceof MethodNotAllowedHttpException:
                    return response()->json([
                        "code" => 405,
                        "status" => "fail",
                        "message" => $e->getMessage(),
                    ], 405);
                    break;
                case $e instanceof HttpResponseException:
                    return response()->json([
                        "code" => $e->getCode(),
                        "status" => "fail",
                        "message" => $e->getMessage(),
                    ], $e->getCode());
                    break;
                case $e instanceof AuthenticationException:
                    return response()->json([
                        "code" => 401,
                        "status" => "fail",
                        "message" => $e->getMessage(),
                    ], 401);
                    break;
                case $e instanceof TokenMismatchException:
                    return response()->json([
                        "code" => 419,
                        "status" => "fail",
                        "message" => $e->getMessage(),
                    ], 419);
                    break;
                default:
                    Log::error($e);

                    return response()->json([
                        "code" => 500,
                        "status" => "error",
                        "message" => "Something Went Wrong, Please Contact Administrator",
                    ], 500);
                    break;
            }
        });
    }
}
