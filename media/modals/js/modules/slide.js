/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

'use strict';

import {Helper} from './helper.js?12.3.5.p';

export function Slide(modal, link, id) {
    this.modal          = modal;
    this.link           = link;
    this.id             = id;
    this.settings       = JSON.parse(JSON.stringify(modal.settings));
    this.element        = null;
    this.elements       = {};
    this.eventListeners = [];
    this.status         = 'closed';
    this.isLoaded       = false;
    this.isResizing     = false;

    const init = async () => {
        const initSettings = () => {
            return new Promise((resolve) => {
                Object.entries(this.settings).forEach(([key, value]) => {
                    const dataValue = Helper.getData(this.link, key.toLowerCase());

                    if (dataValue === undefined) {
                        return;
                    }

                    this.settings[key] = dataValue;
                });

                resolve();
            });
        };

        const createElements = () => {
            return new Promise((resolve) => {
                const createElements = () => {
                    this.element = Helper.createElementFromHtml(this.settings.htmlTemplates.slide);

                    Helper.hide(this.element);

                    this.elements.container    = this.element.querySelector('[data-modals-element="slide-container"]');
                    this.elements.content      = this.element.querySelector('[data-modals-element="slide-content"]');
                    this.elements.contentInner = this.element.querySelector('[data-modals-element="slide-content-inner"]');
                    this.elements.before       = this.element.querySelector('[data-modals-element="slide-before"]');
                    this.elements.after        = this.element.querySelector('[data-modals-element="slide-after"]');
                    this.elements.countdown = this.element.querySelector('[data-modals-element="countdown"]');
                };

                const setPosition = () => {
                    Helper.setData(this.element, 'position', this.settings.position || '');
                };

                const setOrientation = () => {
                    let orientation = '';

                    const title = Helper.getData(this.link, 'title');
                    if (title) {
                        const title_element = Helper.createElementFromHtml(
                            this.settings.htmlTemplates.title.replace(
                                /%titleTagType%/g,
                                this.settings.titleTagType
                            )
                        );
                        title_element.appendChild(Helper.createElementFromHtml(title));

                        const titlePosition = this.settings.titlePosition === 'bottom' || this.settings.titlePosition === 'right'
                            ? 'after' : 'before';
                        orientation         = this.settings.titlePosition === 'left' || this.settings.titlePosition === 'right'
                            ? 'horizontal' : 'vertical';

                        this.elements[titlePosition].appendChild(title_element);
                        Helper.show(this.elements[titlePosition]);
                    }

                    const description = Helper.getData(this.link, 'description');
                    if (description) {
                        const description_element = Helper.createElementFromHtml(this.settings.htmlTemplates.description);
                        description_element.appendChild(Helper.createElementFromHtml(description));

                        let descriptionPosition = this.settings.descriptionPosition === 'bottom' || this.settings.descriptionPosition === 'right'
                            ? 'after' : 'before';
                        if (Helper.getData(this.link, 'legacy')) {
                            descriptionPosition = 'before';
                        }
                        if ( ! orientation) {
                            orientation = this.settings.descriptionPosition === 'left' || this.settings.descriptionPosition === 'right'
                                ? 'horizontal' : 'vertical';
                        }

                        this.elements[descriptionPosition].appendChild(description_element);
                        Helper.show(this.elements[descriptionPosition]);
                    }

                    Helper.setData(this.element, 'orientation', orientation || 'vertical');
                };

                const setDimensions = () => {
                    let width  = Helper.getData(this.link, 'width');
                    let height = Helper.getData(this.link, 'height');

                    const type = this.getType();

                    switch (type) {
                        case 'image':
                            width  = 0;
                            height = 0;
                            break;

                        case 'video':
                            width  = width && width !== '0' ? width : this.settings.videoWidth;
                            height = height && height !== '0' ? height : this.settings.videoHeight;
                            break;

                        case 'iframe':
                            width  = width && width !== '0' ? width : this.settings.iframeWidth;
                            height = height && height !== '0' ? height : this.settings.iframeHeight;
                            break;

                        default:
                            width  = width && width !== '0' ? width : this.settings.width;
                            height = height && height !== '0' ? height : this.settings.height;
                            break;
                    }

                    if (width && width !== '0') {
                        width = Helper.getStyleSize(width);

                        if (this.settings.dimensionsIncludeTitle) {
                            this.elements.container.style.width    = width;
                            this.elements.contentInner.style.width = '100%';
                        } else {
                            this.elements.contentInner.style.width = width;
                        }
                    }

                    if (height && height !== '0') {
                        height = Helper.getStyleSize(height, true);

                        if (this.settings.dimensionsIncludeTitle) {
                            this.elements.container.style.height    = height;
                            this.elements.contentInner.style.height = '100%';
                        } else {
                            this.elements.contentInner.style.height = height;
                        }
                    }
                };

                createElements();
                setPosition();
                setOrientation();
                setDimensions();

                this.modal.elements.slides.appendChild(this.element);

                resolve();
            });
        };

        const addEventListeners = () => {
            const action = ['hover', 'mouseenter'].includes(this.settings.mode) ? 'mouseenter' : 'click';
            this.link.addEventListener(action, (event) => {
                this.modal.open(this);
                event.preventDefault();
            });

            ['open', 'opened', 'load', 'loaded', 'close', 'closed'].forEach(state => {
                const action = Helper.getData(this.link, `on-${state}`);

                if ( ! action) {
                    return;
                }

                this.eventListeners[state] = new Function(action);
            });
        };

        await initSettings();
        await createElements();
        addEventListeners();
    };

    init();
}

