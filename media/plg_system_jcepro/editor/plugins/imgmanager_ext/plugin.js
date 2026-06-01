/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var DOM = tinymce.DOM, Event = tinymce.dom.Event, extend = tinymce.extend;
    function isMediaObject(node) {
        return node.getAttribute("data-mce-object") || node.getAttribute("data-mce-type");
    }
    function isImage(node) {
        return node && "IMG" === node.nodeName && !isMediaObject(node);
    }
    function insertImage(ed, args) {
        var node = ed.selection.getNode();
        isImage(node) ? ed.dom.setAttribs(node, {
            src: args.src,
            alt: args.alt || "",
            class: args.class || ""
        }) : (ed.execCommand("mceInsertContent", !1, '<img id="__mce_tmp" src="" />', {
            skip_undo: 1
        }), node = ed.dom.get("__mce_tmp"), ed.dom.setAttribs(node, args), ed.dom.setAttrib(node, "id", "")), 
        ed.selection.select(node), ed.undoManager.add(), ed.nodeChanged();
    }
    function validateImage(ed, value) {
        return new Promise(function(resolve, reject) {
            if (!value) return resolve();
            var img = new Image();
            img.onload = function() {
                resolve({
                    width: img.width,
                    height: img.height
                });
            }, img.onerror = function() {
                reject();
            }, img.src = ed.documentBaseURI.toAbsolute(value);
        });
    }
    tinymce.PluginManager.add("imgmanager_ext", function(ed, url) {
        var self = this;
        ed.addCommand("mceImageManagerExtended", function() {
            var n = ed.selection.getNode();
            "IMG" == n.nodeName && isMediaObject(n) || ed.windowManager.open({
                file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=imagepro",
                size: "mce-modal-portrait-full"
            }, {
                plugin_url: url
            });
        }), ed.addButton("imgmanager_ext", {
            title: "imgmanager_ext.desc",
            cmd: "mceImageManagerExtended"
        }), ed.onNodeChange.add(function(ed, cm, n, collapsed) {
            n = isImage(n);
            cm.setDisabled("imgmanager_ext", !n && !collapsed), cm.setActive("imgmanager_ext", n);
        }), ed.onPreInit.add(function() {
            ed.parser.addNodeFilter("img", function(nodes) {
                for (var i = nodes.length; i--; ) {
                    var node, stamp, src = (node = nodes[i]).attr("src");
                    src && -1 === src.indexOf("?") && /\.(jpg|jpeg|png|gif|webp|avif)$/.test(src) && (stamp = "?" + new Date().getTime(), 
                    src = ed.convertURL(src, "src", node.name), node.attr("src", src + stamp), 
                    node.attr("data-mce-src", src));
                }
            });
            var cm, form, urlCtrl, captionCtrl, descriptionCtrl, args, stylesListCtrl, params = ed.getParam("imgmanager_ext", {});
            window.matchMedia("(max-width: 600px)").matches && (cm = ed.controlManager, 
            form = cm.createForm("imgmanager_ext_form"), args = {
                label: ed.getLang("dlg.url", "URL"),
                name: "url",
                clear: !0
            }, extend(args, {
                picker: !0,
                picker_label: "browse",
                picker_icon: "imgmanager_ext",
                onpick: function() {
                    ed.execCommand("mceFileBrowser", !0, {
                        caller: "imagepro",
                        callback: function(selected, data) {
                            var src;
                            data.length && (src = data[0].url, data = data[0].title, 
                            urlCtrl.value(src), data = data.replace(/\.[^.]+$/i, ""), 
                            descriptionCtrl.value(data), window.setTimeout(function() {
                                urlCtrl.focus();
                            }, 10));
                        },
                        filter: params.filetypes || "images",
                        value: urlCtrl.value()
                    });
                }
            }), urlCtrl = cm.createUrlBox("imgmanager_ext_url", args), form.add(urlCtrl), 
            descriptionCtrl = cm.createTextBox("imgmanager_ext_description", {
                label: ed.getLang("dlg.description", "Description"),
                name: "alt",
                clear: !0
            }), form.add(descriptionCtrl), stylesListCtrl = cm.createStylesBox("imgmanager_ext_class", {
                label: ed.getLang("image.class", "Classes"),
                onselect: function() {},
                name: "classes",
                styles: params.custom_classes || []
            }), form.add(stylesListCtrl), captionCtrl = cm.createCheckBox("imgmanager_ext_caption", {
                label: ed.getLang("image.caption", "Caption"),
                name: "caption"
            }), form.add(captionCtrl), ed.addCommand("mceImageManagerExtended", function() {
                var node = ed.selection.getNode();
                "IMG" == node.nodeName && isMediaObject(node) || ed.windowManager.open({
                    title: ed.getLang("imgmanager.desc", "Image"),
                    items: [ form ],
                    size: "mce-modal-landscape-small",
                    open: function() {
                        var label = ed.getLang("insert", "Insert"), node = ed.selection.getNode(), src = "", alt = "", caption = !1, classes = params.attributes.class || "";
                        isImage(node) && ((src = ed.dom.getAttrib(node, "src")) && (label = ed.getLang("update", "Update")), 
                        alt = ed.dom.getAttrib(node, "alt"), ed.dom.getNext(node, "figcaption") && (caption = !0), 
                        classes = ed.dom.getAttrib(node, "class")), urlCtrl.value(src), 
                        descriptionCtrl.value(alt), stylesListCtrl.value(classes), 
                        captionCtrl && captionCtrl.checked(caption), window.setTimeout(function() {
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
                            if (Event.cancel(e), !data.url) return isImage(node) && ed.dom.remove(node), 
                            !1;
                            e = {
                                src: data.url,
                                alt: data.alt,
                                class: data.classes
                            }, e = extend(e, self.getAttributes(params));
                            !function(ed, args) {
                                var params = ed.getParam("imgmanager_ext", {});
                                return new Promise(function(resolve, reject) {
                                    !1 !== params.always_include_dimensions ? (ed.setProgressState(!0), 
                                    validateImage(ed, args.src).then(function(data) {
                                        ed.setProgressState(!1), insertImage(ed, extend(args, data)), 
                                        resolve();
                                    }, function() {
                                        ed.setProgressState(!1), reject();
                                    })) : (insertImage(ed, args), resolve());
                                });
                            }(ed, e).then(function() {
                                var figcaption;
                                node = ed.selection.getNode(), captionCtrl && (figcaption = ed.dom.getNext(node, "figcaption"), 
                                data.caption && data.alt ? figcaption ? figcaption.textContent = data.alt : (ed.selection.select(node), 
                                ed.formatter.apply("figure", {
                                    caption: data.alt
                                })) : figcaption && (ed.dom.remove(figcaption.parentNode, 1), 
                                ed.dom.remove(figcaption)));
                            });
                        },
                        classes: "primary",
                        scope: self
                    } ]
                });
            }));
        }), ed.onInit.add(function() {
            var ux;
            ed && ed.plugins.contextmenu && ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                m.add({
                    title: "imgmanager_ext.desc",
                    icon: "imgmanager_ext",
                    cmd: "mceImageManagerExtended"
                });
            }), ed.settings.compress.css || ed.dom.loadCSS(url + "/css/content.css"), 
            ed.getParam("imgmanager_convert_img_links", 1) && ed.plugins.clipboard && (ux = "^((http|https)://[-!#$%&'*+\\/0-9=?A-Z^_`a-z{|}~;]+[-!#$%&*+\\/0-9=?A-Z^_`a-z{|}~;.]+?).(" + (ed.getParam("imgmanager_ext", {}).filetypes || [ "jpg", "jpeg", "png", "gif", "webp", "avif" ]).join("|") + ")$", 
            ed.onGetClipboardContent.add(function(ed, content) {
                var match, value = content["text/plain"] || "";
                value && (match = new RegExp(ux).exec(value)) && (content["text/plain"] = "", 
                content["text/html"] = content["x-tinymce/html"] = ed.dom.createHTML("img", function(ed, value) {
                    var params = ed.getParam("imgmanager_ext", {}), args = {
                        src: value,
                        alt: ""
                    };
                    return tinymce.each([ "alt", "title", "id", "dir", "class", "usemap", "style", "longdesc", "loading", "width", "height" ], function(key) {
                        tinymce.is(params[key]) && (args[key] = params[key]);
                    }), args.style && (value = ed.dom.parseStyle(ed.dom.serializeStyle(args.style)), 
                    args.style = ed.dom.serializeStyle(value, "IMG")), args;
                }(ed, match[0])), ed.setProgressState(!0), validateImage(ed, value).then(function() {
                    ed.setProgressState(!1);
                }, function() {
                    ed.setProgressState(!1);
                }));
            }));
        }), this.getAttributes = function(data) {
            var attr = {
                style: {}
            }, attribs = data.attributes || {};
            return attribs.style && tinymce.is(attribs.style, "string") && (attribs.style = ed.dom.parseStyle(attribs.style)), 
            attribs.styles && tinymce.is(attribs.styles, "object") && (attribs.style = extend(attribs.styles, attribs.style || {}), 
            delete attribs.styles), attribs.style && (attribs.style = ed.dom.serializeStyle(attribs.style)), 
            tinymce.each([ "alt", "title", "id", "dir", "class", "usemap", "style", "longdesc", "loading" ], function(key) {
                tinymce.is(attribs[key]) && (attr[key] = attribs[key]);
            }), data.width && (attr.width = data.width), data.height && (attr.height = data.height), 
            attr;
        }, this.insertUploadedFile = function(o) {
            var data = this.getUploadConfig();
            if (data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(o.name)) return data = {
                src: o.file,
                alt: o.alt || o.name,
                style: {}
            }, data = extend(data, this.getAttributes(o)), ed.dom.create("img", data);
            return !1;
        }, this.getUploadURL = function(file) {
            var data = this.getUploadConfig();
            return !!(data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(file.name)) && ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=imagepro";
        }, this.getUploadConfig = function() {
            return ed.getParam("imgmanager_ext", {}).upload || {};
        };
    });
}();