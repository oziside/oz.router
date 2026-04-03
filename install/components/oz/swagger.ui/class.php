<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\SystemException;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class OzSwaggerUiComponent extends CBitrixComponent
{
	//private const DEFAULT_SPEC_PATH = '/local/php_interface/openapi/scheme.json';

	public function onPrepareComponentParams($arParams): array
	{
		$specPath = trim((string)($arParams['SPEC_PATH'] ?? ''));
		
		// if ($specPath === '')
		// {
		// 	$specPath = self::DEFAULT_SPEC_PATH;
		// }

		if ($specPath[0] !== '/')
		{
			$specPath = '/' . $specPath;
		}

		$arParams['SPEC_PATH'] = $specPath;
		$arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);

		return $arParams;
	}

	public function executeComponent(): void
	{
		try
		{
			$this->prepareResult();
			$this->includeComponentTemplate();
		}
		catch (\Throwable $exception)
		{
			ShowError($exception->getMessage());
		}
	}

	private function prepareResult(): void
	{
		$realPath = $this->resolveSpecPath($this->arParams['SPEC_PATH']);
		if ($realPath === '')
		{
			throw new SystemException(
				Loc::getMessage('OZ_SWAGGER_UI_SPEC_NOT_FOUND', ['#PATH#' => $this->arParams['SPEC_PATH']])
			);
		}

		$specContent = file_get_contents($realPath);
		if ($specContent === false)
		{
			throw new SystemException(
				Loc::getMessage('OZ_SWAGGER_UI_SPEC_NOT_READABLE', ['#PATH#' => $this->arParams['SPEC_PATH']])
			);
		}

		try
		{
			$spec = Json::decode($specContent);
		}
		catch (\Throwable $exception)
		{
			throw new SystemException(Loc::getMessage('OZ_SWAGGER_UI_SPEC_INVALID_JSON'));
		}

		if (!is_array($spec))
		{
			throw new SystemException(Loc::getMessage('OZ_SWAGGER_UI_SPEC_EMPTY'));
		}

		$this->arResult = [
			'SPEC' => $spec,
			'SPEC_PATH' => $this->arParams['SPEC_PATH'],
			'TITLE' => (string)($spec['info']['title'] ?? Loc::getMessage('OZ_SWAGGER_UI_DEFAULT_TITLE')),
			'VERSION' => (string)($spec['info']['version'] ?? ''),
			'DESCRIPTION' => (string)($spec['info']['description'] ?? ''),
			'OPENAPI' => (string)($spec['openapi'] ?? ''),
		];
	}

	private function resolveSpecPath(string $path): string
	{
		$documentRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
		$documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
		if ($documentRoot === '')
		{
			return '';
		}

		$normalizedPath = trim(str_replace('\\', '/', $path));
		if ($normalizedPath === '')
		{
			return '';
		}

		$absolutePath = $documentRoot . '/' . ltrim($normalizedPath, '/');
		$realPath = realpath($absolutePath);
		$realDocumentRoot = realpath($documentRoot);
		if ($realPath === false || $realDocumentRoot === false)
		{
			return '';
		}

		$realPath = str_replace('\\', '/', $realPath);
		$realDocumentRoot = rtrim(str_replace('\\', '/', $realDocumentRoot), '/');
		if (!str_starts_with($realPath . '/', $realDocumentRoot . '/'))
		{
			throw new SystemException(Loc::getMessage('OZ_SWAGGER_UI_SPEC_ACCESS_DENIED'));
		}

		return is_file($realPath) && is_readable($realPath) ? $realPath : '';
	}
}