Slide.prototype = {
    show: async function(effect = 'open', callbacks = []) {
        this.status = 'opening';

        if ( ! this.isLoaded) {
            await new Promise(async (resolve) => {
                await this.load();
                resolve();
            });
        }

        return new Promise(async (resolve) => {
            this.setModalStyling();

            const autoclose = Helper.getData(this.link, 'auto-close');

            if (autoclose) {
                let delay = parseInt(autoclose);
                if (delay < 1000) {
                    delay = 5000;
                }

                callbacks.push(() => {
                    this.setAutoClose(delay);
                });
            }

            callbacks.push(() => {
                this.status = 'open';
            });

            const effectName = this.settings[`${effect}Effect`] || effect;
            effect           = this.settings.cssEffects[effectName] && this.settings.cssEffects[effectName]
                ? this.settings.cssEffects[effectName].in
                : 'show';

            Helper.animate(this.element, effect, callbacks);
            resolve();
        });
    },

    setModalStyling: function() {
        Helper.setData(this.modal.element, 'theme', this.settings.theme);

        this.modal.element.className = '';

        const modal_class = Helper.getData(this.link, 'class');

        if (modal_class) {
            this.modal.element.classList.add(modal_class);
        }

        if (Helper.getData(this.link, 'legacy')) {
            this.modal.elements.main.appendChild(this.modal.elements.close);
            this.modal.elements.main.appendChild(this.elements.countdown);
        } else {
            this.modal.elements.closeBar.appendChild(this.modal.elements.close);
            this.elements.content.appendChild(this.elements.countdown);
        }

        this.resize(true);
    },

    setAutoClose: function(delay) {
        this.modal.setAutoClose(delay);
        this.startCountdown(delay);
    },

    hideCountdown: function() {
        this.elements.countdown.classList.add('hidden');
    },

    startCountdown: function(delay) {
        this.hideCountdown();

        if ( ! this.settings.showCountdown) {
            return;
        }

        this.elements.countdown.style.transition = 'none';
        this.elements.countdown.style.width      = '100%';
        this.elements.countdown.classList.remove('hidden');

        setTimeout(() => {
            // decrease width of countdown bar to zero based on delay in ms
            this.elements.countdown.style.transition = `width ${delay - 100}ms linear`;
            this.elements.countdown.style.width      = '0%';
        }, 100);
    },

    hide: async function(effect = 'close', callbacks = []) {
        this.status = 'closing';

        callbacks.push(() => {
            Helper.hide(this.element);
            this.status = 'closed';
        });

        const effectName = this.settings[`${effect}Effect`] || effect;
        effect           = this.settings.cssEffects[effectName] && this.settings.cssEffects[effectName]
            ? this.settings.cssEffects[effectName].out
            : 'hide';

        if (effect === 'hide') {
            this.hideCountdown();
        }

        this.restoreElementToParent();

        Helper.animate(this.element, effect, callbacks);
    },

    load: async function(force_resize = true) {
        const type = this.getType();

        if (this.isLoaded || type === 'none') {
            return;
        }

        this.isLoaded = true;

        this.elements.contentInner.innerHTML = '';

        await this.loadByType(type, force_resize);

        // resize on resize of window or orientation change
        let resize_timeout;
        window.addEventListener('resize', (event) => {
            clearTimeout(resize_timeout);
            resize_timeout = setTimeout(() => {
                this.resize();
            }, 100);
        });

        this.elements.contentInner.classList.remove('hidden');

        await this.resize(force_resize);
    },

    loadByType: async function(type, force_resize = true) {
        switch (type) {
            case 'image':
                return this.loadImage(force_resize);

            case 'audio':
                return this.loadAudio();

            case 'video':
                return this.loadVideo();

            case 'html':
                return this.loadHtml();

            case 'element':
                return this.loadElement();

            case 'iframe':
                return this.loadIframe();

            default:
                return true;
        }
    },

    getType: function() {
        if (this.type) {
            return this.type;
        }

        const getTypeString = () => {
            const url  = this.link.getAttribute('href');
            const hash = this.getHash();

            if (hash !== '') {
                return 'element';
            }

            if (url && Helper.getData(this.link, 'image')) {
                return 'image';
            }

            if (url && Helper.getData(this.link, 'video')) {
                return 'video';
            }

            if (Helper.getData(this.link, 'html')) {
                return 'html';
            }

            if (url.match(/\.(jpeg|jpg|jpe|gif|png|apn|webp|svg)(\?|$)/) !== null) {
                return 'image';
            }

            if (url.match(/(youtube\.com|youtube-nocookie\.com)\/watch\?v=([a-zA-Z0-9\-_]+)/) || url.match(/youtu\.be\/([a-zA-Z0-9\-_]+)/) || url.match(/(youtube\.com|youtube-nocookie\.com)\/embed\/([a-zA-Z0-9\-_]+)/)) {
                return 'video';
            }

            if (url.match(/vimeo\.com\/([0-9]*)/)) {
                return 'video';
            }

            if (url.match(/\.(mp4|ogg|webm|mov)(\?|$)/) !== null) {
                return 'video';
            }

            if (url.match(/\.(mp3|wav|wma|aac|ogg)(\?|$)/) !== null) {
                return 'audio';
            }

            if (url) {
                return 'iframe';
            }

            return 'none';
        };

        this.type = getTypeString();

        return this.type;
    },

    loadIframe: async function(preload = false) {
        return new Promise(async (resolve) => {
            if ( ! preload) {
                // We need to resolve early, so that the iframe gets loaded in view.
                // Some websites have issues with loading in an iframe, if they are not visible.
                resolve();
            }

            Helper.setData(this.element, 'type', 'iframe');

            this.elements.contentInner.classList.add('hidden');

            const iframe = await Helper.createIframe(this.link.href, null, () => {
                this.elements.contentInner.classList.remove('hidden');
                resolve();
            });

            this.insertElement(iframe);
        });
    },

    loadHtml: async function() {
        Helper.setData(this.element, 'type', 'inline');

        const element = Helper.createElementFromHtml(Helper.getData(this.link, 'html'));

        return this.insertElement(element);
    },

    loadImage: function(force_resize = true) {
        return new Promise(async (resolve) => {
            Helper.setData(this.element, 'type', 'image');
            Helper.setData(this.element, 'media', 'true');

            this.elements.contentInner.classList.add('hidden');

            // Create image element
            const image = new Image();
            image.src   = this.link.href;

            const srcset = Helper.getData(this.link, 'srcset') || '';
            if (srcset) {
                image.srcset = srcset;
            }

            image.addEventListener('load', async () => {
                await this.resize(force_resize);
                resolve();
            });

            await this.insertElement(image);
        });
    },

    getMaxWidthContainer: function() {
        // return inner width of this.modal.elements.slides (without margins/padding of slide element)
        const slidesWidth = this.modal.elements.slides.clientWidth || 0;
        if ( ! slidesWidth) {
            return 0;
        }

        const slideStyle        = window.getComputedStyle(this.element);
        const slideMarginLeft   = parseInt(slideStyle.marginLeft, 10) || 0;
        const slideMarginRight  = parseInt(slideStyle.marginRight, 10) || 0;
        const slidePaddingLeft  = parseInt(slideStyle.paddingLeft, 10) || 0;
        const slidePaddingRight = parseInt(slideStyle.paddingRight, 10) || 0;

        return slidesWidth - slideMarginLeft - slideMarginRight - slidePaddingLeft - slidePaddingRight;
    },

    getMaxHeightContainer: function() {
        // return inner height of this.modal.elements.slides (without margins/padding of slide element)
        const slidesHeight = this.modal.elements.slides.clientHeight || 0;
        if ( ! slidesHeight) {
            return 0;
        }

        const slideStyle         = window.getComputedStyle(this.element);
        const slideMarginTop     = parseInt(slideStyle.marginTop, 10) || 0;
        const slideMarginBottom  = parseInt(slideStyle.marginBottom, 10) || 0;
        const slidePaddingTop    = parseInt(slideStyle.paddingTop, 10) || 0;
        const slidePaddingBottom = parseInt(slideStyle.paddingBottom, 10) || 0;

        return slidesHeight - slideMarginTop - slideMarginBottom - slidePaddingTop - slidePaddingBottom;
    },

    getWidthContent: function() {
        // return inner width of this.modal.elements.slides (without margins)
        return this.elements.contentInner.offsetWidth || 0;
    },

    getHeightContent: function() {
        // return inner height of this.modal.elements.slides (without margins)
        return this.elements.contentInner.offsetHeight || 0;
    },

    getWidthContainer: function() {
        // return outer width of this.modal.elements.slides (including margins)
        const width       = this.elements.container.offsetWidth || 0;
        const marginLeft  = parseFloat(window.getComputedStyle(this.elements.container).marginLeft) || 0;
        const marginRight = parseFloat(window.getComputedStyle(this.elements.container).marginRight) || 0;

        return parseInt(width + marginLeft + marginRight);
    },

    getHeightContainer: function() {
        // return outer height of this.modal.elements.slides (including margins)
        const height       = this.elements.container.offsetHeight || 0;
        const marginTop    = parseFloat(window.getComputedStyle(this.elements.container).marginTop) || 0;
        const marginBottom = parseFloat(window.getComputedStyle(this.elements.container).marginBottom) || 0;

        return parseInt(height + marginTop + marginBottom);
    },

    resize: async function(force_resize = false) {
        if ( ! this.isLoaded || this.isResizing) {
            return;
        }

        if ( ! force_resize && this.status !== 'open') {
            return;
        }

        this.isResizing = true;

        const type = this.getType();

        switch (type) {
            case 'image':
                await this.resizeImage();
                break;

            case 'video':
                await this.resizeVideo();
                break;

            default:
                await this.resizeDefault();
                break;
        }

        this.isResizing = false;
    },

    resizeDefault: async function() {
        return new Promise((resolve) => {
            const has_hidden_main    = this.element.classList.contains('hidden');
            const has_hidden_content = this.elements.contentInner.classList.contains('hidden');

            this.element.classList.remove('hidden');
            this.elements.contentInner.classList.remove('hidden');

            Helper.storeOriginalStyles(this.element, ['width', 'height'], ['auto', 'auto']);
            Helper.storeOriginalStyles(this.elements.container, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);
            Helper.storeOriginalStyles(this.elements.contentInner, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);

            let maxWidth        = this.getMaxWidthContainer()
            let maxHeight       = this.getMaxHeightContainer();
            let containerWidth  = this.getWidthContainer();
            let containerHeight = this.getHeightContainer();

            let contentWidth  = this.getWidthContent();
            let contentHeight = this.getHeightContent();

            if (containerWidth && contentWidth) {
                while (containerWidth > maxWidth && contentWidth > 10) {
                    let decrease = containerWidth - maxWidth;
                    decrease     = Math.max(1, decrease);

                    contentWidth = contentWidth - decrease;

                    this.elements.contentInner.style.width = contentWidth + 'px';

                    maxWidth        = this.getMaxWidthContainer()
                    maxHeight       = this.getMaxHeightContainer();
                    containerWidth  = this.getWidthContainer();
                    containerHeight = this.getHeightContainer();
                }
            }

            if (containerHeight && contentHeight) {
                while (containerHeight > maxHeight && contentHeight > 10) {
                    let decrease = containerHeight - containerHeight;
                    decrease     = Math.max(1, decrease);

                    contentHeight = contentHeight - decrease;

                    this.elements.contentInner.style.height = contentHeight + 'px';

                    maxWidth        = this.getMaxWidthContainer()
                    maxHeight       = this.getMaxHeightContainer();
                    containerWidth  = this.getWidthContainer();
                    containerHeight = this.getHeightContainer();
                }
            }

            Helper.restoreOriginalStyles(this.element, ['maxWidth', 'maxHeight']);
            Helper.restoreOriginalStyles(this.elements.container, ['maxWidth', 'maxHeight']);

            if (has_hidden_main) {
                this.element.classList.add('hidden');
            }
            if (has_hidden_content) {
                this.elements.contentInner.classList.add('hidden');
            }

            resolve();
        });
    },

    resizeVideo: async function() {
        return new Promise((resolve) => {
            const child = this.elements.contentInner.firstElementChild;

            if ( ! child) {
                resolve();
                return;
            }

            child.style.visibility = 'hidden';

            const has_hidden_main    = this.element.classList.contains('hidden');
            const has_hidden_content = this.elements.contentInner.classList.contains('hidden');

            this.element.classList.remove('hidden');
            this.elements.contentInner.classList.remove('hidden');

            Helper.storeOriginalStyles(this.element, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);
            Helper.storeOriginalStyles(this.elements.container, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);
            Helper.storeOriginalStyles(this.elements.contentInner, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);

            this.element.style.maxWidth                = 'none';
            this.element.style.maxHeight               = 'none';
            this.elements.container.style.maxWidth     = 'none';
            this.elements.container.style.maxHeight    = 'none';
            this.elements.contentInner.style.maxWidth  = 'none';
            this.elements.contentInner.style.maxHeight = 'none';

            let maxWidth        = this.getMaxWidthContainer();
            let maxHeight       = this.getMaxHeightContainer();
            let containerWidth  = this.getWidthContainer();
            let containerHeight = this.getHeightContainer();

            let contentWidth  = this.getWidthContent();
            let contentHeight = this.getHeightContent();
            const ratio       = contentWidth / contentHeight;

            while ((containerWidth > maxWidth || containerHeight > maxHeight) && containerWidth > 10) {
                let decrease = (containerWidth > maxWidth)
                    ? containerWidth - maxWidth
                    : Math.floor((containerHeight - maxHeight) * ratio);
                decrease     = Math.max(1, decrease);

                contentWidth = contentWidth - decrease;

                this.elements.contentInner.style.width  = contentWidth + 'px';
                this.elements.contentInner.style.height = Math.floor(contentWidth / ratio) + 'px';

                maxWidth        = this.getMaxWidthContainer();
                maxHeight       = this.getMaxHeightContainer();
                containerWidth  = this.getWidthContainer();
                containerHeight = this.getHeightContainer();
            }

            Helper.restoreOriginalStyles(this.element, ['maxWidth', 'maxHeight']);
            Helper.restoreOriginalStyles(this.elements.container, ['maxWidth', 'maxHeight']);
            Helper.restoreOriginalStyles(this.elements.contentInner, ['maxWidth', 'maxHeight']);

            child.style.visibility = 'visible';

            if (has_hidden_main) {
                this.element.classList.add('hidden');
            }
            if (has_hidden_content) {
                this.elements.contentInner.classList.add('hidden');
            }

            resolve();
        });
    },

    resizeImage: async function() {
        return new Promise((resolve) => {
            const child = this.elements.contentInner.firstElementChild;

            if ( ! child) {
                resolve();
                return;
            }

            const has_hidden_main    = this.element.classList.contains('hidden');
            const has_hidden_content = this.elements.contentInner.classList.contains('hidden');

            this.element.classList.remove('hidden');
            this.elements.contentInner.classList.remove('hidden');

            Helper.storeOriginalStyles(this.element, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);
            Helper.storeOriginalStyles(this.elements.container, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);
            Helper.storeOriginalStyles(this.elements.contentInner, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);
            Helper.storeOriginalStyles(child, ['width', 'height', 'maxWidth', 'maxHeight'], ['auto', 'auto', 'none', 'none']);

            this.element.style.maxWidth                = 'none';
            this.element.style.maxHeight               = 'none';
            this.elements.container.style.maxWidth     = 'none';
            this.elements.container.style.maxHeight    = 'none';
            this.elements.contentInner.style.maxWidth  = 'none';
            this.elements.contentInner.style.maxHeight = 'none';
            child.style.maxWidth                       = 'none';
            child.style.maxHeight                      = 'none';

            let childWidth  = child.offsetWidth;
            let childHeight = child.offsetHeight;
            const ratio     = childWidth / childHeight;

            child.style.height     = 'auto';
            child.style.visibility = 'hidden';

            let maxWidth        = this.getMaxWidthContainer()
            let maxHeight       = this.getMaxHeightContainer();
            let containerWidth  = this.getWidthContainer();
            let containerHeight = this.getHeightContainer();

            while ((containerWidth > maxWidth || containerHeight > maxHeight) && childWidth > 10) {
                let decrease = (containerWidth > maxWidth)
                    ? containerWidth - maxWidth
                    : Math.floor((containerHeight - maxHeight) * ratio);
                decrease     = Math.max(1, decrease);

                childWidth = childWidth - decrease;

                child.style.width = childWidth + 'px';

                maxWidth        = this.getMaxWidthContainer();
                maxHeight       = this.getMaxHeightContainer();
                containerWidth  = this.getWidthContainer();
                containerHeight = this.getHeightContainer();
            }

            const contentWidth = this.getWidthContent();

            if (childWidth < contentWidth) {
                this.element.style.width = childWidth + 'px';
            }

            Helper.restoreOriginalStyles(this.element, ['maxWidth', 'maxHeight']);
            Helper.restoreOriginalStyles(this.elements.container, ['maxWidth', 'maxHeight']);
            Helper.restoreOriginalStyles(this.elements.contentInner, ['maxWidth', 'maxHeight']);
            Helper.restoreOriginalStyles(child, ['maxWidth', 'maxHeight']);

            child.style.visibility = 'visible';

            if (has_hidden_main) {
                this.element.classList.add('hidden');
            }
            if (has_hidden_content) {
                this.elements.contentInner.classList.add('hidden');
            }

            resolve();
        });
    },

    loadAudio: async function() {
        Helper.setData(this.element, 'type', 'audio');
        Helper.setData(this.element, 'media', 'true');

        // Create audio element
        const audio    = document.createElement('audio');
        audio.id       = 'audio-player';
        audio.controls = 'controls';
        audio.src      = this.link.href;
        audio.type     = 'audio/' + this.link.href.split('.').pop();
        audio.autoplay = true;

        return this.insertElement(audio);
    },

    loadVideo: async function() {
        Helper.setData(this.element, 'type', 'video');
        Helper.setData(this.element, 'media', 'true');

        return this.loadIframe(true);
    },

    loadElement: async function() {
        const element = Helper.getExistingElementFromHashString(this.link.getAttribute('href'));

        if ( ! element) {
            return;
        }

        if ( ! element.parent) {
            element.parent = element.parentNode;
        }

        Helper.setData(this.element, 'type', 'inline');

        return this.insertElement(element);
    },

    getHash: function() {
        const hash = this.link.getAttribute('href').split('#').pop();

        // Return if hash contains a slash
        if (hash.indexOf('/') > -1) {
            return '';
        }

        return hash.trim();
    },

    insertElement: async function(element) {
        if ( ! element) {
            return;
        }

        this.elements.contentInner.appendChild(element);
    },

    restoreElementToParent: async function() {
        if ( ! this.elements.contentInner.firstElementChild) {
            return;
        }

        const child = this.elements.contentInner.firstElementChild;

        if ( ! child || ! child.parent) {
            return;
        }

        this.insertElement(child.cloneNode(true));
        child.parent.appendChild(child);

        this.isLoaded = false;
    },
};
