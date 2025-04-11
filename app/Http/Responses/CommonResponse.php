<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;

class CommonResponse implements Responsable
{
    protected int $httpCode;
    protected string | array | object $data;
    protected string $errorMessage;
    protected array $errors;

    public function __construct(int $httpCode, string | array | object $data = [], string $errorMessage = '', array $errors = [])
    {
        if (! (($httpCode >= 200 && $httpCode <= 300) || ($httpCode >= 400 && $httpCode <= 600))) {
          throw new \RuntimeException($httpCode . ' is not valid');
        }

        $this->httpCode = $httpCode;
        $this->data = $data;
        $this->errorMessage = $errorMessage;
        $this->errors = $errors;
    }

    public function toResponse($request)
    {
        $message = match(true) {
            $this->httpCode >= 500 => 'Internal Server Error',
            $this->httpCode >= 400 => 'Error: ' . $this->errorMessage,
            $this->httpCode >= 200 => 'Success!'
        };

        return response()->streamJson(
            data: [
                'meta' => [
                    'status' => $this->httpCode,
                    'message' => $message,
                    'errors' => $this->errors,
                ],
                'data' => $this->data
            ],
            status: $this->httpCode
        );
    }

    public static function ok(array | string | object $data)
    {
        return new static(200, $data);
    }
    
    public static function created(array $data)
    {
        return new static(201, $data);
    }
    
    public static function notFound(string $errorMessage = "Item not found")
    {
        return new static(404, errorMessage: $errorMessage);
    }
    
    public static function forbidden(string $errorMessage = "Action couldn't be performed")
    {
        return new static(403, errorMessage: $errorMessage);
    }
    
    public static function unprocessableEntity(array $errors = [], string $errorMessage = "Incorrect payload format")
    {
        return new static(422, errorMessage: $errorMessage, errors: $errors);
    }

    public static function badRequest(array $errors = [], string $errorMessage = "Incorrect payload format")
    {
        return new static(400, errorMessage: $errorMessage, errors: $errors);
    }
}