<?php
declare(strict_types=1);
namespace Oz\Router\Module;

use Bitrix\Main\{
    HttpRequest,
    Error,
    ErrorCollection,
    Engine\Response
};

abstract class Route
{
    protected array $errors = [];

    /**
     * Returns the name of the route template.
     * 
     * @return string
    */
    abstract public function getViewName(): string;


    /**
     * Handles the form request 
     * (saving/updating) of the route
     * 
     * @param HttpRequest $request
     * 
     * @return ?array{
     *   status: string,
     *   message: string,
     *   details: string[]
     *   ?redirect: string
     * }
    */
    abstract public function formRequestHandler(
        HttpRequest $request
    ): ?array;

    
    /**
     * Handles AJAX request
     * 
     * @param HttpRequest $request
     * 
     * @return ?Response\AjaxJson
    */
    abstract public function ajaxRequestHandler(
        HttpRequest $request
    ): ?Response\AjaxJson;

    
    /**
     * Prepares data for the route template.
     * 
     * @return array
    */
    abstract public function prepareViewData(): array;


    /**
     * Returns the response after processing
     * a POST form request
     * 
     * @param string $status('success'|'error')
     * @param array  $details
     * 
     * @return array
    */
    public function formResponse(
        string $status  = 'success',
        array  $details = []
    ): array
    {
        $message = ($status === 'error')
            ? 'Операция завершена с ошибками'
            : 'Операция успешно завершена';

        return [
            'status'  => $status === 'error' ? 'ERROR' : 'OK',
            'message' => $message,
            'details' => $details
        ];
    }


    /**
     * Returns a JSON response for an AJAX request
     * 
     * @param array|null $data
     * @param array|null $errors
     * 
     * @return void
    */
    public function ajaxResponse(
        ?array $data, 
        ?array $errors
    ): Response\AjaxJson
    {
        $status          = 'success';
        $errorCollection = null;

        if(!empty($errors))
        {
            $errors = array_map(fn($text) => new Error($text), $errors);
            // set errors
            $status          = 'error';
            $errorCollection = new ErrorCollection($errors);
        }

        return new Response\AjaxJson(
            $data, 
            $status, 
            $errorCollection
        );
    }


    /**
     * Includes the route template
     * 
     * @return void
    */
    public function renderView(): void
    {
        global $APPLICATION;

        $viewsDir     = $_SERVER["DOCUMENT_ROOT"]."/local/modules/oz.router/admin/view/";
        $viewFilePath = $viewsDir.$this->getViewName().".php";

        if(!is_file($viewFilePath))
        {
            require $viewsDir."404.php";
        }
        else
        {
            $arFields = $this->prepareViewData();
            require $viewFilePath; 
        }
    }
}
