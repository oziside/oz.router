<?php
declare(strict_types=1);
namespace Oz\Router\Http;

use Bitrix\Main\{
    Web\Json,
    HttpResponse,
    Engine\Response,
};


final class ResponseNormalizer
{
    private JsonObjectSerializer $jsonObjectSerializer;

    public function __construct(?JsonObjectSerializer $jsonObjectSerializer = null)
    {
        $this->jsonObjectSerializer = $jsonObjectSerializer ?? new JsonObjectSerializer();
    }

    public function normalize(mixed $result): HttpResponse
    {
        /**
         * Перехватываем объекты HttpResponse, 
         * и его наследники:
         * - Response\(AjaxJson|Json);
         * - Response\(File|BFile|ResizedImage|Zip\Archive);
         * - Response\Redirect
         * - Response\(Component|HtmlContent)
         * - Response\(OpenDesktopApp|OpenMobileApp)
         * - Reponse\Render\(Component|Extension|View)
        */
        if ($result instanceof HttpResponse)
        {
            return $result;
        }


        /**
         * Если пришел массив или объект, 
         * обрабатывам его как json
        */
        if (is_array($result) || is_object($result))
        {
            return new Response\Json(
                $this->jsonObjectSerializer->normalize($result),
                Json::DEFAULT_OPTIONS
            );
        }


        /**
         * Иначе, возвращаем Content-Type: text/html
         * в виде строки
        */
        return new HttpResponse()
            ->setContent($result);
    }
}
