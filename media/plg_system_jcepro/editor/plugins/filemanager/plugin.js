/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, extend = tinymce.extend, DOM = tinymce.DOM, Event = tinymce.dom.Event, htmlSchema = new tinymce.html.Schema(), DomParser = tinymce.html.DomParser;
    function isMedia(node) {
        return node && DOM.getParent(node, "[data-mce-object]");
    }
    function isAnchor(elm) {
        return elm && DOM.getParent(elm, "a[href]");
    }
    function collectNodesInRange(rng, predicate) {
        if (rng.collapsed) return [];
        for (var rng = rng.cloneContents(), walker = new tinymce.dom.TreeWalker(rng.firstChild, rng), elements = [], nodes = [], current = rng.firstChild; (predicate(current) ? elements : nodes).push(current), 
        current = walker.next(); );
        return nodes.length && function(nodes) {
            for (var hasTextNodes = !1, hasElementNodes = !1, i = 0; i < nodes.length; i++) {
                var node = nodes[i];
                if (3 === node.nodeType ? hasTextNodes = !0 : 1 === node.nodeType && "A" != node.tagName && (hasElementNodes = !0), 
                hasTextNodes && hasElementNodes) return 1;
            }
        }(nodes) ? nodes : elements;
    }
    function getAnchorText(selection, anchorElm) {
        return (anchorElm ? anchorElm.innerText || anchorElm.textContent : selection.getContent({
            format: "text"
        })).replace(/\uFEFF/g, "");
    }
    function updateTextContent(elm, text) {
        tinymce.each(elm.childNodes, function(node) {
            3 == node.nodeType && "" !== node.nodeValue.trim() && (node.textContent = text);
        });
    }
    function insertMedia(ed, data) {
        var attribs, node = ed.selection.getNode(), mediaApi = ed.plugins.media;
        data.html && (attribs = function(html) {
            var attribs = {
                html: ""
            }, parser = new DomParser({
                verify_html: !0,
                validate: !0,
                forced_root_block: !1,
                invalid_elements: "script,noscript,svg"
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
        }(data.html), data.width && delete attribs.width, data.height && delete attribs.height, 
        extend(data, attribs)), data.query && (-1 !== data.src.indexOf("?") ? data.src += "&" + data.query : data.src += "?" + data.query), 
        isMedia(node) ? mediaApi.updateMedia(data) : (attribs = mediaApi.getMediaHtml(data), 
        ed.execCommand("insertMediaHtml", !1, attribs)), ed.undoManager.add(), ed.nodeChanged();
    }
    var openwith = {
        googledocs: {
            supported: [ "doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "pages", "ai", "psd", "tiff", "dxf", "svg", "ps", "ttf", "xps", "rar" ],
            link: "https://docs.google.com/viewer?url=",
            embed: "https://docs.google.com/viewer?embedded=true&url="
        },
        officeapps: {
            supported: [ "doc", "docx", "xls", "xlsx", "ppt", "pptx" ],
            link: "https://view.officeapps.live.com/op/view.aspx?src=",
            embed: "https://view.officeapps.live.com/op/embed.aspx?src="
        }
    }, embedMimes = {
        doc: "application/msword",
        xls: "application/vnd.ms-excel",
        ppt: "application/vnd.ms-powerpoint",
        dot: "application/msword",
        pps: "application/vnd.ms-powerpoint",
        docx: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        dotx: "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
        pptx: "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        xlsx: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        xlsm: "application/vnd.ms-excel.sheet.macroEnabled.12",
        ppsx: "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
        sldx: "application/vnd.openxmlformats-officedocument.presentationml.slide",
        potx: "application/vnd.openxmlformats-officedocument.presentationml.template",
        xltx: "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
        odt: "application/vnd.oasis.opendocument.text",
        odg: "application/vnd.oasis.opendocument.graphics",
        odp: "application/vnd.oasis.opendocument.presentation",
        ods: "application/vnd.oasis.opendocument.spreadsheet",
        odf: "application/vnd.oasis.opendocument.formula",
        txt: "text/plain",
        rtf: "application/rtf",
        md: "text/markdown",
        pdf: "application/pdf"
    }, embedInvalid = [ "gif", "jpeg", "jpg", "png", "apng", "webp", "avif", "zip", "tar", "gz", "avi", "wmv", "wm", "asf", "asx", "wmx", "wvx", "mov", "qt", "mpg", "mpeg", "swf", "dcr", "rm", "ra", "ram", "divx", "mp4", "ogv", "ogg", "webm", "flv", "f4v", "mp3", "ogg", "wav", "m4a", "xap", "aiff" ];
    tinymce.PluginManager.add("filemanager", function(ed, url) {
        function isFile(node) {
            return !!isMedia(node) || !!isAnchor(node) && (node = ed.dom.getParent(node, "a[href]"), 
            DOM.hasClass(node, "wf_file") || DOM.hasClass(node, "jce_file"));
        }
        ed.addCommand("mceFileManager", function() {
            ed.windowManager.open({
                file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=filemanager",
                size: "mce-modal-portrait-full"
            }, {
                plugin_url: url
            });
        }), this.editor = ed, this.url = url, ed.addButton("filemanager", {
            title: "filemanager.desc",
            cmd: "mceFileManager"
        }), ed.onNodeChange.add(function(ed, cm, n) {
            n = ed.dom.getParent(n, "a, .mce-object-iframe, .mce-object-object, .mce-object-embed") || n, 
            cm.setActive("filemanager", isFile(n));
        }), ed.onInit.add(function(ed) {
            ed.settings.compress.css || ed.dom.loadCSS(url + "/css/content.css"), 
            ed && ed.plugins.contextmenu && ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                m.add({
                    title: "filemanager.desc",
                    icon: "filemanager",
                    cmd: "mceFileManager"
                });
            });
        }), ed.onPreInit.add(function() {
            var cm, form, urlCtrl, formatCtrl, heightCtrl, sizeCtrl, args, attribs, mediaApi = ed.plugins.media, params = ed.getParam("filemanager", {});
            window.matchMedia("(max-width: 600px)").matches && (cm = ed.controlManager, 
            form = cm.createForm("filemanager_form"), args = {
                label: ed.getLang("dlg.url", "URL"),
                name: "src",
                clear: !0
            }, extend(args, {
                picker: !0,
                picker_label: "browse",
                picker_icon: "file",
                onpick: function() {
                    ed.execCommand("mceFileBrowser", !0, {
                        caller: "filemanager",
                        callback: function(selected, data) {
                            data = data[0].url;
                            urlCtrl.value(data), window.setTimeout(function() {
                                urlCtrl.focus();
                            }, 10);
                        },
                        value: urlCtrl.value()
                    });
                }
            }), urlCtrl = cm.createUrlBox("filemanager_url", args), form.add(urlCtrl), 
            formatCtrl = cm.createListBox("filemanager_format", {
                label: ed.getLang("filemanager.format", "Format"),
                name: "format",
                onselect: function(v) {
                    form.controls.forEach(function(ctrl) {
                        switch (ctrl.name) {
                          case "target":
                          case "text":
                            "link" == v ? form.show(ctrl) : form.hide(ctrl);
                            break;

                          case "size":
                            "embed" == v ? form.show(ctrl) : form.hide(ctrl);
                        }
                    });
                }
            }), args = {
                link: ed.getLang("filemanager.format_link", "Link"),
                embed: ed.getLang("filemanager.format_embed", "Embed")
            }, each(args, function(name, value) {
                formatCtrl.add(name, value);
            }), form.add(formatCtrl), formatCtrl.value("link"), textCtrl = cm.createTextBox("filemanager_text", {
                label: ed.getLang("filemanager.text", "Text"),
                name: "text",
                clear: !0,
                attributes: {
                    required: !0
                }
            }), form.add(textCtrl), titleCtrl = cm.createTextBox("filemanager_title", {
                label: ed.getLang("filemanager.title", "Title"),
                name: "title",
                clear: !0
            }), form.add(titleCtrl), targetCtrl = cm.createListBox("filemanager_target", {
                label: ed.getLang("filemanager.target", "Target"),
                name: "target",
                onselect: function(v) {}
            }), args = {
                "": "--",
                _blank: ed.getLang("filemanager.target_blank", "Open in new window"),
                _self: ed.getLang("filemanager.target_self", "Open in same window"),
                _parent: ed.getLang("filemanager.target_parent", "Open in parent window"),
                _top: ed.getLang("filemanager.target_top", "Open in top window"),
                download: ed.getLang("filemanager.target_download", "Download")
            }, each(args, function(name, value) {
                targetCtrl.add(name, value);
            }), form.add(targetCtrl), args = cm.createTextBox("filemanager_embed_width", {
                label: ed.getLang("filemanager_embed_width.width", "Width"),
                name: "width"
            }), heightCtrl = cm.createTextBox("filemanager_embed_height", {
                label: ed.getLang("filemanager_embed_width.height", "Height"),
                name: "height"
            }), (sizeCtrl = cm.createLayout("filemanager_embed_size", {
                label: ed.getLang("dlg.dimensions", "Dimensions"),
                class: "mceGridLayout mceSizes",
                name: "size"
            })).add(args), sizeCtrl.add(cm.createSeparator()), sizeCtrl.add(heightCtrl), 
            form.add(sizeCtrl), args = cm.createStylesBox("filemanager_classes", {
                label: ed.getLang("dlg.classes", "Classes"),
                onselect: function(v) {},
                name: "classes",
                styles: params.custom_classes || []
            }), form.add(args), attribs = {
                src: ""
            }, ed.addCommand("mceFileManager", function() {
                ed.windowManager.open({
                    title: ed.getLang("filemanager.desc", ""),
                    items: [ form ],
                    size: "mce-modal-landscape-small",
                    open: function() {
                        var state, anchorNode, label = ed.getLang("insert", "Insert"), node = ed.selection.getNode(), mediaApi = ed.plugins.media, data = extend({
                            src: "",
                            format: "link"
                        }, params), classes = params.attributes.classes || "";
                        isFile(node) && (isMedia(node) && (mediaApi = mediaApi.getMediaData(), 
                        (data = extend(data, mediaApi)).format = "embed", classes = data.class || "", 
                        data.classes = classes.replace(/mce-[\w\-]+/g, "").replace(/\s+/g, " ").trim().split(" ").filter(function(cls) {
                            return "" !== cls.trim();
                        }), each([ "width", "height" ], function(name) {
                            var ctrl = sizeCtrl.find(name);
                            ctrl && ctrl.value(data[name] || "");
                        })), state = function(ed) {
                            var inlineTextElements = ed.schema.getTextInlineElements();
                            return 0 === collectNodesInRange(ed.selection.getRng(), function(elm) {
                                return 1 === elm.nodeType && !isAnchor(elm) && !inlineTextElements[elm.nodeName.toLowerCase()];
                            }).length;
                        }(ed), (anchorNode = ed.dom.getParent(node, "a[href]")) && (ed.selection.select(anchorNode), 
                        data.src = ed.dom.getAttrib(anchorNode, "href"), each([ "title", "target" ], function(name) {
                            data[name] = ed.dom.getAttrib(anchorNode, name);
                        }), classes = ed.dom.getAttrib(anchorNode, "class")), label = ed.getLang("update", "Update")), 
                        data.text = getAnchorText(ed.selection, isAnchor(node) ? node : null) || "", 
                        each(form.controls, function(ctrl) {
                            var name = ctrl.name;
                            data[name] && ctrl.value(data[name]);
                        }), textCtrl.setDisabled(!state), window.setTimeout(function() {
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
                            if (Event.cancel(e), !data.src) return !1;
                            if (data.class = data.classes, delete data.classes, 
                            "embed" == data.format) {
                                if (each(sizeCtrl.controls, function(ctrl) {
                                    "width" != ctrl.name && "height" != ctrl.name || (data[ctrl.name] = parseInt(ctrl.value(), 10));
                                }), isMedia(node)) return mediaApi.updateMedia(data);
                                if (mediaApi.isMediaObject(node)) return !1;
                                attribs = tinymce.extend(params.attributes || {}, attribs), 
                                data = tinymce.extend(data, {
                                    attributes: attribs
                                }), insertMedia(ed, data);
                            }
                            "link" == data.format && (data.url = data.src, function(ed, data) {
                                var text, cls, node = ed.selection.getNode(), anchor = ed.dom.getParent(node, "a[href]"), params = ed.getParam("link", {});
                                (data = "string" == typeof data ? {
                                    url: data,
                                    text: data
                                } : data).url ? (text = getAnchorText(ed.selection, isAnchor(node) ? node : null) || "", 
                                node && node.hasAttribute("data-mce-item") && (text = "", 
                                ed.selection.select(node)), data.text = data.text || text || data.url, 
                                /^\s*www\./i.test(data.url) && (data.url = "https://" + data.url), 
                                text = {
                                    href: data.url,
                                    title: data.title || "",
                                    target: data.target || ""
                                }, text = tinymce.extend(text, params.attributes || {}), 
                                cls = [ "wf_file" ], text.class && (cls = cls.concat(text.class.split(" "))), 
                                data.class && (cls = cls.concat(data.class.split(" "))), 
                                ed.selection.isCollapsed() ? ed.execCommand("mceInsertContent", !1, ed.dom.createHTML("a", text, data.text)) : (text["data-mce-tmp"] = "1", 
                                ed.execCommand("mceInsertLink", !1, text), isAnchor(anchor) && updateTextContent(node, data.text), 
                                params = ed.dom.select('[data-mce-tmp="1"]'), each(params, function(elm) {
                                    elm.removeAttribute("data-mce-tmp"), updateTextContent(elm, data.text), 
                                    ed.dom.setAttrib(elm, "class", cls.join(" "));
                                })), ed.undoManager.add(), ed.nodeChanged()) : isAnchor(node) && ed.execCommand("unlink", !1);
                            }(ed, data));
                        },
                        classes: "primary",
                        scope: self
                    } ]
                });
            }));
        }), this.getAttributes = function(data) {
            var attr = {
                style: {}
            };
            return data.style && tinymce.is(data.style, "string") && (data.style = ed.dom.serializeStyle(ed.dom.parseStyle(data.style))), 
            tinymce.each([ "target", "id", "dir", "class", "charset", "style", "hreflang", "lang", "type", "rev", "rel", "tabindex", "accesskey" ], function(key) {
                tinymce.is(data[key]) && (attr[key] = data[key]);
            }), attr;
        }, this.insertUploadedFile = function(o) {
            var data = this.getUploadConfig();
            if (data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(o.file)) {
                var openWithConfig, embedTag, html, args = {
                    href: o.file,
                    title: o.title || o.name
                }, data = o.method || "link";
                if (o.openwith && (openWithConfig = openwith[o.openwith] || !1) && (new RegExp(".(" + openWithConfig.supported.join("|") + ")$", "i").test(o.file) ? (args.href = encodeURIComponent(decodeURIComponent(ed.documentBaseURI.toAbsolute(args.href, ed.settings.remove_script_host))), 
                args.href = openWithConfig[data] + args.href) : openWithConfig = !1), 
                "embed" == data && (data = args.href, !1 === new RegExp(".(" + embedInvalid.join("|") + ")$").test(data))) return data = (data = args.href).substring(data.length, data.lastIndexOf(".") + 1), 
                data = embedMimes[data] || "", embedTag = "object", args = extend(args, {
                    width: o.width || 640,
                    height: o.height || 480
                }), data ? args = extend(args, {
                    type: data,
                    data: args.href
                }) : (args = extend(args, {
                    seamless: "seamless",
                    src: args.href
                }), openWithConfig && (args = extend(args, {
                    sandbox: "allow-scripts allow-same-origin allow-popups allow-forms",
                    allow: "fullscreen"
                })), embedTag = "iframe"), delete args.href, html = ed.dom.createHTML(embedTag, args, ""), 
                ed.execCommand("mceInsertContent", !1, html, {
                    skip_undo: 1
                }), !0;
                html = [], o.features && each(o.features, function(n) {
                    n = ed.dom.createHTML(n.node, n.attribs || {}, n.html || "");
                    html.push(n);
                });
                var cls = [ "wf_file" ], data = this.getAttributes(o.attributes || {});
                return each(data, function(val, key) {
                    "class" == key && val ? cls = cls.concat(val.split(" ")) : args[key] = val;
                }), args.class = cls.join(" "), 1 === html.length && (html = [ o.name ]), 
                ed.dom.create("a", args, html.join(""));
            }
            return !1;
        }, this.getUploadURL = function(file) {
            var data = this.getUploadConfig();
            if (data && data.filetypes) {
                if (/\.(jpg|jpeg|png|tiff|bmp|gif|avif|webp)$/i.test(file.name) && (ed.plugins.imgmanager || ed.plugins.imgmanager_ext)) return !1;
                if (/\.(html|htm|txt|md)$/i.test(file.name) && ed.plugins.templatemanager) return !1;
                if (/\.(mp4|m4v|ogg|webm|ogv|mp3|oga)$/i.test(file.name) && ed.plugins.mediamanager) return !1;
                if (new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(file.name)) return ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=filemanager";
            }
            return !1;
        }, this.getUploadConfig = function() {
            return ed.getParam("filemanager", {}).upload || {};
        };
    });
}();