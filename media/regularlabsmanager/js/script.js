/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

(function() {
    'use strict';

    window.RegularLabs = window.RegularLabs || {};

    window.RegularLabs.Manager = window.RegularLabs.Manager || {
        form              : null,
        container         : null,
        options           : {},
        tag_characters    : {},
        group             : null,
        do_update         : false,
        tag_type          : '',
        process_name      : '',
        last_form_data    : '',
        process_start_time: Date.now(),
        time_to_process   : 4000,
        queue             : [],
        failed            : [],
        broken            : [],
        updates_available : [],
        not_installed     : [],

        init: function() {
            this.container = document.querySelector('#regularlabsmanager');
            this.spinner   = this.container.querySelector('#rlem_spinner');
            this.content   = this.container.querySelector('#rlem_content');
            this.error     = this.container.querySelector('#rlem_error');
            this.form      = this.container.querySelector('#regularlabsmanagerForm');

            this.back_button       = document.querySelector('.button-back');
            this.refresh_button    = document.querySelector('.button-refresh');
            this.retry_button      = document.querySelector('.button-retry');
            this.update_all_button = document.querySelector('.button-update_all');

            this.refresh();
        },

        startLoad: function(task) {
            window.scrollTo({top: 0, left: 0});

            this.container.style.height = this.container.offsetHeight + 'px';

            Regular.removeClass(this.spinner, 'hidden');
            Regular.addClass(this.content, 'hidden');
            Regular.addClass(this.error, 'hidden');

            this.back_button.blur();
            this.refresh_button.blur();
            Regular.addClass(this.back_button, 'hidden disabled');
            Regular.addClass(this.refresh_button, 'hidden disabled');
            Regular.addClass(this.update_all_button, 'hidden disabled');
            Regular.addClass(this.retry_button, 'hidden disabled');

            switch (task) {
                case 'discover.display':
                    this.startLoadDiscover();
                    break;

                case 'process.start':
                case 'process.display':
                    this.startLoadProcess();
                    break;

                default:
                    break;
            }
        },

        endLoad: function(data = '', task = '') {
            this.form = this.container.querySelector('#regularlabsmanagerForm');

            switch (task) {
                case 'discover.display':
                    this.endLoadDiscover(data);
                    break;

                case 'process.display':
                    this.endLoadProcess(data);
                    break;

                default:
                    break;
            }

            // disable buttons and links in parent element with class disabled
            document.querySelectorAll('.disabled button, .disabled a').forEach((el) => {
                Regular.addClass(el, 'disabled');
            });

            Regular.addClass(this.spinner, 'hidden');

            document.dispatchEvent(new Event('rl-update-form-descriptions'));
        },

        startLoadDiscover: function() {
            Regular.removeClasses(this.refresh_button, 'hidden');
        },

        endLoadDiscover: function(data = '') {
            Regular.removeClasses(this.refresh_button, 'hidden disabled');

            // show Update All button if updates are found
            if (data && data.indexOf('rlem-update-all') !== -1) {
                Regular.removeClass(this.update_all_button, 'hidden disabled');
            }

            // Hide the Update All button if the Extension Manager needs to be updated
            if (Regular.hasClass(this.form, 'has_extensionmanager')) {
                Regular.addClass(this.update_all_button, 'hidden disabled');
            }
        },

        startLoadProcess: function() {
            Regular.removeClasses(this.back_button, 'hidden');
        },

        endLoadProcess: function(data = '') {
            Regular.removeClasses(this.back_button, 'hidden disabled');

            if (this.failed.length) {
                Regular.removeClass(this.retry_button, 'hidden disabled');
            }
        },

        refresh: function(refresh = false) {
            this.loadPage(
                'discover.display',
                {
                    'refresh': refresh ? '1' : '0'
                },
                (() => {
                    this.setExtensionsByStates();
                })
            );
        },

        setExtensionsByStates: function(extensions = []) {
            const states = ['broken', 'not_installed', 'updates_available'];

            states.forEach((state) => {
                this[state] = [];
                document.querySelectorAll(`[data-state="${state}"]`).forEach((el) => {
                    this[state].push(el.dataset.extension);
                });
            });
        },

        start: function(extensions = []) {
            this.queue  = [];
            this.failed = [];

            this.loadPage(
                'process.start',
                {
                    'process'   : this.process_name,
                    'extensions': extensions
                },
                (() => {
                    this.startQueue();
                })
            );
        },

        update: function(extension = '') {
            this.process_name = 'update';

            const extensions = extension === ''
                ? this.updates_available
                : [extension];

            this.start(extensions);
        },

        install: function(extension = '') {
            this.process_name = 'install';

            const extensions = extension === ''
                ? this.getSelected()
                : [extension];

            if ( ! extensions.length) {
                return;
            }

            this.start(extensions);
        },

        downgrade: function(extension = '') {
            this.process_name = 'downgrade';

            this.start([extension]);
        },

        uninstall: function(extension = '') {
            this.process_name = 'uninstall';

            this.start([extension]);
        },

        reinstall: function(extension = '') {
            this.process_name = 'reinstall';

            const extensions = extension === ''
                ? this.broken
                : [extension];

            this.start(extensions);
        },

        retry: function() {
            const extensions = [];

            this.failed.forEach((extension) => {
                extensions.push(extension.extension);
            });

            this.failed = [];

            this.start(extensions);
        },

        startQueue: function() {
            const progress_bars = document.querySelectorAll('.progress-bar[data-extension]');

            progress_bars.forEach((progress_bar) => {
                this.queue.push({
                    extension: progress_bar.dataset.extension,
                    url      : progress_bar.dataset.url,
                    bar      : progress_bar
                });
            });

            this.processNext(0);
        },

        processNext: function(id) {
            if (this.queue[id] === undefined) {
                this.loadPage('process.display');
                return;
            }

            const current = this.queue[id];

            // set to current time - start time
            this.process_start_time = Date.now();

            this.progressProgressBar(id);

            const task = this.process_name === 'uninstall' ? 'uninstall' : 'install';

            Regular.loadUrl(
                'index.php',
                {
                    'option'                         : 'com_regularlabsmanager',
                    'task'                           : 'process.' + task,
                    'extension'                      : current.extension,
                    'url'                            : current.url,
                    [Joomla.getOptions('csrf.token')]: 1,
                },
                (data) => {
                    this.finishProcess(id, data);
                },
                (data) => {
                    this.failProcess(id);
                }
            );
        },

        progressProgressBar: function(id, value = 0, className = '') {
            if (this.queue[id] === undefined) {
                return;
            }

            const current = this.queue[id];

            // Add random jumps
            const current_progress = this.progressBar(current.bar, value);

            if (className) {
                Regular.addClass(current.bar, className);
            }

            if (current_progress >= 100) {
                Regular.removeClass(current.bar, 'progress-bar-striped');
            }

            if (value || current_progress >= 90) {
                return;
            }

            // progress at a random interval between 0 and 10% of the total progress bar
            const random = Math.floor(Math.random() * (this.time_to_process / 10)) + 1;

            setTimeout(() => {
                this.progressProgressBar(id);
            }, Math.floor(random + 1));
        },

        finishProcess: function(id, data = '0') {
            if (data === '0') {
                this.failProcess(id);
                return;
            }

            this.progressProgressBar(id, 100, 'bg-success');

            // set to current time - start time
            this.time_to_process = Date.now() - this.process_start_time;

            this.processNext(id + 1);
        },

        failProcess: function(id) {
            const current = this.queue[id];

            this.failed.push(current);

            this.progressProgressBar(id, 100, 'bg-danger');

            // set to current time - start time
            this.time_to_process = Date.now() - this.process_start_time;

            this.processNext(id + 1);
        },

        progressBar: function(bar, progress = 0) {
            progress = parseInt(progress);

            if ( ! progress) {
                // random value between 1 - 10
                progress = Math.floor((Math.random() * 10) + 1);
            }

            const current_progress = parseInt(bar.getAttribute('aria-valuenow'));
            const total_progress   = Math.min(current_progress + progress, 100);

            bar.style.width = `${total_progress}%`;
            bar.setAttribute('aria-valuenow', total_progress);

            return total_progress;
        },

        updateByUrl: function(url = '') {
            this.loadPage('update.update', {'url': url});
        },

        loadPage: function(task, params = {}, success = null, fail = null) {
            this.startLoad(task);

            if (params.refresh === undefined || parseInt(params.refresh) === 1) {
                const core_messages = document.querySelector('#system-message-container');
                Regular.addClass(core_messages, 'hidden');
            }

            Regular.loadUrl(
                'index.php',
                {
                    'option': 'com_regularlabsmanager',
                    'task'  : task,
                    ...params
                },
                (data) => {
                    this.updatePage(data, success, task);
                },
                (data) => {
                    this.updatePage(null, fail, task);
                }
            );
        },

        updatePage: function(data, callback = null, task = '') {
            this.container.style.height = 'auto';

            if ( ! data) {
                Regular.removeClass(this.error, 'hidden');

                this.endLoad(null, task);

                callback && callback();
                return;
            }

            this.content.innerHTML = data;

            Regular.removeClass(this.content, 'hidden');

            this.endLoad(data, task);

            callback && callback();
        },

        getSelected: function() {
            const form_data = this.getFormData();

            return form_data['extensions[]'];
        },

        getFormData: function() {
            const form_data = new FormData(this.form);

            const object = {};

            form_data.forEach((value, key) => {
                // Reflect.has in favor of: object.hasOwnProperty(key)
                if (Reflect.has(object, key)) {
                    if ( ! Array.isArray(object[key])) {
                        object[key] = [object[key]];
                    }

                    object[key].push(value);
                    return;
                }

                object[key] = value;

            });

            return object;
        },
    };
})();
