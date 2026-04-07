<?php
declare(strict_types=1);
namespace Oz\Router\Http;


enum HttpMethodEnum: string
{
    case GET = "GET";
    case POST = "POST";
    case PUT = "PUT";
    case DELETE = "DELETE";
    case PATCH = "PATCH";
    case ALL = "ALL";
    case OPTIONS = "OPTIONS";
    case HEAD = "HEAD";
}