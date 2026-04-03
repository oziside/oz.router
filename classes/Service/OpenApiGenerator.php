<?
declare(strict_types=1);
namespace Oz\Router\Module\Service;

use Bitrix\Main\{
    Localization\Loc
};

Loc::loadMessages(__FILE__);

final class OpenApiGenerator
{
    private PathResolver $pathResolver;
 
    public function __construct()
    {
        $this->pathResolver = new PathResolver;
    }


    /**
     * Генерирует схему OpenAPI по аннотациям
     * в коде и сохраняет ее в указанный файл
     * 
     * @param string[] $sources
     * @param string $output
     * 
     * @return array
    */
    public function generate(
        array $sources,
        string $output
    ): array
    {
        $resolvedSourcePaths = [];
        $invalidSourcePaths = [];

        foreach ($sources as $path)
        {
            $resolvedPath = $this->pathResolver->resolve($path);

            if ($resolvedPath === null)
            {
                $invalidSourcePaths[] = $path;
                continue;
            }

            $resolvedSourcePaths[] = $resolvedPath;
        }

        $resolvedSourcePaths = array_values(array_unique($resolvedSourcePaths));

        if ($invalidSourcePaths)
        {
            return $this->toError([
                Loc::getMessage("OZ_ROUTER_OPENAPI_ERROR_SOURCE_PATHS_NOT_FOUND", [
                    "#PATHS#" => implode(", ", $invalidSourcePaths)
                ])
            ]);
        }

        if (!$resolvedSourcePaths)
        {
            return $this->toError([
                Loc::getMessage("OZ_ROUTER_OPENAPI_ERROR_NO_VALID_SOURCE_PATHS")
            ]);
        }
 
        if (!class_exists(\OpenApi\Generator::class))
        {
            return $this->toError([
                Loc::getMessage("OZ_ROUTER_OPENAPI_ERROR_GENERATOR_CLASS_NOT_FOUND")
            ]);
        }


        // Генерация схемы OpenAPI
        try
        {
            $openApi = new \OpenApi\Generator()
                ->generate($resolvedSourcePaths);

            if($openApi === null)
            {
                return $this->toError([
                    "OZ_ROUTER_OPENAPI_ERROR_SCHEMA_GENERATION_FAILED"
                ]);
            }
                

            $outputPathAbsolute = $this->pathResolver->resolve($output);

            if ($outputPathAbsolute === "")
            {
                return $this->toError([
                    "OZ_ROUTER_OPENAPI_ERROR_EMPTY_TARGET"
                ]);
            }

            $outputDir = dirname($outputPathAbsolute);

            if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir))
            {
                return $this->toError([
                    "OZ_ROUTER_OPENAPI_ERROR_CREATE_DIR #DIR#" . $outputDir
                ]);
            }

            $isYaml = (bool)preg_match("/\\.(yaml|yml)$/i", $output);
            $serializedSchema = $isYaml
                ? $openApi->toYaml()
                : $openApi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $writeResult = @file_put_contents($outputPathAbsolute, $serializedSchema);

            if($writeResult === false)
            {
                return $this->toError([
                    "OZ_ROUTER_OPENAPI_ERROR_WRITE_SCHEMA #PATH#" .  $outputPathAbsolute
                ]);
            }

            return $this->toSuccess([
                "OZ_ROUTER_OPENAPI_NOTE_GENERATED #PATH#" . $outputPathAbsolute . " #COUNT#" . count($resolvedSourcePaths),
            ]);
        }
        catch (\Throwable $th)
        {
            return $this->toError([
                $th->getMessage()
            ]);
        }
    }


    /**
     * Возвращает массив ошибок формата ответа Response\AjaxJson
     * с ключом "status" => "error" и массивом ошибок в ключе "data"
     * 
     * @param array $data
     * 
     * @return array
    */
    private function toSuccess(array $data): array
    {
        return [
            "status" => "error",
            "data"   => $data
        ];
    }


    /**
     * Возвращает массив ошибок формата ответа Response\AjaxJson
     * с ключом "status" => "error" и массивом ошибок в ключе "errors"
     * 
     * @param array $errors
     * 
     * @return array
    */
    private function toError(array $errors): array
    {
        return [
            "status" => "error",
            "errors" => $errors
        ];
    }
}
