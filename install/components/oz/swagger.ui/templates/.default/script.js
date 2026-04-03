/* eslint-disable */
this.BX = this.BX || {};
this.BX.Oz = this.BX.Oz || {};
(function (exports,main_core) {
	'use strict';

	var HTTP_METHODS = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head', 'trace'];
	var methodPriority = new Map(HTTP_METHODS.map(function (method, index) {
	  return [method, index];
	}));
	var App = {
	  init: function init(containerId) {
	    var container = document.getElementById(containerId);
	    if (!container) {
	      return;
	    }
	    this.render(container);
	  },
	  initAll: function initAll() {
	    var _this = this;
	    document.querySelectorAll('[data-role="oz-swagger-ui"]').forEach(function (container) {
	      _this.render(container);
	    });
	  },
	  render: function render(container) {
	    if (!main_core.Type.isElementNode(container) || container.dataset.initialized === 'Y') {
	      return;
	    }
	    var config = this.parseConfig(container);
	    if (!main_core.Type.isPlainObject(config) || !main_core.Type.isPlainObject(config.spec)) {
	      container.innerHTML = '';
	      container.appendChild(this.renderError('Failed to render Swagger UI: invalid configuration'));
	      container.dataset.initialized = 'Y';
	      return;
	    }
	    var messages = main_core.Type.isPlainObject(config.messages) ? config.messages : {};
	    var spec = config.spec;
	    var meta = main_core.Type.isPlainObject(config.meta) ? config.meta : {};
	    var operations = this.getOperations(spec, messages);
	    var groups = this.groupByTag(operations, messages);
	    container.innerHTML = '';
	    container.appendChild(this.renderApp(groups, meta, spec, messages));
	    container.dataset.initialized = 'Y';
	  },
	  parseConfig: function parseConfig(container) {
	    var configNode = container.querySelector('.oz-swagger-ui__config');
	    if (!configNode) {
	      return null;
	    }
	    var config = null;
	    try {
	      config = JSON.parse(configNode.textContent || '{}');
	    } catch (e) {
	      console.error('oz.swagger.ui: unable to parse config', e);
	    }
	    configNode.remove();
	    return config;
	  },
	  groupByTag: function groupByTag(operations, messages) {
	    var defaultTag = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DEFAULT_TAG', 'Untagged');
	    var groups = new Map();
	    operations.forEach(function (operation) {
	      var tag = main_core.Type.isArray(operation.tags) && main_core.Type.isStringFilled(operation.tags[0]) ? operation.tags[0] : defaultTag;
	      if (!groups.has(tag)) {
	        groups.set(tag, []);
	      }
	      groups.get(tag).push(operation);
	    });
	    return groups;
	  },
	  getOperations: function getOperations(spec, messages) {
	    var _this2 = this;
	    var operations = [];
	    var paths = main_core.Type.isPlainObject(spec.paths) ? spec.paths : {};
	    var defaultTag = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DEFAULT_TAG', 'Untagged');
	    Object.keys(paths).sort(function (a, b) {
	      return a.localeCompare(b);
	    }).forEach(function (path) {
	      var pathData = paths[path];
	      if (!main_core.Type.isPlainObject(pathData)) {
	        return;
	      }
	      var commonParameters = main_core.Type.isArray(pathData.parameters) ? pathData.parameters : [];
	      Object.keys(pathData).filter(function (method) {
	        return HTTP_METHODS.includes(method.toLowerCase());
	      }).sort(function (a, b) {
	        var first = methodPriority.get(a.toLowerCase()) || 99;
	        var second = methodPriority.get(b.toLowerCase()) || 99;
	        return first - second;
	      }).forEach(function (method) {
	        var operationData = pathData[method];
	        if (!main_core.Type.isPlainObject(operationData)) {
	          return;
	        }
	        var operationParameters = main_core.Type.isArray(operationData.parameters) ? operationData.parameters : [];
	        var tags = main_core.Type.isArray(operationData.tags) ? operationData.tags.filter(function (tag) {
	          return main_core.Type.isStringFilled(tag);
	        }) : [];
	        operations.push({
	          method: method.toUpperCase(),
	          path: path,
	          summary: main_core.Type.isStringFilled(operationData.summary) ? operationData.summary : main_core.Type.isStringFilled(operationData.description) ? operationData.description : path,
	          description: main_core.Type.isStringFilled(operationData.description) ? operationData.description : '',
	          operationId: main_core.Type.isStringFilled(operationData.operationId) ? operationData.operationId : '',
	          tags: tags.length > 0 ? tags : [defaultTag],
	          parameters: _this2.mergeParameters(commonParameters, operationParameters),
	          requestBody: main_core.Type.isPlainObject(operationData.requestBody) ? operationData.requestBody : null,
	          responses: main_core.Type.isPlainObject(operationData.responses) ? operationData.responses : {}
	        });
	      });
	    });
	    return operations;
	  },
	  mergeParameters: function mergeParameters(commonParameters, operationParameters) {
	    var registry = new Map();
	    [].concat(babelHelpers.toConsumableArray(commonParameters), babelHelpers.toConsumableArray(operationParameters)).filter(function (parameter) {
	      return main_core.Type.isPlainObject(parameter);
	    }).forEach(function (parameter) {
	      var name = main_core.Type.isStringFilled(parameter.name) ? parameter.name : 'param';
	      var location = main_core.Type.isStringFilled(parameter["in"]) ? parameter["in"] : 'query';
	      registry.set("".concat(name, ":").concat(location), parameter);
	    });
	    return babelHelpers.toConsumableArray(registry.values());
	  },
	  renderApp: function renderApp(groups, meta, spec, messages) {
	    var _this3 = this;
	    var wrapper = this.createElement('div', 'oz-swagger-ui__app');
	    var topbar = this.renderTopbar(meta, spec, messages);
	    var content = this.createElement('div', 'oz-swagger-ui__content');
	    var controls = this.createElement('div', 'oz-swagger-ui__controls');
	    var search = this.createElement('input', 'oz-swagger-ui__search');
	    var noOperationsText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NO_OPERATIONS', 'No operations found');
	    var noResults = this.createElement('div', 'oz-swagger-ui__no-results', noOperationsText);
	    var sectionsContainer = this.createElement('div', 'oz-swagger-ui__sections');
	    var operationItems = [];
	    search.type = 'search';
	    search.placeholder = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SEARCH_PLACEHOLDER', 'Filter by path, method, or description...');
	    controls.appendChild(search);
	    groups.forEach(function (groupOperations, tag) {
	      var section = _this3.createElement('section', 'oz-swagger-ui__tag');
	      var header = _this3.createElement('button', 'oz-swagger-ui__tag-title');
	      var titleText = _this3.createElement('span', 'oz-swagger-ui__tag-title-text', tag);
	      var counter = _this3.createElement('span', 'oz-swagger-ui__tag-counter', "".concat(groupOperations.length));
	      var body = _this3.createElement('div', 'oz-swagger-ui__tag-body');
	      header.type = 'button';
	      header.appendChild(titleText);
	      header.appendChild(counter);
	      header.addEventListener('click', function () {
	        section.classList.toggle('--collapsed');
	      });
	      groupOperations.forEach(function (operation) {
	        var operationNode = _this3.renderOperation(operation, messages);
	        body.appendChild(operationNode.node);
	        operationItems.push({
	          node: operationNode.node,
	          section: section,
	          searchIndex: operationNode.searchIndex
	        });
	      });
	      section.appendChild(header);
	      section.appendChild(body);
	      sectionsContainer.appendChild(section);
	    });
	    if (operationItems.length === 0) {
	      noResults.classList.add('--visible');
	    }
	    search.addEventListener('input', function () {
	      var term = search.value.trim().toLowerCase();
	      var visibleCount = 0;
	      operationItems.forEach(function (item) {
	        var visible = term === '' || item.searchIndex.includes(term);
	        item.node.style.display = visible ? '' : 'none';
	        if (visible) {
	          visibleCount += 1;
	        }
	      });
	      sectionsContainer.querySelectorAll('.oz-swagger-ui__tag').forEach(function (section) {
	        var sectionHasVisibleOperations = babelHelpers.toConsumableArray(section.querySelectorAll('.oz-swagger-ui__operation')).some(function (operationNode) {
	          return operationNode.style.display !== 'none';
	        });
	        section.style.display = sectionHasVisibleOperations ? '' : 'none';
	      });
	      if (visibleCount > 0) {
	        noResults.classList.remove('--visible');
	      } else {
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
	  renderTopbar: function renderTopbar(meta, spec, messages) {
	    var topbar = this.createElement('div', 'oz-swagger-ui__topbar');
	    var brand = this.createElement('div', 'oz-swagger-ui__brand');
	    var brandMark = this.createElement('span', 'oz-swagger-ui__brand-mark');
	    var brandText = this.createElement('span', 'oz-swagger-ui__brand-text', this.getMessage(messages, 'OZ_SWAGGER_UI_JS_UI_TITLE', 'Swagger UI'));
	    var subtitle = this.createElement('div', 'oz-swagger-ui__subtitle', this.getMessage(messages, 'OZ_SWAGGER_UI_JS_UI_SUBTITLE', 'OpenAPI Interactive Documentation'));
	    var infoBlock = this.createElement('div', 'oz-swagger-ui__info');
	    var titleLine = this.createElement('div', 'oz-swagger-ui__title-line');
	    var title = this.createElement('h1', 'oz-swagger-ui__title', String(meta.title || 'REST API'));
	    var version = this.createElement('span', 'oz-swagger-ui__version', String(meta.version || ''));
	    var openapiVersion = this.createElement('span', 'oz-swagger-ui__openapi-version', "".concat(this.getMessage(messages, 'OZ_SWAGGER_UI_JS_OPENAPI', 'OpenAPI'), " ").concat(String(meta.openapi || spec.openapi || '')));
	    var description = this.createElement('p', 'oz-swagger-ui__description', String(meta.description || ''));
	    brand.appendChild(brandMark);
	    brand.appendChild(brandText);
	    topbar.appendChild(brand);
	    topbar.appendChild(subtitle);
	    titleLine.appendChild(title);
	    if (version.textContent !== '') {
	      titleLine.appendChild(version);
	    }
	    if (openapiVersion.textContent.trim() !== this.getMessage(messages, 'OZ_SWAGGER_UI_JS_OPENAPI', 'OpenAPI')) {
	      titleLine.appendChild(openapiVersion);
	    }
	    infoBlock.appendChild(titleLine);
	    if (description.textContent !== '') {
	      infoBlock.appendChild(description);
	    }
	    var wrapper = this.createElement('div', 'oz-swagger-ui__header');
	    wrapper.appendChild(topbar);
	    wrapper.appendChild(infoBlock);
	    return wrapper;
	  },
	  renderOperation: function renderOperation(operation, messages) {
	    var _this4 = this;
	    var container = this.createElement('article', 'oz-swagger-ui__operation');
	    var header = this.createElement('button', 'oz-swagger-ui__operation-header');
	    var methodBadge = this.createElement('span', "oz-swagger-ui__method --".concat(operation.method.toLowerCase()), operation.method);
	    var path = this.createElement('span', 'oz-swagger-ui__path', operation.path);
	    var summary = this.createElement('span', 'oz-swagger-ui__summary', operation.summary);
	    var toggleIcon = this.createElement('span', 'oz-swagger-ui__toggle', '▾');
	    var body = this.createElement('div', 'oz-swagger-ui__operation-body');
	    header.type = 'button';
	    header.appendChild(methodBadge);
	    header.appendChild(path);
	    header.appendChild(summary);
	    header.appendChild(toggleIcon);
	    var description = this.createElement('p', 'oz-swagger-ui__operation-description', operation.description || this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data'));
	    body.appendChild(description);
	    if (operation.operationId) {
	      body.appendChild(this.createMetaRow(this.getMessage(messages, 'OZ_SWAGGER_UI_JS_OPERATION_ID', 'Operation ID'), operation.operationId));
	    }
	    if (main_core.Type.isArray(operation.tags) && operation.tags.length > 0) {
	      var tagsRow = this.createElement('div', 'oz-swagger-ui__meta-row');
	      var tagsLabel = this.createElement('div', 'oz-swagger-ui__meta-label', this.getMessage(messages, 'OZ_SWAGGER_UI_JS_TAGS', 'Tags'));
	      var tagsBody = this.createElement('div', 'oz-swagger-ui__meta-value');
	      operation.tags.forEach(function (tag) {
	        tagsBody.appendChild(_this4.createElement('span', 'oz-swagger-ui__chip', tag));
	      });
	      tagsRow.appendChild(tagsLabel);
	      tagsRow.appendChild(tagsBody);
	      body.appendChild(tagsRow);
	    }
	    body.appendChild(this.renderParameters(operation.parameters, messages));
	    body.appendChild(this.renderRequestBody(operation.requestBody, messages));
	    body.appendChild(this.renderResponses(operation.responses, messages));
	    header.addEventListener('click', function () {
	      container.classList.toggle('--expanded');
	    });
	    container.appendChild(header);
	    container.appendChild(body);
	    var searchIndex = [operation.method, operation.path, operation.summary, operation.description, operation.operationId, operation.tags.join(' ')].join(' ').toLowerCase();
	    return {
	      node: container,
	      searchIndex: searchIndex
	    };
	  },
	  renderParameters: function renderParameters(parameters, messages) {
	    var _this5 = this;
	    var title = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_PARAMETERS', 'Parameters');
	    var emptyText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data');
	    if (!main_core.Type.isArray(parameters) || parameters.length === 0) {
	      return this.createSimpleSection(title, emptyText);
	    }
	    var rows = parameters.filter(function (parameter) {
	      return main_core.Type.isPlainObject(parameter);
	    }).map(function (parameter) {
	      var name = main_core.Type.isStringFilled(parameter.name) ? parameter.name : '-';
	      var location = main_core.Type.isStringFilled(parameter["in"]) ? parameter["in"] : '-';
	      var required = parameter.required === true ? _this5.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required') : '-';
	      var schemaText = _this5.stringifySchema(parameter.schema);
	      var description = main_core.Type.isStringFilled(parameter.description) ? parameter.description : '-';
	      return [name, location, required, schemaText, description];
	    });
	    return this.createTableSection(title, rows, ['Name', 'In', this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required'), this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SCHEMA', 'Schema'), this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DESCRIPTION', 'Description')]);
	  },
	  renderRequestBody: function renderRequestBody(requestBody, messages) {
	    var _this6 = this;
	    var title = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUEST_BODY', 'Request body');
	    var emptyText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data');
	    if (!main_core.Type.isPlainObject(requestBody) || !main_core.Type.isPlainObject(requestBody.content)) {
	      return this.createSimpleSection(title, emptyText);
	    }
	    var rows = Object.keys(requestBody.content).map(function (contentType) {
	      var contentItem = requestBody.content[contentType];
	      var schema = main_core.Type.isPlainObject(contentItem) ? contentItem.schema : null;
	      var schemaText = _this6.stringifySchema(schema);
	      var required = requestBody.required === true ? _this6.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required') : '-';
	      return [contentType, schemaText, required];
	    });
	    if (rows.length === 0) {
	      return this.createSimpleSection(title, emptyText);
	    }
	    return this.createTableSection(title, rows, [this.getMessage(messages, 'OZ_SWAGGER_UI_JS_CONTENT_TYPE', 'Content-Type'), this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SCHEMA', 'Schema'), this.getMessage(messages, 'OZ_SWAGGER_UI_JS_REQUIRED', 'Required')]);
	  },
	  renderResponses: function renderResponses(responses, messages) {
	    var _this7 = this;
	    var title = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_RESPONSES', 'Responses');
	    var emptyText = this.getMessage(messages, 'OZ_SWAGGER_UI_JS_NONE', 'No data');
	    if (!main_core.Type.isPlainObject(responses) || Object.keys(responses).length === 0) {
	      return this.createSimpleSection(title, emptyText);
	    }
	    var rows = Object.keys(responses).sort(function (a, b) {
	      return a.localeCompare(b);
	    }).map(function (statusCode) {
	      var response = responses[statusCode];
	      if (!main_core.Type.isPlainObject(response)) {
	        return [statusCode, '-', '-'];
	      }
	      var contentInfo = _this7.getFirstContentSchema(response.content);
	      var responseDescription = main_core.Type.isStringFilled(response.description) ? response.description : '-';
	      return [statusCode, responseDescription, contentInfo.schema];
	    });
	    return this.createTableSection(title, rows, [this.getMessage(messages, 'OZ_SWAGGER_UI_JS_STATUS', 'Status'), this.getMessage(messages, 'OZ_SWAGGER_UI_JS_DESCRIPTION', 'Description'), this.getMessage(messages, 'OZ_SWAGGER_UI_JS_SCHEMA', 'Schema')]);
	  },
	  getFirstContentSchema: function getFirstContentSchema(content) {
	    if (!main_core.Type.isPlainObject(content)) {
	      return {
	        schema: '-'
	      };
	    }
	    var firstContentType = Object.keys(content)[0];
	    if (!firstContentType) {
	      return {
	        schema: '-'
	      };
	    }
	    var contentItem = content[firstContentType];
	    if (!main_core.Type.isPlainObject(contentItem) || !main_core.Type.isPlainObject(contentItem.schema)) {
	      return {
	        schema: '-'
	      };
	    }
	    return {
	      schema: this.stringifySchema(contentItem.schema)
	    };
	  },
	  stringifySchema: function stringifySchema(schema) {
	    var _this8 = this;
	    if (!main_core.Type.isPlainObject(schema)) {
	      return '-';
	    }
	    if (main_core.Type.isStringFilled(schema.$ref)) {
	      return schema.$ref.split('/').pop() || schema.$ref;
	    }
	    if (main_core.Type.isArray(schema.oneOf) && schema.oneOf.length > 0) {
	      return schema.oneOf.map(function (item) {
	        return _this8.stringifySchema(item);
	      }).join(' | ');
	    }
	    if (main_core.Type.isArray(schema.anyOf) && schema.anyOf.length > 0) {
	      return schema.anyOf.map(function (item) {
	        return _this8.stringifySchema(item);
	      }).join(' | ');
	    }
	    if (main_core.Type.isArray(schema.allOf) && schema.allOf.length > 0) {
	      return schema.allOf.map(function (item) {
	        return _this8.stringifySchema(item);
	      }).join(' & ');
	    }
	    var type = main_core.Type.isStringFilled(schema.type) ? schema.type : 'object';
	    if (type === 'array') {
	      return "array<".concat(this.stringifySchema(schema.items), ">");
	    }
	    if (main_core.Type.isArray(schema["enum"]) && schema["enum"].length > 0) {
	      var values = schema["enum"].slice(0, 4).map(function (enumValue) {
	        return String(enumValue);
	      }).join(', ');
	      var suffix = schema["enum"].length > 4 ? ', ...' : '';
	      return "".concat(type, " (").concat(values).concat(suffix, ")");
	    }
	    if (main_core.Type.isStringFilled(schema.format)) {
	      return "".concat(type, " (").concat(schema.format, ")");
	    }
	    return type;
	  },
	  createMetaRow: function createMetaRow(labelText, valueText) {
	    var row = this.createElement('div', 'oz-swagger-ui__meta-row');
	    var label = this.createElement('div', 'oz-swagger-ui__meta-label', labelText);
	    var value = this.createElement('div', 'oz-swagger-ui__meta-value', valueText);
	    row.appendChild(label);
	    row.appendChild(value);
	    return row;
	  },
	  createSimpleSection: function createSimpleSection(title, text) {
	    var section = this.createElement('section', 'oz-swagger-ui__section');
	    var heading = this.createElement('h4', 'oz-swagger-ui__section-title', title);
	    var content = this.createElement('div', 'oz-swagger-ui__section-empty', text);
	    section.appendChild(heading);
	    section.appendChild(content);
	    return section;
	  },
	  createTableSection: function createTableSection(title, rows, columns) {
	    var _this9 = this;
	    var section = this.createElement('section', 'oz-swagger-ui__section');
	    var heading = this.createElement('h4', 'oz-swagger-ui__section-title', title);
	    var wrapper = this.createElement('div', 'oz-swagger-ui__table-wrap');
	    var table = this.createElement('table', 'oz-swagger-ui__table');
	    var tableHead = this.createElement('thead', 'oz-swagger-ui__table-head');
	    var headRow = this.createElement('tr');
	    var tableBody = this.createElement('tbody', 'oz-swagger-ui__table-body');
	    columns.forEach(function (column) {
	      var cell = _this9.createElement('th', '', column);
	      headRow.appendChild(cell);
	    });
	    tableHead.appendChild(headRow);
	    rows.forEach(function (row) {
	      var rowNode = _this9.createElement('tr');
	      row.forEach(function (value) {
	        rowNode.appendChild(_this9.createElement('td', '', String(value)));
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
	  renderError: function renderError(text) {
	    return this.createElement('div', 'oz-swagger-ui__error', text);
	  },
	  createElement: function createElement(tagName) {
	    var className = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	    var text = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
	    var node = document.createElement(tagName);
	    if (className) {
	      node.className = className;
	    }
	    if (text !== null) {
	      node.textContent = text;
	    }
	    return node;
	  },
	  getMessage: function getMessage(messages, code, fallback) {
	    if (main_core.Type.isPlainObject(messages) && main_core.Type.isStringFilled(messages[code])) {
	      return messages[code];
	    }
	    return fallback;
	  }
	};
	if (document.readyState === 'loading') {
	  document.addEventListener('DOMContentLoaded', function () {
	    App.initAll();
	  });
	} else {
	  App.initAll();
	}

	exports.App = App;

}((this.BX.Oz.SwaggerUi = this.BX.Oz.SwaggerUi || {}),BX));
//# sourceMappingURL=script.js.map
