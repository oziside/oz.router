import { Type } from 'main.core';
import './style.css';

const HTTP_METHODS = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'];

const methodPriority = new Map(HTTP_METHODS.map((method, index) => [method, index]));

const App = {
	init(containerId)
	{
		const container = document.getElementById(containerId);
		if (!container)
		{
			return;
		}

		this.render(container);
	},

	initAll()
	{
		document.querySelectorAll('[data-role="oz-swagger-ui"]').forEach((container) => {
			this.render(container);
		});
	},

	render(container)
	{
		if (!Type.isElementNode(container) || container.dataset.initialized === 'Y')
		{
			return;
		}

		const config = this.parseConfig(container);
		if (!Type.isPlainObject(config) || !Type.isPlainObject(config.spec))
		{
			container.innerHTML = '';
			container.appendChild(this.renderError('Failed to render Swagger UI: invalid configuration'));
			container.dataset.initialized = 'Y';

			return;
		}

		const messages = Type.isPlainObject(config.messages) ? config.messages : {};
		const spec = config.spec;
		const meta = Type.isPlainObject(config.meta) ? config.meta : {};
		const operations = this.getOperations(spec, messages);
		const groups = this.groupByTag(operations, messages);

		container.innerHTML = '';
		container.appendChild(this.renderApp(groups, meta, spec, messages));
		container.dataset.initialized = 'Y';
	},

	parseConfig(container)
	{
		const configNode = container.querySelector('.oz-swagger-ui__config');
		if (!configNode)
		{
			return null;
		}

		let config = null;

		try
		{
			config = JSON.parse(configNode.textContent || '{}');
		}
		catch (e)
		{
			console.error('oz.swagger.ui: unable to parse config', e);
		}

		configNode.remove();

		return config;
	},

	groupByTag(operations, messages)
	{
		const defaultTag = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DEFAULT_TAG', 'Untagged');
		const groups = new Map();

		operations.forEach((operation) => {
			const tag = Type.isArray(operation.tags) && Type.isStringFilled(operation.tags[0])
				? operation.tags[0]
				: defaultTag;

			if (!groups.has(tag))
			{
				groups.set(tag, []);
			}

			groups.get(tag).push(operation);
		});

		return groups;
	},

	getOperations(spec, messages)
	{
		const operations = [];
		const paths = Type.isPlainObject(spec.paths) ? spec.paths : {};
		const defaultTag = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DEFAULT_TAG', 'Untagged');

		Object.keys(paths)
			.sort((a, b) => a.localeCompare(b))
			.forEach((path) => {
				const pathData = paths[path];
				if (!Type.isPlainObject(pathData))
				{
					return;
				}

				const commonParameters = Type.isArray(pathData.parameters) ? pathData.parameters : [];

				Object.keys(pathData)
					.filter((method) => HTTP_METHODS.includes(method.toLowerCase()))
					.sort((a, b) => {
						const first = methodPriority.get(a.toLowerCase()) || 99;
						const second = methodPriority.get(b.toLowerCase()) || 99;

						return first - second;
					})
					.forEach((method) => {
						const operationData = pathData[method];
						if (!Type.isPlainObject(operationData))
						{
							return;
						}

						const operationParameters = Type.isArray(operationData.parameters) ? operationData.parameters : [];
						const tags = Type.isArray(operationData.tags)
							? operationData.tags.filter((tag) => Type.isStringFilled(tag))
							: [];

						operations.push({
							method: method.toUpperCase(),
							path,
							summary: Type.isStringFilled(operationData.summary)
								? operationData.summary
								: (Type.isStringFilled(operationData.description) ? operationData.description : path),
							description: Type.isStringFilled(operationData.description) ? operationData.description : '',
							operationId: Type.isStringFilled(operationData.operationId) ? operationData.operationId : '',
							tags: tags.length > 0 ? tags : [defaultTag],
							parameters: this.mergeParameters(commonParameters, operationParameters),
							requestBody: Type.isPlainObject(operationData.requestBody) ? operationData.requestBody : null,
							responses: Type.isPlainObject(operationData.responses) ? operationData.responses : {},
						});
					});
			});

		return operations;
	},

	mergeParameters(commonParameters, operationParameters)
	{
		const registry = new Map();

		[...commonParameters, ...operationParameters]
			.filter((parameter) => Type.isPlainObject(parameter))
			.forEach((parameter) => {
				const name = Type.isStringFilled(parameter.name) ? parameter.name : 'param';
				const location = Type.isStringFilled(parameter.in) ? parameter.in : 'query';
				registry.set(`${name}:${location}`, parameter);
			});

		return [...registry.values()];
	},

	renderApp(groups, meta, spec, messages)
	{
		const wrapper = this.createElement('div', 'oz-swagger-ui__app');
		const topbar = this.renderTopbar(meta, spec, messages);
		const content = this.createElement('div', 'oz-swagger-ui__content');
		const controls = this.createElement('div', 'oz-swagger-ui__controls');
		const search = this.createElement('input', 'oz-swagger-ui__search');
		const noOperationsText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NO_OPERATIONS', 'No operations found');
		const noResults = this.createElement('div', 'oz-swagger-ui__no-results', noOperationsText);
		const sectionsContainer = this.createElement('div', 'oz-swagger-ui__sections');
		const operationItems = [];

		search.type = 'search';
		search.placeholder = this.getMessage(
			messages,
			'OZ_SWAGGER_UI_JS_SEARCH_PLACEHOLDER',
			'Filter by path, method, or description...'
		);

		controls.appendChild(search);

		groups.forEach((groupOperations, tag) => {
			const section = this.createElement('section', 'oz-swagger-ui__tag');
			const header = this.createElement('button', 'oz-swagger-ui__tag-title');
			const titleText = this.createElement('span', 'oz-swagger-ui__tag-title-text', tag);
			const counter = this.createElement('span', 'oz-swagger-ui__tag-counter', `${groupOperations.length}`);
			const body = this.createElement('div', 'oz-swagger-ui__tag-body');

			header.type = 'button';
			header.appendChild(titleText);
			header.appendChild(counter);
			header.addEventListener('click', () => {
				section.classList.toggle('--collapsed');
			});

			groupOperations.forEach((operation) => {
				const operationNode = this.renderOperation(operation, messages);
				body.appendChild(operationNode.node);
				operationItems.push({
					node: operationNode.node,
					section,
					searchIndex: operationNode.searchIndex,
				});
			});

			section.appendChild(header);
			section.appendChild(body);
			sectionsContainer.appendChild(section);
		});

		if (operationItems.length === 0)
		{
			noResults.classList.add('--visible');
		}

		search.addEventListener('input', () => {
			const term = search.value.trim().toLowerCase();
			let visibleCount = 0;

			operationItems.forEach((item) => {
				const visible = term === '' || item.searchIndex.includes(term);
				item.node.style.display = visible ? '' : 'none';
				if (visible)
				{
					visibleCount += 1;
				}
			});

			sectionsContainer.querySelectorAll('.oz-swagger-ui__tag').forEach((section) => {
				const sectionHasVisibleOperations = [...section.querySelectorAll('.oz-swagger-ui__operation')]
					.some((operationNode) => operationNode.style.display !== 'none');

				section.style.display = sectionHasVisibleOperations ? '' : 'none';
			});

			if (visibleCount > 0)
			{
				noResults.classList.remove('--visible');
			}
			else
			{
				noResults.classList.add('--visible');
			}
		});

		content.appendChild(controls);
		content.appendChild(noResults);
		content.appendChild(sectionsContainer);

		wrapper.appendChild(topbar);
		wrapper.appendChild(content);

		return wrapper;
	},

	renderTopbar(meta, spec, messages)
	{
		const topbar = this.createElement('div', 'oz-swagger-ui__topbar');
		const brand = this.createElement('div', 'oz-swagger-ui__brand');
		const brandMark = this.createElement('span', 'oz-swagger-ui__brand-mark');
		const brandText = this.createElement(
			'span',
			'oz-swagger-ui__brand-text',
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_UI_TITLE', 'Swagger UI')
		);
		const subtitle = this.createElement(
			'div',
			'oz-swagger-ui__subtitle',
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_UI_SUBTITLE', 'OpenAPI Interactive Documentation')
		);

		const infoBlock = this.createElement('div', 'oz-swagger-ui__info');
		const titleLine = this.createElement('div', 'oz-swagger-ui__title-line');
		const title = this.createElement('h1', 'oz-swagger-ui__title', String(meta.title || 'REST API'));
		const version = this.createElement('span', 'oz-swagger-ui__version', String(meta.version || ''));
		const openapiVersion = this.createElement(
			'span',
			'oz-swagger-ui__openapi-version',
			`${this.getMessage(messages, 'OZ_SWAGGER_UI_JS_OPENAPI', 'OpenAPI')} ${String(meta.openapi || spec.openapi || '')}`
		);
		const description = this.createElement('p', 'oz-swagger-ui__description', String(meta.description || ''));

		brand.appendChild(brandMark);
		brand.appendChild(brandText);
		topbar.appendChild(brand);
		topbar.appendChild(subtitle);

		titleLine.appendChild(title);
		if (version.textContent !== '')
		{
			titleLine.appendChild(version);
		}

		if (openapiVersion.textContent.trim() !== this.getMessage(messages, 'OZ_SWAGGER_UI_JS_OPENAPI', 'OpenAPI'))
		{
			titleLine.appendChild(openapiVersion);
		}

		infoBlock.appendChild(titleLine);
		if (description.textContent !== '')
		{
			infoBlock.appendChild(description);
		}

		const wrapper = this.createElement('div', 'oz-swagger-ui__header');
		wrapper.appendChild(topbar);
		wrapper.appendChild(infoBlock);

		return wrapper;
	},

	renderOperation(operation, messages)
	{
		const container = this.createElement('article', 'oz-swagger-ui__operation');
		const header = this.createElement('button', 'oz-swagger-ui__operation-header');
		const methodBadge = this.createElement('span', `oz-swagger-ui__method --${operation.method.toLowerCase()}`, operation.method);
		const path = this.createElement('span', 'oz-swagger-ui__path', operation.path);
		const summary = this.createElement('span', 'oz-swagger-ui__summary', operation.summary);
		const toggleIcon = this.createElement('span', 'oz-swagger-ui__toggle', '▾');
		const body = this.createElement('div', 'oz-swagger-ui__operation-body');

		header.type = 'button';
		header.appendChild(methodBadge);
		header.appendChild(path);
		header.appendChild(summary);
		header.appendChild(toggleIcon);

		const description = this.createElement(
			'p',
			'oz-swagger-ui__operation-description',
			operation.description || this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data')
		);
		body.appendChild(description);

		if (operation.operationId)
		{
			body.appendChild(this.createMetaRow(
				this.getMessage(messages, 'OZ_SWAGGER_UI_JS_OPERATION_ID', 'Operation ID'),
				operation.operationId
			));
		}

		if (Type.isArray(operation.tags) && operation.tags.length > 0)
		{
			const tagsRow = this.createElement('div', 'oz-swagger-ui__meta-row');
			const tagsLabel = this.createElement(
				'div',
				'oz-swagger-ui__meta-label',
				this.getMessage(messages, 'OZ_SWAGGER_UI_JS_TAGS', 'Tags')
			);
			const tagsBody = this.createElement('div', 'oz-swagger-ui__meta-value');

			operation.tags.forEach((tag) => {
				tagsBody.appendChild(this.createElement('span', 'oz-swagger-ui__chip', tag));
			});

			tagsRow.appendChild(tagsLabel);
			tagsRow.appendChild(tagsBody);
			body.appendChild(tagsRow);
		}

		body.appendChild(this.renderParameters(operation.parameters, messages));
		body.appendChild(this.renderRequestBody(operation.requestBody, messages));
		body.appendChild(this.renderResponses(operation.responses, messages));

		header.addEventListener('click', () => {
			container.classList.toggle('--expanded');
		});

		container.appendChild(header);
		container.appendChild(body);

		const searchIndex = [
			operation.method,
			operation.path,
			operation.summary,
			operation.description,
			operation.operationId,
			operation.tags.join(' '),
		]
			.join(' ')
			.toLowerCase();

		return {
			node: container,
			searchIndex,
		};
	},

	renderParameters(parameters, messages)
	{
		const title = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_PARAMETERS', 'Parameters');
		const emptyText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data');

		if (!Type.isArray(parameters) || parameters.length === 0)
		{
			return this.createSimpleSection(title, emptyText);
		}

		const rows = parameters
			.filter((parameter) => Type.isPlainObject(parameter))
			.map((parameter) => {
				const name = Type.isStringFilled(parameter.name) ? parameter.name : '-';
				const location = Type.isStringFilled(parameter.in) ? parameter.in : '-';
				const required = parameter.required === true
					? this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required')
					: '-';
				const schemaText = this.stringifySchema(parameter.schema);
				const description = Type.isStringFilled(parameter.description) ? parameter.description : '-';

				return [name, location, required, schemaText, description];
			});

		return this.createTableSection(title, rows, [
			'Name',
			'In',
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required'),
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SCHEMA', 'Schema'),
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DESCRIPTION', 'Description'),
		]);
	},

	renderRequestBody(requestBody, messages)
	{
		const title = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUEST_BODY', 'Request body');
		const emptyText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data');
		if (!Type.isPlainObject(requestBody) || !Type.isPlainObject(requestBody.content))
		{
			return this.createSimpleSection(title, emptyText);
		}

		const rows = Object.keys(requestBody.content)
			.map((contentType) => {
				const contentItem = requestBody.content[contentType];
				const schema = Type.isPlainObject(contentItem) ? contentItem.schema : null;
				const schemaText = this.stringifySchema(schema);
				const required = requestBody.required === true
					? this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required')
					: '-';

				return [contentType, schemaText, required];
			});

		if (rows.length === 0)
		{
			return this.createSimpleSection(title, emptyText);
		}

		return this.createTableSection(title, rows, [
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_CONTENT_TYPE', 'Content-Type'),
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SCHEMA', 'Schema'),
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required'),
		]);
	},

	renderResponses(responses, messages)
	{
		const title = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_RESPONSES', 'Responses');
		const emptyText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data');
		if (!Type.isPlainObject(responses) || Object.keys(responses).length === 0)
		{
			return this.createSimpleSection(title, emptyText);
		}

		const rows = Object.keys(responses)
			.sort((a, b) => a.localeCompare(b))
			.map((statusCode) => {
				const response = responses[statusCode];
				if (!Type.isPlainObject(response))
				{
					return [statusCode, '-', '-'];
				}

				const contentInfo = this.getFirstContentSchema(response.content);
				const responseDescription = Type.isStringFilled(response.description)
					? response.description
					: '-';

				return [statusCode, responseDescription, contentInfo.schema];
			});

		return this.createTableSection(title, rows, [
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_STATUS', 'Status'),
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DESCRIPTION', 'Description'),
			this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SCHEMA', 'Schema'),
		]);
	},

	getFirstContentSchema(content)
	{
		if (!Type.isPlainObject(content))
		{
			return { schema: '-' };
		}

		const firstContentType = Object.keys(content)[0];
		if (!firstContentType)
		{
			return { schema: '-' };
		}

		const contentItem = content[firstContentType];
		if (!Type.isPlainObject(contentItem) || !Type.isPlainObject(contentItem.schema))
		{
			return { schema: '-' };
		}

		return {
			schema: this.stringifySchema(contentItem.schema),
		};
	},

	stringifySchema(schema)
	{
		if (!Type.isPlainObject(schema))
		{
			return '-';
		}

		if (Type.isStringFilled(schema.$ref))
		{
			return schema.$ref.split('/').pop() || schema.$ref;
		}

		if (Type.isArray(schema.oneOf) && schema.oneOf.length > 0)
		{
			return schema.oneOf.map((item) => this.stringifySchema(item)).join(' | ');
		}

		if (Type.isArray(schema.anyOf) && schema.anyOf.length > 0)
		{
			return schema.anyOf.map((item) => this.stringifySchema(item)).join(' | ');
		}

		if (Type.isArray(schema.allOf) && schema.allOf.length > 0)
		{
			return schema.allOf.map((item) => this.stringifySchema(item)).join(' & ');
		}

		const type = Type.isStringFilled(schema.type) ? schema.type : 'object';
		if (type === 'array')
		{
			return `array<${this.stringifySchema(schema.items)}>`;
		}

		if (Type.isArray(schema.enum) && schema.enum.length > 0)
		{
			const values = schema.enum
				.slice(0, 4)
				.map((enumValue) => String(enumValue))
				.join(', ');
			const suffix = schema.enum.length > 4 ? ', ...' : '';

			return `${type} (${values}${suffix})`;
		}

		if (Type.isStringFilled(schema.format))
		{
			return `${type} (${schema.format})`;
		}

		return type;
	},

	createMetaRow(labelText, valueText)
	{
		const row = this.createElement('div', 'oz-swagger-ui__meta-row');
		const label = this.createElement('div', 'oz-swagger-ui__meta-label', labelText);
		const value = this.createElement('div', 'oz-swagger-ui__meta-value', valueText);

		row.appendChild(label);
		row.appendChild(value);

		return row;
	},

	createSimpleSection(title, text)
	{
		const section = this.createElement('section', 'oz-swagger-ui__section');
		const heading = this.createElement('h4', 'oz-swagger-ui__section-title', title);
		const content = this.createElement('div', 'oz-swagger-ui__section-empty', text);

		section.appendChild(heading);
		section.appendChild(content);

		return section;
	},

	createTableSection(title, rows, columns)
	{
		const section = this.createElement('section', 'oz-swagger-ui__section');
		const heading = this.createElement('h4', 'oz-swagger-ui__section-title', title);
		const wrapper = this.createElement('div', 'oz-swagger-ui__table-wrap');
		const table = this.createElement('table', 'oz-swagger-ui__table');
		const tableHead = this.createElement('thead', 'oz-swagger-ui__table-head');
		const headRow = this.createElement('tr');
		const tableBody = this.createElement('tbody', 'oz-swagger-ui__table-body');

		columns.forEach((column) => {
			const cell = this.createElement('th', '', column);
			headRow.appendChild(cell);
		});

		tableHead.appendChild(headRow);

		rows.forEach((row) => {
			const rowNode = this.createElement('tr');
			row.forEach((value) => {
				rowNode.appendChild(this.createElement('td', '', String(value)));
			});
			tableBody.appendChild(rowNode);
		});

		table.appendChild(tableHead);
		table.appendChild(tableBody);
		wrapper.appendChild(table);

		section.appendChild(heading);
		section.appendChild(wrapper);

		return section;
	},

	renderError(text)
	{
		return this.createElement('div', 'oz-swagger-ui__error', text);
	},

	createElement(tagName, className = '', text = null)
	{
		const node = document.createElement(tagName);
		if (className)
		{
			node.className = className;
		}

		if (text !== null)
		{
			node.textContent = text;
		}

		return node;
	},

	getMessage(messages, code, fallback)
	{
		if (Type.isPlainObject(messages) && Type.isStringFilled(messages[code]))
		{
			return messages[code];
		}

		return fallback;
	},
};

if (document.readyState === 'loading')
{
	document.addEventListener('DOMContentLoaded', () => {
		App.initAll();
	});
}
else
{
	App.initAll();
}

export {
	App,
};
