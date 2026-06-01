/**
 * SqueezeBox Compatibility Shim for Bootstrap 5 (Refactored)
 * Resolves: SqueezeBox is undefined, classList error, and UI discrepancies
 * Support for Nested Popups and Iframe Proxying
 */
(function () {
    // If we're in an iframe and the parent has SqueezeBox, proxy to it
    if (window.parent && window.parent.SqueezeBox && window.parent !== window) {
        window.SqueezeBox = window.parent.SqueezeBox;
        window.sbox = window.parent.SqueezeBox;

        // Add delegated click listener to this iframe too, to work with proxied SqueezeBox
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof jQuery !== 'undefined') {
                jQuery(document).on('click', 'a.modal, a.squeezebox', function (e) {
                    if (this.href && this.href !== '#' && !this.href.startsWith('javascript:')) {
                        e.preventDefault();
                        window.SqueezeBox.fromElement(this);
                    }
                });
            }
        });
        return;
    }

    // Define SqueezeBox early to prevent 'undefined' errors in other scripts
    var SqueezeBox = {
        modalInstance: null,
        modalElement: null,
        options: {},
        presets: {
            onOpen: function () { },
            onClose: function () { },
            onUpdate: function () { },
            onResize: function () { },
            onMove: function () { },
            onShow: function () { },
            onHide: function () { },
            size: { x: 600, y: 600 }, // Increased default height
            sizeLoading: { x: 200, y: 150 },
            marginInner: { x: 20, y: 20 },
            marginImage: { x: 50, y: 75 },
            handler: false,
            target: null,
            closable: true,
            closeBtn: true,
            zIndex: 65555,
            overlayOpacity: .7,
            classWindow: "",
            classOverlay: "",
            overlayFx: {},
            resizeFx: {},
            contentFx: {},
            parse: false,
            parseSecure: false,
            shadow: true,
            overlay: true,
            document: null,
            ajaxOptions: {}
        },

        initialize: function (options) {
            this.presets = Object.assign({}, this.presets, options || {});
            return this;
        },

        assign: function (links, options) {
            var self = this;
            if (typeof jQuery !== 'undefined') {
                jQuery(links).on('click', function (e) {
                    e.preventDefault();
                    self.fromElement(this, options);
                });
            } else {
                var elements = document.querySelectorAll(links);
                for (var i = 0; i < elements.length; i++) {
                    elements[i].addEventListener('click', function (e) {
                        e.preventDefault();
                        self.fromElement(this, options);
                    });
                }
            }
            return this;
        },

        fromElement: function (element, options) {
            var url = element.getAttribute('href') || (options ? options.url : '');

            var relOptions = {};
            var rel = element.getAttribute('rel');
            if (rel && rel.indexOf('{') !== -1) {
                try {
                    var jsonStr = rel.replace(/([a-zA-Z0-9_]+):/g, '"$1":').replace(/'/g, '"');
                    relOptions = JSON.parse(jsonStr);
                } catch (e) {
                    try { relOptions = eval('(' + rel + ')'); } catch (e2) { }
                }
            }

            var finalOptions = Object.assign({}, this.presets, relOptions, options || {});
            return this.open(url, finalOptions);
        },

        open: function (url, options) {
            options = Object.assign({}, this.presets, options || {});
            this.options = options;
            var self = this;

            if (!url && options.url) url = options.url;

            if (!this.modalElement) {
                this.createModal();
            }

            var modalBody = this.modalElement.querySelector('#sbox-content');
            var sboxWindow = this.modalElement.querySelector('#sbox-window');
            var dialog = this.modalElement.querySelector('.modal-dialog');

            // Clear previous content
            modalBody.innerHTML = '';
            // Reset height/width to ensure next popup shows correctly
            modalBody.style.height = '';
            dialog.style.maxWidth = '';
            dialog.style.width = '';

            if (options.handler === 'iframe' || !options.handler) {
                // Show loader
                var loader = this.modalElement.querySelector('#tjucm_loader');
                if (loader) loader.classList.add('active');

                var iframe = document.createElement('iframe');
                iframe.src = url;
                iframe.frameBorder = "0";
                iframe.className = "w-100 h-100 d-block";

                iframe.onload = function () {
                    // Hide loader
                    if (loader) loader.classList.remove('active');
                };

                modalBody.appendChild(iframe);
            } else {
                modalBody.innerHTML = '<div class="p-4">Unsupported handler: ' + options.handler + '</div>';
            }

            // Apply Size and Classes
            sboxWindow.className = 'modal-content bg-white border-0 rounded-1 position-relative overflow-hidden ' + (options.classWindow || '');
            if (options.shadow !== false) sboxWindow.classList.add('shadow');

            if (options.size) {
                if (options.size.x) {
                    dialog.style.maxWidth = options.size.x + 'px';
                    dialog.style.width = options.size.x + 'px';
                }
                if (options.size.y) {
                    modalBody.style.height = options.size.y + 'px';
                }
            }

            // Show using Bootstrap
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                this.modalInstance = bootstrap.Modal.getOrCreateInstance(this.modalElement, {
                    backdrop: options.closable === false ? 'static' : true,
                    keyboard: options.closable === false ? false : true
                });
                this.modalInstance.show();
                if (typeof options.onOpen === 'function') options.onOpen();
            } else {
                console.error('SqueezeBox Shim: Bootstrap Modal not found.');
            }

            return this;
        },

        close: function () {
            if (this.modalInstance) {
                this.modalInstance.hide();
            } else if (this.modalElement) {
                var inst = bootstrap.Modal.getInstance(this.modalElement);
                if (inst) inst.hide();
            }
        },

        createModal: function () {
            var modalId = 'bootstrap-sbox-container';
            if (document.getElementById(modalId)) {
                this.modalElement = document.getElementById(modalId);
                return;
            }

            var modalHtml = `
                <div class="modal fade sbox-compat-wrapper" id="${modalId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white border-0 rounded-1 position-relative overflow-hidden" id="sbox-window">
                            <button type="button" class="btn-close" id="sbox-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            <div class="modal-body p-0 overflow-hidden rounded-1" id="sbox-content">
                            </div>
                            <!-- Loader structure styled via custom.css -->
                            <div id="item-form-popup-wrapper" class="position-absolute top-0 left-0 w-100 h-100 pointer-events-none z-index-1060 overflow-hidden">
                                <div class="overlay" id="tjucm_loader">
                                    <div class="loader"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            var div = document.createElement('div');
            div.innerHTML = modalHtml;
            document.body.appendChild(div.firstElementChild);
            this.modalElement = document.getElementById(modalId);

            var self = this;
            this.modalElement.addEventListener('hidden.bs.modal', function () {
                var modalBody = self.modalElement.querySelector('#sbox-content');
                if (modalBody) modalBody.innerHTML = '';

                var loader = self.modalElement.querySelector('#tjucm_loader');
                if (loader) loader.classList.remove('active');

                if (self.options && typeof self.options.onClose === 'function') {
                    self.options.onClose();
                }
            });
        }
    };

    // Export to global scope
    window.SqueezeBox = SqueezeBox;
    window.sbox = SqueezeBox;

    // Listen for clicks on legacy triggers
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('click', 'a.modal, a.squeezebox', function (e) {
                if (this.href && this.href !== '#' && !this.href.startsWith('javascript:')) {
                    e.preventDefault();
                    window.SqueezeBox.fromElement(this);
                }
            });
        }
    });

    // Support window.parent access if scripts expect it
    if (typeof window.jModalClose === 'undefined') {
        window.jModalClose = function () {
            window.SqueezeBox.close();
        };
    }
})();
