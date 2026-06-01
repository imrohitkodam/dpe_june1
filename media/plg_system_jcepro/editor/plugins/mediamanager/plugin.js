/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, extend = tinymce.extend, DOM = tinymce.DOM, Event = tinymce.dom.Event, htmlSchema = new tinymce.html.Schema(), DomParser = tinymce.html.DomParser, Uuid = tinymce.util.Uuid;
    function isMedia(node) {
        return node && DOM.getParent(node, "[data-mce-object]");
    }
    var mediaProviders = {
        youtube: /youtu(\.)?be(.+)?\/(.+)/,
        vimeo: /vimeo(.+)?\/(.+)/,
        dailymotion: /dai\.?ly(motion)?(\.com)?/,
        scribd: /scribd\.com\/(.+)/,
        slideshare: /slideshare\.net\/(.+)\/(.+)/,
        soundcloud: /soundcloud\.com\/(.+)/,
        spotify: /spotify\.com\/(.+)/,
        ted: /ted\.com\/talks\/(.+)/,
        twitch: /twitch\.tv\/(.+)/,
        facebook: /facebook\.com\/(.+)/,
        instagram: /instagram\.com\/(.+)/,
        threads: /threads\.net\/(.+)/,
        video: /\.(mp4|ogv|ogg|webm)$/,
        audio: /\.(mp3|ogg|webm|wav|m4a|aiff)$/,
        calendly: /calendly\.com\/(.+)/
    }, Calendly = {
        toIframe: function(ed, html) {
            var div, items, mediaApi = ed.plugins.media;
            return html && -1 !== html.indexOf("calendly-inline-widget") && (div = ed.dom.create("div", {}, html), 
            (items = ed.dom.select("div.calendly-inline-widget", div)).length && each(items, function(item) {
                var url = item.getAttribute("data-url");
                if (url && 0 === url.indexOf("https://calendly.com/")) {
                    var props = getMediaProps(ed, {
                        src: url
                    }, "calendly"), elm = ed.dom.create("iframe", {}), props = (each(props.calendly, function(val, key) {
                        if (ed.schema.isValid("iframe", key)) {
                            if ("sandbox" == key) return !0;
                            ed.dom.setAttrib(elm, key, val);
                        }
                    }), ed.dom.setAttrib(elm, "width", ed.dom.getStyle(item, "width") || props.calendly.width || "100%"), 
                    ed.dom.setAttrib(elm, "height", ed.dom.getStyle(item, "height") || props.calendly.height || "700"), 
                    ed.dom.select('script[src="https://assets.calendly.com/assets/external/widget.js"]', div));
                    if (each(props, function(tag) {
                        ed.dom.remove(tag);
                    }), ed.dom.replace(elm, item), !1 === mediaApi.isSupportedMedia(url)) return ed.dom.remove(elm, !0), 
                    !0;
                }
            }), html = (html = div.innerHTML).replace("\x3c!-- Calendly inline widget begin --\x3e", "").replace("\x3c!-- Calendly inline widget end --\x3e", "")), 
            html;
        },
        fromIframe: function(ed, html) {
            var div;
            return html && -1 !== html.indexOf("https://calendly.com/") && ((div = ed.dom.create("div", {}, html)).querySelectorAll('iframe[src^="https://calendly.com/"]').forEach(function(item) {
                var src = item.getAttribute("src"), parent = ed.dom.getParent(item, "p"), parent = (parent && 1 === parent.childNodes.length && ed.dom.remove(parent, !0), 
                {
                    "min-width": "320px",
                    width: ed.dom.getStyle(item, "width") || "",
                    height: ed.dom.getStyle(item, "height") || "700px"
                }), parent = ed.dom.serializeStyle(parent), src = ed.dom.create("div", {}, `<!-- Calendly inline widget begin -->
<div class="calendly-inline-widget" data-url="${src}" style="${parent}"></div>
<script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>
<!-- Calendly inline widget end -->`);
                ed.dom.replace(src, item), ed.dom.remove(src, !0);
            }), html = div.innerHTML), html;
        }
    };
    function getAttribsFromHtml(html) {
        var attribs = {
            html: ""
        }, parser = new DomParser({
            verify_html: !0,
            validate: !0,
            forced_root_block: !1,
            invalid_elements: "script,noscript,svg",
            purify_html: !1
        }, htmlSchema);
        return parser.addNodeFilter("iframe", function(nodes) {
            for (var node, i = nodes.length; i--; ) {
                node = nodes[i], each(node.attributes.map, function(val, name) {
                    attribs[name] = val;
                });
                var child = node.firstChild;
                if (child && htmlSchema.isValid(child.name)) for (;child.value && (attribs.html += child.value), 
                child = child.next; );
            }
        }), parser.parse(html), attribs;
    }
    function stripQuery(value) {
        return value = -1 !== value.indexOf("?") ? value.substring(0, value.indexOf("?")) : value;
    }
    function insertMedia(ed, data, provider) {
        var attribs, node = ed.selection.getNode(), mediaApi = ed.plugins.media;
        data.html && (data.html = Calendly.toIframe(ed, data.html), attribs = getAttribsFromHtml(data.html), 
        data.width && delete attribs.width, data.height && delete attribs.height, 
        extend(data, attribs)), data.query && (-1 !== data.src.indexOf("?") ? data.src += "&" + data.query : data.src += "?" + data.query, 
        delete data.query), data.src && (isMedia(node) ? (data = getMediaData(ed, data), 
        mediaApi.updateMedia(data.attributes)) : (attribs = getMediaHtml(ed, data), 
        ed.execCommand("insertMediaHtml", !1, attribs)), ed.undoManager.add(), ed.nodeChanged());
    }
    function getMediaData(ed, value) {
        var invalidAttributes = [], data = {
            nodeName: "iframe",
            attributes: {},
            html: ""
        }, src = stripQuery((value = "string" == typeof value ? {
            src: value
        } : value).src), boolAttrs = (/\.(mp4|m4v|ogg|webm|ogv)$/.test(src) ? data.nodeName = "video" : /\.(mp3|m4a|oga)$/.test(src) && (data.nodeName = "audio"), 
        ed.schema.getBoolAttrs());
        function isValueAttribute(name) {
            return !(!ed.schema.isValid(data.nodeName, name) && -1 === name.indexOf("-"));
        }
        return each(value, function(val, name) {
            return -1 !== tinymce.inArray(invalidAttributes, name) || "" == val && !boolAttrs[name] || (val.html ? (data.html = val.html, 
            !0) : "attributes" == name ? (each(val, function(attrValue, attrName) {
                isValueAttribute(attrName) && (data.attributes[attrName] = attrValue);
            }), !0) : void (isValueAttribute(name) && (data.attributes[name] = val)));
        }), data;
    }
    function getMediaHtml(ed, value) {
        value = getMediaData(ed, value);
        return ed.dom.createHTML(value.nodeName, value.attributes, value.html);
    }
    function isSupportedMedia(ed, url) {
        var mediaApi, match;
        return !(!url || "string" != typeof url || (mediaApi = ed.plugins.media, 
        match = !1, ed = ed.getParam("mediamanager", {}), url = stripQuery(url), 
        each(mediaProviders, function(rx, key) {
            rx.test(url) && mediaApi.isSupportedMedia(url) && (match = key);
        }), match || (/\.(mov|qt|mpg|mpeg)$/i.test(url) && (match = "quicktime"), 
        /\.(swf|dcr)$/i.test(url) && (match = "flash"), /\.divx$/i.test(url) && (match = "divx")), 
        match && !1 === ed.quickmedia[match])) && (ed.custom_embed && each(ed.custom_embed, function(values, name) {
            values = values.expression || name;
            if (new RegExp(values).test(url)) return match = name, !0;
        }), match);
    }
    function getMediaProps(ed, data, provider) {
        var mediaApi = ed.plugins.media, value = data.src || "", defaultValues = {}, mediaApi = (defaultValues[provider] = mediaApi.getMediaProps(data, provider), 
        ed.getParam("mediamanager", {})), attribs = (mediaApi.custom_embed && mediaApi.custom_embed[provider] && (defaultValues[provider] = mediaApi.custom_embed[provider] || {}, 
        delete defaultValues[provider].expression, defaultValues[provider] = extend({
            src: value,
            width: 560,
            height: 315,
            frameborder: 0
        }, defaultValues[provider])), data.attributes || {}), args = {}, attribsMap = [ "title", "id", "class", "style", "width", "height" ];
        return each(attribs, function(val, key) {
            key === provider ? args = extend(args, attribs[provider]) : -1 !== tinymce.inArray(attribsMap, key) && (args[key] = val);
        }), defaultValues[provider] = extend(defaultValues[provider], args), defaultValues;
    }
    function getEmbedData(ed, data, provider) {
        var defaultProviderData = data[provider] || "";
        return new Promise(function(resolve, reject) {
            if (!defaultProviderData) return reject();
            var video, value;
            if ("video" === provider) (video = document.createElement("video")).onloadedmetadata = function() {
                data.video.width || data.video.height || tinymce.extend(data.video, {
                    width: video.videoWidth,
                    height: video.videoHeight
                }), video = null, tinymce.is(data.video.controls) || (data.video.controls = "controls"), 
                data.video.width || (data.video.width = 640), data.video.height || (data.video.height = 480), 
                resolve(data.video);
            }, video.onerror = function() {
                video = null, resolve(data.video);
            }, video.src = ed.documentBaseURI.toAbsolute(data.video.src); else {
                if ("audio" !== provider) return defaultProviderData.oembed ? (value = defaultProviderData.url || defaultProviderData.src, 
                value = {
                    id: Uuid.uuid("wf_"),
                    method: "getEmbedData",
                    params: [ provider, encodeURIComponent(value), "" ]
                }, void tinymce.util.XHR.send({
                    url: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.rpc&plugin=mediamanager&" + ed.settings.query,
                    data: "json=" + JSON.stringify(value),
                    content_type: "application/x-www-form-urlencoded",
                    success: function(response) {
                        var data = "";
                        try {
                            data = JSON.parse(response);
                        } catch (e) {
                            return resolve(defaultProviderData);
                        }
                        if (!data.result) return resolve(defaultProviderData);
                        if (data.result.error) return reject(data.result.error);
                        response = tinymce.is(data.result, "object") ? data.result : {};
                        if ("string" == typeof data.result) try {
                            response = JSON.parse(data.result);
                        } catch (e) {
                            return resolve(defaultProviderData);
                        }
                        !response.src && response.url && (response.src = response.url), 
                        data = tinymce.extend(defaultProviderData, response), resolve(data);
                    },
                    error: function(err, xhr) {
                        return resolve(defaultProviderData);
                    }
                })) : resolve(defaultProviderData);
                tinymce.is(data.audio.controls) || (data.audio.controls = "controls"), 
                resolve(data.audio);
            }
        });
    }
    function getDataAndInsert(ed, data) {
        return new Promise(function(resolve, reject) {
            if (data.src) {
                var provider = isSupportedMedia(ed, data.src);
                if (!provider) return ed.windowManager.alert({
                    title: ed.getLang("mediamanager.error", "Media Manager Error"),
                    text: ed.getLang("mediamanager.url_unsupported", "This URL is not currently supported")
                });
                ed.setProgressState(!0);
                var props = getMediaProps(ed, data, provider);
                props[provider].url = data.src, getEmbedData(ed, props, provider).then(function(args) {
                    ed.setProgressState(!1), insertMedia(ed, args), resolve();
                }, function(msg) {
                    ed.setProgressState(!1), msg && ed.windowManager.alert({
                        title: ed.getLang("mediamanager.insert_error", "Media Manager Insert Error"),
                        text: msg
                    }), reject();
                });
            }
        });
    }
    tinymce.PluginManager.add("mediamanager", function(ed, url) {
        var self = this;
        function isMediaElm(n) {
            if (n = ed.dom.getParent(n, "[data-mce-object]")) {
                n = ed.plugins.media.getMediaData();
                if (n.src) return !1 !== isSupportedMedia(ed, n.src);
            }
        }
        function isPopup(n) {
            return !!ed.dom.is(n, "a.jcepopup") && (/(flash|quicktime|divx|video|audio)/.test(n.type) || isSupportedMedia(ed, n.href));
        }
        ed.addCommand("mceMedia", function() {
            var se = ed.selection, n = se.getNode();
            isPopup(n) && se.select(n), ed.windowManager.open({
                file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=mediamanager",
                size: "mce-modal-portrait-full"
            }, {
                plugin_url: url
            });
        }), ed.onNodeChange.add(function(ed, cm, n, collapsed) {
            var node, nonEditClass, state = !1, disabled = !0;
            node = n, nonEditClass = tinymce.settings.noneditable_noneditable_class || "mceNonEditable", 
            (node.attr ? node.hasClass(nonEditClass) : DOM.hasClass(node, nonEditClass)) || (disabled = !1), 
            isMediaElm(n) && (state = !0), cm.setDisabled("mediamanager", disabled && !collapsed), 
            cm.setActive("mediamanager", state || isPopup(n));
        }), ed.onPreInit.add(function() {
            var cm, form, urlCtrl, args, stylesListCtrl, attribs, mediaApi = ed.plugins.media, params = ed.getParam("mediamanager", {}), isMobile = (ed.onBeforeSetContent.addToTop(function(ed, o) {
                o.content = Calendly.toIframe(ed, o.content);
            }), ed.onGetContent.add(function(ed, o) {
                o.get && (o.content = Calendly.fromIframe(ed, o.content));
            }), window.matchMedia("(max-width: 600px)").matches);
            !0 === (!0 === params.basic_dialog || isMobile) && (cm = ed.controlManager, 
            form = cm.createForm("media_form"), !(args = {
                label: ed.getLang("dlg.url", "URL"),
                name: "src",
                clear: !0
            }) !== params.basic_dialog_filebrowser && (params.basic_dialog_filebrowser || isMobile) && tinymce.extend(args, {
                picker: !0,
                picker_label: "browse",
                picker_icon: "media",
                onpick: function() {
                    ed.execCommand("mceFileBrowser", !0, {
                        caller: "mediamanager",
                        callback: function(selected, data) {
                            data = data[0].url;
                            urlCtrl.value(data), window.setTimeout(function() {
                                urlCtrl.focus();
                            }, 10);
                        },
                        filter: params.filetypes.join(","),
                        value: urlCtrl.value()
                    });
                }
            }), params.upload && extend(args, {
                upload_label: "upload.label",
                upload_accept: params.upload.filetypes,
                upload: function(e, file) {
                    if (file && file.name) {
                        var url = self.getUploadURL(file);
                        if (!url) return ed.windowManager.alert({
                            text: ed.getLang("upload.file_extension_error", "File type not supported"),
                            title: ed.getLang("upload.error", "Upload Error")
                        }), !1;
                        urlCtrl.setLoading(!0), extend(file, {
                            filename: file.name.replace(/[\+\\\/\?\#%&<>"\'=\[\]\{\},;@\^\(\)\xa3\u20ac$~]/g, ""),
                            upload_url: url
                        }), ed.plugins.upload.upload(file, function(response) {
                            urlCtrl.setLoading(!1);
                            response = response.files || [], response = response.length ? response[0] : {};
                            if (response.file) return urlCtrl.value(response.file), 
                            !0;
                            ed.windowManager.alert({
                                text: "File upload failed!",
                                title: ed.getLang("upload.error", "Upload Error")
                            });
                        }, function(message) {
                            ed.windowManager.alert({
                                text: message,
                                title: ed.getLang("upload.error", "Upload Error")
                            }), urlCtrl.setLoading(!1);
                        });
                    }
                }
            }), urlCtrl = cm.createUrlBox("media_url", args), form.add(urlCtrl), 
            stylesListCtrl = cm.createStylesBox("media_class", {
                label: ed.getLang("media.class", "Classes"),
                onselect: function() {},
                name: "classes",
                styles: params.custom_classes || []
            }), form.add(stylesListCtrl), attribs = {
                src: ""
            }, ed.addCommand("mceMedia", function() {
                ed.windowManager.open({
                    title: ed.getLang("mediamanager.desc", ""),
                    items: [ form ],
                    size: "mce-modal-landscape-small",
                    open: function() {
                        var label = ed.getLang("insert", "Insert"), node = ed.selection.getNode(), data = {
                            src: ""
                        }, classes = params.attributes.classes || "";
                        classes.trim().split(" ").filter(function(cls) {
                            return "" !== cls.trim();
                        }), isMedia(node) && (data = mediaApi.getMediaData(), each([ "width", "height", "style", "class", "id", "title" ], function(name) {
                            var val = ed.dom.getAttrib(node, name);
                            "" !== val && ("class" === name && (val = val.replace(/mce-[\w\-]+/g, "").replace(/\s+/g, " ").trim().split(" ").filter(function(cls) {
                                return "" !== cls.trim();
                            }), classes = val), attribs[name] = val), "width" !== name && "height" !== name || (val = ed.dom.getStyle(node, name) || ed.dom.getAttrib(node, name), 
                            attribs[name] = parseInt(val, 10));
                        })), isPopup(node) && (data.src = ed.dom.getAttrib(node, "href")), 
                        data.src && (label = ed.getLang("update", "Update")), urlCtrl.value(data.src), 
                        stylesListCtrl.value(classes), window.setTimeout(function() {
                            urlCtrl.focus();
                        }, 10), DOM.setHTML(this.id + "_insert", label);
                    },
                    buttons: [ {
                        title: ed.getLang("cancel", "Cancel"),
                        id: "cancel"
                    }, {
                        title: ed.getLang("insert", "Insert"),
                        id: "insert",
                        onsubmit: function(e) {
                            var data = form.submit(), node = ed.selection.getNode();
                            return Event.cancel(e), !!data.src && (isMedia(node) ? mediaApi.updateMedia(data) : isPopup(node) ? ed.dom.setAttrib(node, "href", data.src) : !mediaApi.isMediaObject(node) && (attribs = tinymce.extend(params.attributes || {}, attribs), 
                            data = tinymce.extend(data, {
                                attributes: attribs
                            }), void getDataAndInsert(ed, data).then(function() {})));
                        },
                        classes: "primary",
                        scope: self
                    } ]
                });
            }));
        }), ed.onInit.add(function() {
            var params = ed.getParam("mediamanager", {});
            ed && ed.plugins.contextmenu && ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                m.add({
                    title: "mediamanager.desc",
                    icon: "mediamanager",
                    cmd: "mceMedia"
                });
            }), !1 !== params.quickmedia && ed.plugins.clipboard && (ed.onGetClipboardContent.add(function(ed, content, plainText) {
                var provider, data, html;
                plainText || (plainText = content["text/plain"] || "") && (plainText = new RegExp("^((http|https)://[-!#$%&'*+\\./0-9=?A-Z^_`a-z{|}~;\\[\\]]+\\.[-!#$%&'*+\\./0-9=?A-Z^_`a-z{|}~;\\[\\]]+(/.*)?)$").exec(plainText)) && (provider = isSupportedMedia(ed, plainText = tinymce.trim(plainText[0]))) && (content["text/plain"] = "", 
                data = getMediaProps(ed, {
                    src: plainText
                }, provider), html = "\x3c!-- x-tinymce/html --\x3e", html += getMediaHtml(ed, tinymce.extend({
                    "data-mce-clipboard-media": plainText
                }, data[provider])), content["text/html"] = content["x-tinymce/html"] = html);
            }), ed.onPasteBeforeInsert.add(function(ed, o) {
                var node = ed.dom.create("div", 0, o.content), media = ed.dom.select("[data-mce-clipboard-media]", node);
                media.length && (each(media, function(el) {
                    var value = el.getAttribute("data-mce-clipboard-media"), provider = isSupportedMedia(ed, value);
                    provider && (ed.setProgressState(!0), el = self.getAttributes(params.attributes || {}), 
                    (el = getMediaProps(ed, {
                        src: value,
                        attributes: el
                    }, provider))[provider].url = value, getEmbedData(ed, el, provider).then(function(data) {
                        each(ed.dom.select("[data-mce-clipboard-media]", ed.getBody()), function(el) {
                            el.getAttribute("data-mce-clipboard-media") === value && (ed.selection.select(el), 
                            el.removeAttribute("data-mce-clipboard-media"), insertMedia(ed, data, provider));
                        }), ed.setProgressState(!1);
                    }));
                }), o.content = ed.serializer.serialize(node, {
                    getInner: 1,
                    forced_root_block: ""
                }), ed.dom.remove(node));
            }));
        }), this.createControl = function(n, cm) {
            var params, html, self = this, mediaApi = ed.plugins.media;
            return "mediamanager" !== n ? null : !1 === (params = ed.getParam("mediamanager", {})).quickmedia || !0 === params.basic_dialog ? cm.createButton("mediamanager", {
                title: "mediamanager.desc",
                cmd: "mceMedia"
            }) : (html = '<div class="mceToolbarRow">   <div class="mceToolbarItem">       <input type="text" id="' + ed.id + '_media_input" class="mceTextBox" aria-label="' + ed.getLang("mediamanager.src", "URL") + '" placeholder="' + ed.getLang("mediamanager.src", "URL") + '" />   </div></div>', 
            !1 !== params.quickembed && (html += '<div class="mceToolbarRow">   <div class="mceToolbarItem">       <textarea id="' + ed.id + '_media_embed" rows="5" class="mceTextBox" aria-label="' + ed.getLang("mediamanager.embed", "Embed Code") + '" placeholder="' + ed.getLang("mediamanager.embed", "Embed Code") + '"></textarea>   </div></div>'), 
            html += '<div class="mceToolbarFooter">   <div class="mceToolbarItem">       <button type="button" id="' + ed.id + '_media_submit" class="mceButton mceButtonPrimary">' + ed.getLang("insert", "Insert") + "</button>   </div></div>", 
            (n = cm.createSplitButton("mediamanager", {
                title: "mediamanager.desc",
                cmd: "mceMedia",
                max_width: 264,
                onselect: function(node) {
                    "" !== node.value && (node && isMedia(node) ? mediaApi.updateMedia({
                        src: node.value
                    }) : getDataAndInsert(ed, {
                        src: node.value
                    }).then(function() {}));
                }
            })).onRenderMenu.add(function(c, m) {
                var item = m.add({
                    onclick: function(e) {
                        e.preventDefault(), item.setSelected(!1);
                        e = ed.dom.getParent(e.target, ".mceButton");
                        if (e && !e.disabled) {
                            var e = ed.selection.getNode(), urlValue = DOM.getValue(ed.id + "_media_input"), embedValue = DOM.getValue(ed.id + "_media_embed");
                            if ("" !== embedValue) insertMedia(ed, {
                                html: embedValue
                            }); else if ("" !== urlValue) if (isMedia(e)) mediaApi.updateMedia({
                                src: urlValue
                            }); else {
                                if (mediaApi.isMediaObject(e)) return !1;
                                getDataAndInsert(ed, {
                                    src: urlValue,
                                    attributes: self.getAttributes(params.attributes || {})
                                }).then(function() {});
                            }
                            m.hideMenu();
                        }
                    },
                    html: html
                });
                !1 !== params.quickembed && item.onRender.add(function() {
                    DOM.bind(DOM.get(ed.id + "_media_input"), "change", function(e) {
                        "" !== e.target.value && (e = mediaApi.getMediaHtml({
                            src: e.target.value
                        }), DOM.setValue(ed.id + "_media_embed", e));
                    }), DOM.bind(DOM.get(ed.id + "_media_embed"), "change", function(e) {
                        "" !== e.target.value && (e = getAttribsFromHtml(e.target.value)).src && DOM.setValue(ed.id + "_media_input", e.src);
                    });
                }), m.onShowMenu.add(function() {
                    var data = {}, node = ed.selection.getNode(), html = "";
                    node && isMedia(node) && (data = mediaApi.getMediaData(), html = mediaApi.getMediaHtml(data)), 
                    window.setTimeout(function() {
                        DOM.get(ed.id + "_media_input").focus();
                    }, 10), DOM.setValue(ed.id + "_media_input", data.src || ""), 
                    DOM.setValue(ed.id + "_media_embed", html);
                }), m.onHideMenu.add(function() {
                    DOM.setValue(ed.id + "_media_input", ""), DOM.setValue(ed.id + "_media_embed", "");
                });
            }), n);
        }, this.getAttributes = function(data) {
            return data.style && tinymce.is(data.style, "string") && (data.style = ed.dom.parseStyle(data.style)), 
            data.styles && tinymce.is(data.styles, "object") && (data.style = extend(data.styles, data.style || {})), 
            data.style && (data.style = ed.dom.serializeStyle(data.style)), data;
        }, this.insertUploadedFile = function(o) {
            var data = this.getUploadConfig();
            if (data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(o.name)) return data = this.getAttributes(o.attributes || {}), 
            getDataAndInsert(ed, {
                src: o.file,
                attributes: data
            }).then(function() {}), !0;
            return !1;
        }, this.getUploadURL = function(file) {
            var data = this.getUploadConfig();
            return !!(data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(file.name)) && ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=mediamanager";
        }, this.getUploadConfig = function() {
            return ed.getParam("mediamanager", {}).upload || {};
        }, this.isSupportedMedia = function(url) {
            return isSupportedMedia(ed, url);
        }, this.getMediaProps = function(data, provider) {
            return getMediaProps(ed, data, provider);
        };
    });
}();