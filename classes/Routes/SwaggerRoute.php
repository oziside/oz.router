<?
declare(strict_types=1);
namespace Oz\Router\Module\Routes;

use Bitrix\Main\{
    HttpRequest,
    Engine\Response
};
use Oz\Router\{
    Module\Route,
    Module\Module
};


class SwaggerRoute extends Route
{  
    public function getViewName(): string
    {
        return 'swagger';
    }

    public function formRequestHandler(HttpRequest $request): ?array
    {   // do nothing
        return null;
    }

    public function ajaxRequestHandler(HttpRequest $request): ?Response\AjaxJson
    {   // do nothing
        return null;
    } 

    
    public function prepareViewData(): array
    {
        $cfg = Module::getConfig();

        return [
            "OPENAPI_SCHEMA_OUTPUT_FILE" => $cfg->getOpenApiSchemaOutput(),
        ];
    }
}