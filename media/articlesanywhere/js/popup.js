/**
 * @package         Articles Anywhere
 * @version         17.2.10PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

(function() {
    'use strict';

    window.RegularLabs = window.RegularLabs || {};

    window.RegularLabs.ArticlesAnywherePopup = window.RegularLabs.ArticlesAnywherePopup || {
        form          : null,
        options       : {},
        tag_characters: {},
        group         : null,
        tag_type      : '',

        init: function() {
            if ( ! parent.RegularLabs.ArticlesAnywhereButton) {
                document.querySelector('body').innerHTML = '<div class="alert alert-error">This page cannot function on its own.</div>';
                return;
            }

            this.options = Joomla.getOptions ? Joomla.getOptions('rl_articlesanywhere_button', {}) : Joomla.optionsStorage.rl_articlesanywhere_button || {};

            if ( ! this.options.editor_name) {
                document.querySelector('body').innerHTML = 'No editor name found.';
                return;
            }

            this.form = document.querySelector('[name="articlesAnywhereForm"]');

            this.tag_characters.start      = this.options.tag_characters[0];
            this.tag_characters.end        = this.options.tag_characters[1];
            this.tag_characters.data_start = this.options.tag_characters_data[0];
            this.tag_characters.data_end   = this.options.tag_characters_data[1];

            setInterval(() => {
                this.updatePreview();
            }, 250);
        },

        insertText: function() {
            parent.RegularLabs.ArticlesAnywhereButton.insertText(this.options.editor_name);
        },

        updatePreview: function() {
            const self = this;

            const preview_message = document.querySelector('#preview_message');
            const preview_code    = document.querySelector('#preview_code');
            const preview_spinner = document.querySelector('#preview_spinner');

            Regular.addClass(preview_message, 'hidden');
            Regular.addClass(preview_code, 'hidden');
            Regular.removeClass(preview_spinner, 'hidden');

            const code             = this.generateCode();
            preview_code.innerHTML = code;

            parent.RegularLabs.ArticlesAnywhereButton.setCode(code);

            Regular.removeClass(preview_message, 'hidden');
            Regular.addClass(preview_spinner, 'hidden');

            if (code) {
                Regular.addClass(preview_message, 'hidden');
                Regular.removeClass(preview_code, 'hidden');
            }

            if (document.querySelectorAll('joomla-field-subform[name="filters"] div.subform-repeatable-group').length < 1) {
                document.querySelector('joomla-field-subform[name="filters"] .group-add').click();
            }

            if (document.querySelectorAll('joomla-field-subform[name="data_tags"] div.subform-repeatable-group').length < 1) {
                document.querySelector('joomla-field-subform[name="data_tags"] .group-add').click();
            }

            setTimeout(() => {
                addEventListeners();
            }, 10);

            function addEventListeners() {
                // Fix broken references to fields in subform (stupid Joomla!)
                self.form.querySelectorAll('.subform-repeatable-group').forEach((group) => {
                    const group_name = group.dataset['group'];
                    const x_name     = group.dataset['baseName'] + 'X';

                    const regex = new RegExp(x_name, 'g');

                    const sub_elements = group.querySelectorAll(`[id*="${group_name}_"],` + `[id*="${x_name}_"],` + `[data-for*="${x_name}_"],` + `[data-for*="${x_name}]"]`);

                    sub_elements.forEach((el) => {
                        if (el.dataset['for']) {
                            el.dataset['for'] = el.dataset['for'].replace(regex, group_name);
                        }
                        if (el.getAttribute('oninput')) {
                            el.setAttribute('oninput', el.getAttribute('oninput').replace(regex, group_name));
                        }
                        if (el.id) {
                            el.id = el.id.replace(regex, group_name);
                        }
                    });
                });
            }
        },

        generateCode: function() {
            const self = this;

            setSingleTag();

            const filters = getFilters();

            let attributes = convertToAttributes(filters);

            if (attributes === '') {
                return '';
            }

            const data_tags = getDataTags();

            let content = data_tags.join('');

            /* Use multiple {articles} tag */
            if (Object.keys(filters).length && (Object.keys(filters).length > 1 || (filters[0].article === undefined && filters[0].title === undefined && filters[0].id === undefined))) {
                setMultipleTag();

                const extra_attributes = getAttributes();
                attributes             = (attributes + ' ' + convertToAttributes(extra_attributes)).trim();
            }

            if (attributes === 'article="current"') {
                attributes = '';
            }

            return '<p>' + wrapTag(this.tag_type + ' ' + attributes) + content + wrapTag('/' + this.tag_type) + '</p>';

            function setSingleTag() {
                self.tag_type = self.options.article_tag;
            }

            function setMultipleTag() {
                self.tag_type = self.options.articles_tag;
            }

            function isMultipleTag() {
                return self.tag_type === self.options.articles_tag;
            }

            function wrapTag(string) {
                return self.tag_characters.start + string.trim() + self.tag_characters.end;
            }

            function getAttributes() {
                self.group = '';

                let attributes = [];

                const ordering = getOrdering();

                if (ordering) {
                    attributes.push(ordering);
                }

                const limit = getLimit();

                if (limit) {
                    attributes.push(limit);
                }

                const pagination = getPagination();

                if (pagination) {
                    attributes.push(pagination);
                }

                const separator = getSeparator();

                if (separator) {
                    attributes.push(separator);
                }

                const empty_text = getOutputWhenEmpty();

                if (empty_text) {
                    attributes.push(empty_text);
                }

                return attributes;

                function getOrdering() {
                    const ordering = getData('ordering');
                    if ( ! ordering) {
                        return false;
                    }

                    if (ordering === 'random') {
                        return {'ordering': 'random'}
                    }

                    const direction = getData('ordering_direction');

                    return {'ordering': ordering + ' ' + direction};
                }

                function getLimit() {
                    const use_limit = getData('use_limit');

                    if ( ! use_limit) {
                        return false;
                    }

                    const limit = Math.max(1, parseInt(getData('limit')));

                    return {'limit': limit};
                }

                function getPagination() {
                    const use_pagination = getData('use_pagination');

                    if (use_pagination === '') {
                        return false;
                    }

                    if ( ! use_pagination) {
                        return {'pagination': 'false'};
                    }

                    if ( ! getData('use_per_page')) {
                        return {'pagination': 'true'};
                    }

                    const per_page = Math.max(1, parseInt(getData('per_page')));

                    return {
                        'pagination': 'true',
                        'per-page'  : per_page
                    };
                }

                function getSeparator() {
                    let separator = getData('separator');

                    if ( ! separator) {
                        return false;
                    }

                    separator = separator
                        .replace('<', '&lt;')
                        .replace('>', '&gt;');

                    return {'separator': separator};
                }

                function getOutputWhenEmpty() {
                    let empty_text = getData('output_when_empty');

                    if ( ! empty_text) {
                        return false;
                    }

                    empty_text = empty_text
                        .replace('<', '&lt;')
                        .replace('>', '&gt;');

                    return {'empty': empty_text};
                }

                function getData(id) {
                    return getDataByType('', id);
                }
            }

            function getFilters() {
                let filters = [];

                const groups      = getFilterGroupsElements();
                const add_buttons = getFilterAddButtonsElements();

                Regular.removeClass(groups, 'hidden');
                Regular.removeClass(add_buttons, 'hidden');
                Regular.removeClass('.filter-type', 'hidden');

                for (let group of groups) {
                    self.group = group.dataset['group'];

                    const type = getData('type');

                    if ( ! type) {
                        continue;
                    }

                    hideElementsBasedOnFilterType(type, group);

                    const filter = getFilter(type);

                    if ( ! filter) {
                        continue;
                    }

                    if (type === 'article') {
                        return [filter];
                    }

                    filters.push(filter);
                }

                return filters;
                
                function getFilter(type) {
                    if ( ! type || ! type.length) {
                        return false;
                    }

                    switch (type) {
                        case 'article':
                            return getArticle();

                        case 'categories':
                            return getCategories();

                        case 'authors':
                            return getAuthors();

                        case 'date':
                            return getDate();

                        case 'tags':
                            return getTags();

                        case 'field':
                            return getField();

                        default:
                            return false;
                    }
                }

                function getArticle() {
                    const type = getData('article_type');

                    if (type === 'current') {
                        return {'article': 'current'};
                    }

                    const key     = getData('article_key');
                    const article = getData('article', key === 'title');

                    if ( ! article) {
                        return false;
                    }

                    return {
                        [key]: article
                    };
                }

                function getCategories() {
                    const type = getData('categories_type');

                    if (type === 'current') {
                        return {'category': 'current'};
                    }

                    let key          = getData('categories_key');
                    const categories = getData('categories', key === 'title');

                    if ( ! categories || ! categories.length) {
                        return false;
                    }

                    key = key === 'title' ? 'category' : 'category:id';

                    const filters = {
                        [key]: categories.join(',')
                    };

                    const include_children = getData('categories_include_children');

                    if (include_children !== '') {
                        filters['include-child-categories'] = include_children ? 'true' : 'false';
                    }

                    return filters;
                }

                function getAuthors() {
                    const type = getData('authors_type');

                    if (type === 'current') {
                        return {'author': 'current'};
                    }

                    let key       = getData('authors_key');
                    const authors = getData('authors', key === 'name');

                    if ( ! authors || ! authors.length) {
                        return false;
                    }

                    key = key === 'name' ? 'author' : 'author:id';

                    return {
                        [key]: authors.join(',')
                    };
                }

                function getDate() {
                    const key        = getData('date_key');
                    const comparison = getData('date_comparison');

                    if (comparison === 'before' || comparison === 'after') {
                        const type = getData('date_type');
                        const date = type === 'now' ? 'now()' : getData('date');

                        if ( ! date) {
                            return false;
                        }

                        const prefix = comparison === 'before' ? '&lt;' : '&gt;';

                        return {[key]: `${prefix}${date}`};
                    }

                    const date_from = getData('date_from');
                    const date_to   = getData('date_to');

                    if ( ! date_from || ! date_to) {
                        return false;
                    }

                    return {[key]: `${date_from} to ${date_to}`};
                }

                function getTags() {
                    const type = getData('tags_type');

                    if (type === 'current') {
                        return {'tags': 'current'};
                    }

                    const tags = getData('tags', true);

                    if ( ! tags || ! tags.length) {
                        return false;
                    }

                    const glue = getData('tags_must_contain') === 'all' ? ' && ' : ',';

                    return {'tags': tags.join(glue)};
                }

                function getField() {
                    const field = getData('field');

                    if ( ! field) {
                        return false;
                    }

                    const type = getData('field_type');

                    if (type === 'current') {
                        return {[field]: 'current'};
                    }

                    const value = getData('field_value');

                    return {[field]: value};
                }

                function getData(id, use_text = false) {
                    return getDataByType('filters', id, use_text);
                }

                function hideElementsBasedOnFilterType(type, group) {
                    if ( ! type) {
                        return;
                    }

                    if (type === 'article') {
                        Regular.addClass(getFilterGroupsElements(), 'hidden');
                        Regular.removeClass(group, 'hidden');
                        Regular.addClass(getFilterAddButtonsElements(), 'hidden');
                        return;
                    }

                    Regular.addClass(`.filter-type.type-article, .filter-type.type-${type}`, 'hidden');
                }

                function getFilterGroupsElements() {
                    return document.querySelectorAll('joomla-field-subform[name="filters"] div.subform-repeatable-group');
                }

                function getFilterAddButtonsElements() {
                    return document.querySelectorAll('joomla-field-subform[name="filters"] .group-add, joomla-field-subform[name="filters"] .group-move');
                }
            }

            function getDataTags() {
                let data_tags = [];

                document.querySelectorAll('joomla-field-subform[name="data_tags"] div.subform-repeatable-group').forEach((group) => {
                    self.group     = group.dataset['group'];
                    const data_tag = getDataTag('type');

                    if ( ! data_tag) {
                        return;
                    }

                    data_tags.push(data_tag);
                });

                return data_tags;

                function getDataTag() {
                    const type = getData('type');

                    if ( ! type || ! type.length) {
                        return false;
                    }

                    switch (type) {
                        case 'newline':
                            return '<br>';

                        case 'article':
                            return getArticle();

                        case 'title':
                            return getTitle();

                        case 'text':
                            return getText();

                        case 'readmore':
                            return getReadmore();

                        case 'image':
                            return getImage();

                        case 'category':
                        case 'parent-category':
                            return getCategory(type);

                        case 'date':
                            return getDate();

                        case 'field':
                            return getField();

                        default:
                            return wrapTag(type);
                    }

                    function getArticle() {
                        const layout = getData('article_layout');

                        let attributes = {};

                        if (layout) {
                            attributes.layout = layout;
                        }

                        return wrapTag('article ' + convertToAttributes(attributes));
                    }

                    function getTitle() {
                        const heading = getData('title_heading');

                        let tag = wrapTag('title');

                        if (getData('title_add_link')) {
                            tag = wrapTag('link') + tag + wrapTag('/link');
                        }

                        if (heading) {
                            tag = `</p><${heading}>${tag}</${heading}><p>`;
                        }

                        return tag;
                    }

                    function getCategory(key) {
                        const prefix = key.replace('-', '_');

                        let tag = wrapTag(key);

                        if (getData(`${prefix}_add_link`)) {
                            tag = wrapTag(`${key}:link`) + tag + wrapTag(`/${key}:link`);
                        }

                        return tag;
                    }

                    function getDate() {
                        const key         = getData('date_key');
                        const date_format = getData('date_format');

                        let attributes = {};

                        if (date_format) {
                            attributes.format = date_format === 'other' ? getData('date_format_custom') : date_format;
                        }

                        return wrapTag(key + ' ' + convertToAttributes(attributes));

                    }

                    function getText() {
                        const key          = getData('text_key');
                        const limit_by     = getData('text_limit_by');
                        const use_ellipsis = getData('use_ellipsis');
                        const strip        = getData('text_strip');

                        let attributes = {};

                        if (limit_by) {
                            attributes[limit_by] = parseInt(getData(`text_max_length_${limit_by}`));
                        }

                        if (use_ellipsis !== '') {
                            attributes['use-ellipsis'] = use_ellipsis ? 'true' : 'false';
                        }

                        if (strip) {
                            attributes.html = 'false';
                        }

                        return wrapTag(key + ' ' + convertToAttributes(attributes));
                    }

                    function getReadmore() {
                        const text      = getData('readmore_text');
                        const classname = getData('readmore_class');

                        let attributes = {};

                        if (text) {
                            attributes.text = text;
                        }

                        if (classname) {
                            attributes.class = classname;
                        }

                        return wrapTag('readmore ' + convertToAttributes(attributes));
                    }

                    function getImage() {
                        let key          = getData('image_key');
                        let content_type = getData('image_content_type');
                        let number       = getData('image_number');
                        let width        = getData('image_width');
                        let height       = getData('image_height');

                        number = Math.max(1, parseInt(number) ? parseInt(number) : 1);
                        width  = Math.max(0, parseInt(width) ? parseInt(width) : 0);
                        height = Math.max(0, parseInt(height) ? parseInt(height) : 0);

                        if (key === 'content') {
                            key = 'image-' + (content_type === 'select' ? number : 'random');
                        }

                        let attributes = {};

                        if (width) {
                            attributes.width = width;
                        }

                        if (height) {
                            attributes.height = height;
                        }

                        let tag = wrapTag(key + ' ' + convertToAttributes(attributes));

                        if (getData('image_add_link')) {
                            tag = wrapTag('link') + tag + wrapTag('/link');
                        }

                        return tag;
                    }

                    function getField() {
                        const field = getData('field_name');

                        if ( ! field) {
                            return false;
                        }

                        let attributes = {};

                        if (getData('field_show_label')) {
                            attributes.showlabel = 'true';
                        }

                        return wrapTag(field + ' ' + convertToAttributes(attributes));
                    }
                }

                function getData(id, use_text = false) {
                    return getDataByType('data_tags', id, use_text);
                }

                function wrapTag(string) {
                    return self.tag_characters.data_start + string.trim() + self.tag_characters.data_end;
                }
            }

            function convertToAttributes(groups) {
                const attributes = [];

                for (let key in groups) {
                    const value = groups[key];

                    if (typeof value !== 'object') {
                        attributes.push(key + '="' + value + '"');
                        continue;
                    }

                    attributes.push(Object.entries(value).map((key_value) => key_value[0] + '="' + key_value[1] + '"').join(' '));
                }

                return attributes.join(' ');
            }

            function setFormTagType(value = '') {
                const element = getFormElement('tag_type');
                element.value = value;
                element.dispatchEvent(new Event('change'));
            }

            function getFormElement(id, type = '') {
                const group  = self.group ? `[${self.group}]` : '';
                const prefix = type ? `${type}${group}` : '';

                let element = prefix ? `${prefix}[${id}]` : id;

                if ( ! self.form[element]) {
                    element += '[]';
                }

                if ( ! self.form[element] && group) {
                    // keep space between groups separate, otherwise the js minifier will remove it
                    element = document.querySelector(`div[data-group="${self.group}"]` + ' ' + `[name="${id}"]`);
                }

                return typeof element !== 'string' ? element : self.form[element];
            }

            function getDataByType(type, id, use_text = false) {
                let element = getFormElement(id, type);

                if ( ! element) {
                    return '';
                }

                if (element.options === undefined) {
                    return parseValue(element.value);
                }

                let selected = [];
                for (let option of element.options) {
                    if ( ! option.selected || ! option.value.length || option.value === '-') {
                        continue;
                    }

                    if (use_text) {
                        const text = option.innerText
                            .replace(/^[ -]*/, '')
                            .replace(/ \[.*$/, '')
                            .replace('<', '&lt;')
                            .replace('>', '&gt;');

                        selected.push(text);
                        continue;
                    }

                    selected.push(parseValue(option.value));
                }

                if (element.type !== 'select-multiple') {
                    return selected.length ? selected[0] : '';
                }

                return selected;

                function parseValue(string) {
                    string = string.toString().valueOf();

                    if (string === '1' || string === 'true') {
                        return 1;
                    }

                    if (string === '0' || string === 'false') {
                        return 0;
                    }

                    return string;
                }
            }
        },
    };
})();
