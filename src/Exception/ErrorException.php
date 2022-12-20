<?php

namespace App\Exception;

use App\Api\ApiError;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorException extends HttpException
{
    public function __construct(ApiError $apiError, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        $this->apiError = $apiError;
        $statusCode = $apiError->getStatusCode();
        $message = $apiError->getMessage();
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}