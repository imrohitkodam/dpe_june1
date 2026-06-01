/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each, extend = tinymce.extend, HtmlSerializer = tinymce.html.Serializer, HtmlDomParser = tinymce.html.DomParser, XHR = tinymce.util.XHR, Uuid = tinymce.util.Uuid, fontIconRe = /<([a-z0-9]+)([^>]+)class="([^"]*)(glyph|uk-)?(fa|icon)-([\w-]+)([^"]*)"([^>]*)>(&nbsp;|\u00a0)?<\/\1>/gi;
    function dataToHtml(editor, data) {
        if (!data) return "";
        var doc, data = data.trim(), parser = new DOMParser();
        try {
            doc = parser.parseFromString(data, "text/html");
        } catch (e) {
            return editor.windowManager.alert("Error parsing HTML:", e), "";
        }
        parser = doc.body ? doc.body.innerHTML : "", data = new HtmlDomParser({
            allow_event_attributes: !!editor.settings.allow_event_attributes
        }, editor.schema).parse(parser, {
            forced_root_block: !1,
            isRootContent: !0
        });
        return new HtmlSerializer({
            validate: editor.settings.validate
        }, editor.schema).serialize(data);
    }
    function createClassSelector(values) {
        return values = values.trim(), tinymce.map(values.split(" "), function(cls) {
            if (cls) return "." + cls;
        }).join(",");
    }
    function getContentAndInsert(ed, url) {
        ed.setProgressState(!0);
        var absoluteUrl = ed.documentBaseURI.toAbsolute(url);
        return new Promise(function(resolve, reject) {
            XHR.send({
                url: absoluteUrl,
                success: function(value) {
                    value = dataToHtml(ed, value);
                    value && ed.execCommand("mceInsertTemplate", !1, {
                        content: value
                    }), ed.setProgressState(!1), resolve();
                },
                error: function(e) {
                    ed.setProgressState(!1), reject();
                }
            });
        });
    }
    tinymce.PluginManager.add("templatemanager", function(ed, url) {
        var self = this, params = (self.contentLoaded = !1, ed.getParam("templatemanager", {}));
        ed.addCommand("mceTemplate", function(ui) {
            window.matchMedia("(max-width: 600px)").matches ? ed.execCommand("mceFileBrowser", !0, {
                caller: "templatemanager",
                filter: params.filetypes || "html,htm,txt,md",
                callback: function(selected, data) {
                    data = data[0].url;
                    data && getContentAndInsert(ed, data).then();
                }
            }) : ed.windowManager.open({
                file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=templatemanager",
                size: "mce-modal-landscape-xxlarge"
            }, {
                plugin_url: url
            });
        }), ed.onInit.add(function() {
            ed.plugins.contextmenu && ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                m.add({
                    title: "templatemanager.desc",
                    icon: "templatemanager",
                    cmd: "mceTemplate"
                });
            });
        }), ed.addCommand("mceInsertTemplate", function(ui, o) {
            return function(args) {
                var html = function(html) {
                    var dom = ed.dom, replace_values = params.replace_values || {}, cdate_classes = (html = html.replace(/\{\$(.*?)\}/g, "${$1}"), 
                    each(replace_values, function(value, key) {
                        "function" != typeof value && (html = html.replace(new RegExp("\\$\\{" + key + "\\}", "g"), value));
                    }), html = dataToHtml(ed, html), replace_values = dom.create("div", null, html), 
                    dom.remove(dom.select("div.mceTmpl", replace_values), 1), params.cdate_classes || "cdate creationdate"), cdate_format = params.cdate_format || ed.getLang("templatemanager.cdate_format"), mdate_classes = params.mdate_classes || "mdate modifieddate", mdate_format = params.mdate_format || ed.getLang("templatemanager.mdate_format"), selected_content_classes = params.selected_content_classes || "selcontent", selection = ed.selection.getContent(), cdate_classes = createClassSelector(cdate_classes), mdate_classes = createClassSelector(mdate_classes), selected_content_classes = createClassSelector(selected_content_classes);
                    return each(dom.select(cdate_classes, replace_values), function(elm) {
                        elm.innerHTML = getDateTime(new Date(), cdate_format);
                    }), each(dom.select(mdate_classes, replace_values), function(elm) {
                        elm.innerHTML = getDateTime(new Date(), mdate_format);
                    }), each(dom.select(selected_content_classes, replace_values), function(elm) {
                        elm.innerHTML = selection;
                    }), replace_values.innerHTML;
                }(args.content || "");
                function insertAndUpdate(content) {
                    ed.execCommand("mceInsertContent", !1, content), !1 === ed.settings.verify_html && (ed.settings.validate = !1), 
                    ed.addVisual();
                }
                !1 === ed.settings.validate && (ed.settings.validate = !0);
                var values = (html = (html = (html = html.replace(fontIconRe, '<$1$2class="$3$4$5-$6$7"$8>&nbsp;</$1>')).replace(/<(a|i|span)([^>]+)><\/\1>/gi, "<$1$2>&nbsp;</$1>")).replace(/\{\$(.*?)\}/g, "${$1}")).match(/\$\{([^\}]+?)\}/g);
                {
                    var cm, form, controls, supportedTypes;
                    values ? (cm = ed.controlManager, form = cm.createForm("templatemanager_form"), 
                    controls = {}, form.empty(), supportedTypes = {
                        image: "images",
                        file: "files",
                        media: "media"
                    }, each(values, function(key) {
                        var key = function(key) {
                            var m = /\$\{([^}]+)\}/.exec(key), m = m ? m[1] : key, key = m.indexOf("|"), left = -1 !== key ? m.slice(0, key) : m, m = -1 !== key ? m.slice(key + 1).trim() : "", key = left, type = "text", colonIdx = left.indexOf(":");
                            -1 !== colonIdx && (key = left.slice(0, colonIdx), type = left.slice(colonIdx + 1));
                            left = key.replace(/[^a-z0-9]/gi, "_").toLowerCase();
                            return {
                                label: ed.getLang(key, key),
                                name: left,
                                type: type,
                                description: m
                            };
                        }(key), type = key.type || "text", id = key.name, ctrl = type && supportedTypes[type] ? (key = extend(key, {
                            picker: !0,
                            picker_label: "browse",
                            picker_icon: type,
                            onpick: function() {
                                ed.execCommand("mceFileBrowser", !0, {
                                    caller: function(type) {
                                        if ("image" == type) {
                                            if (ed.plugins.imgmanager_ext) return "imgmanager_ext";
                                            if (ed.plugins.imgmanager) return "imgmanager";
                                        }
                                        return "media" == type && ed.plugins.mediamanager ? "mediamanager" : "";
                                    }(type),
                                    callback: function(selected, data) {
                                        var src, mdate_format;
                                        data.length && (data = data[0], src = data.url, 
                                        ctrl.value(src), data.modified && (mdate_format = params.mdate_format || ed.getLang("templatemanager.mdate_format", "%d/%m/%Y, %H:%M"), 
                                        data.date = getDateTime(data.modified, mdate_format)), 
                                        data.size && (data.size = function(s, int) {
                                            if (!s) return "";
                                            if (1048576 < s) return n = Math.round(s / 1048576 * 100) / 100, 
                                            int ? n : n + " " + ed.getLang("size_mb", "MB");
                                            {
                                                var n;
                                                if (1024 < s) return n = Math.round(s / 1024 * 100) / 100, 
                                                int ? n : n + " " + ed.getLang("size_kb", "KB");
                                            }
                                            if (int) return s;
                                            return s + " " + ed.getLang("size_bytes", "Bytes");
                                        }(data.size)), data.filename = src.replace(/^.*[\/\\]/g, "").replace(/\.[^.]+$/i, ""), 
                                        data.extension = src.substring(src.length, src.lastIndexOf(".") + 1), 
                                        each(data, function(value, key) {
                                            controls[key] && controls[key].control.value(value);
                                        }), window.setTimeout(function() {
                                            ctrl.focus();
                                        }, 10));
                                    },
                                    filter: supportedTypes[type],
                                    value: ctrl.value()
                                });
                            }
                        }), cm.createUrlBox("templatemanager_form_" + id, key)) : ("textarea" == type && (key.multiline = !0), 
                        key.attributes = {}, "disabled" == type && (key.attributes.disabled = "disabled"), 
                        "readonly" == type && (key.attributes.readonly = "readonly"), 
                        "hidden" == type && (key.subtype = "hidden"), cm.createTextBox("templatemanager_form_" + id, key));
                        controls[id] = {
                            control: ctrl,
                            type: type
                        }, form.add(ctrl);
                    }), ed.windowManager.open({
                        title: args.name || ed.getLang("templatemanager.values", "Values"),
                        items: [ form ],
                        size: "mce-modal-landscape-medium",
                        open: function() {
                            window.setTimeout(function() {
                                form.controls[0].focus();
                            }, 10);
                        },
                        buttons: [ {
                            title: ed.getLang("cancel", "Cancel"),
                            id: "cancel"
                        }, {
                            title: ed.getLang("insert", "Insert"),
                            id: "insert",
                            onsubmit: function(e) {
                                var data = form.submit();
                                each(data, function(value, key) {
                                    value = ed.dom.create("div", {}, value).textContent, 
                                    html = html.replace(new RegExp("\\$\\{" + key + "(?::[^|}]+)?(?:\\|[^}]+)?\\}", "gi"), value);
                                }), insertAndUpdate(html);
                            },
                            classes: "primary",
                            scope: this
                        } ]
                    })) : insertAndUpdate(html);
                }
            }(o);
        }), params.list || ed.addButton("templatemanager", {
            title: "templatemanager.desc",
            cmd: "mceTemplate"
        }), ed.onPreProcess.add(function(ed, o) {
            var dom = ed.dom, mdate_classes = (dom.remove(dom.select("div.mceTmpl", o.node), 1), 
            params.mdate_classes || "mdate modifieddate"), mdate_format = params.mdate_format || ed.getLang("templatemanager.mdate_format"), ed = createClassSelector(mdate_classes);
            each(dom.select(ed, o.node), function(elm) {
                elm.innerHTML = getDateTime(new Date(), mdate_format);
            });
        });
        var content_url = params.content_url || "";
        function getDateTime(d, fmt) {
            return fmt ? ("string" == typeof d && (d = new Date(1e3 * d)), (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = (fmt = fmt.replace("%D", "%m/%d/%y")).replace("%r", "%I:%M:%S %p")).replace("%Y", "" + d.getFullYear())).replace("%y", "" + d.getYear())).replace("%m", addZeros(d.getMonth() + 1, 2))).replace("%d", addZeros(d.getDate(), 2))).replace("%H", "" + addZeros(d.getHours(), 2))).replace("%M", "" + addZeros(d.getMinutes(), 2))).replace("%S", "" + addZeros(d.getSeconds(), 2))).replace("%I", "" + ((d.getHours() + 11) % 12 + 1))).replace("%p", d.getHours() < 12 ? "AM" : "PM")).replace("%B", "" + ed.getLang("templatemanager_months_long").split(",")[d.getMonth()])).replace("%b", "" + ed.getLang("templatemanager_months_short").split(",")[d.getMonth()])).replace("%A", "" + ed.getLang("templatemanager_day_long").split(",")[d.getDay()])).replace("%a", "" + ed.getLang("templatemanager_day_short").split(",")[d.getDay()])).replace("%%", "%")) : "";
            function addZeros(value, len) {
                var i;
                if ((value = "" + value).length < len) for (i = 0; i < len - value.length; i++) value = "0" + value;
                return value;
            }
        }
        content_url && ed.onInit.add(function() {
            var content;
            self.contentLoaded || "" != (content = ed.getContent()) && "<p>&nbsp;</p>" != content || (/http(s)?:\/\//.test(content_url) || (ed.setProgressState(!0), 
            getContentAndInsert(ed, content_url).then(function() {
                self.contentLoaded = !0;
            }).finally(function() {
                ed.setProgressState(!1);
            }).catch(function() {
                ed.setProgressState(!1);
            })));
        }), this.createControl = function(name, cm) {
            if ("templatemanager" == name && params.list) return (name = cm.createSplitButton("templatemanager", {
                title: "templatemanager.desc",
                cmd: !1 !== params.dialog ? "mceTemplate" : null,
                class: "mce_templatemanager"
            })).onRenderMenu.add(function(btn, menu) {
                var editor, loader = menu.add({
                    id: ed.dom.uniqueId(),
                    title: ed.getLang("dlg.message_load")
                });
                editor = ed, new Promise(function(resolve, reject) {
                    var args = {
                        id: Uuid.uuid("wf_"),
                        method: "getTemplateList",
                        params: []
                    };
                    tinymce.util.XHR.send({
                        url: editor.getParam("site_url") + "index.php?option=com_jce&task=plugin.rpc&plugin=templatemanager&" + editor.settings.query,
                        data: "json=" + JSON.stringify(args),
                        content_type: "application/x-www-form-urlencoded",
                        success: function(response) {
                            var data = "";
                            try {
                                data = JSON.parse(response);
                            } catch (e) {
                                reject();
                            }
                            data.result || reject(), data.result.error && reject();
                            response = tinymce.is(data.result, "object") ? data.result : {};
                            if ("string" == typeof data.result) try {
                                response = JSON.parse(data.result);
                            } catch (e) {
                                return reject();
                            }
                            resolve(response);
                        },
                        error: function(err, xhr) {
                            return reject();
                        }
                    });
                }).then(function(templateData) {
                    loader.remove(), each(templateData, function(value, name) {
                        "string" == typeof value && (value = {
                            data: value,
                            image: "",
                            description: ""
                        });
                        var item = menu.add({
                            id: ed.dom.uniqueId(),
                            title: name,
                            description: value.description || "",
                            image: value.image,
                            onclick: function(e) {
                                return item.setSelected(!1), function(args) {
                                    var value = args.value, name = args.name || "";
                                    /\.(html|html|txt|md)$/i.test(value) ? (ed.setProgressState(!0), 
                                    XHR.send({
                                        url: value,
                                        success: function(val) {
                                            val = dataToHtml(ed, val);
                                            val && (ed.execCommand("mceInsertTemplate", !1, {
                                                content: val,
                                                name: name
                                            }), ed.setProgressState(!1));
                                        }
                                    })) : (value = ed.dom.decode(value), ed.execCommand("mceInsertTemplate", !1, {
                                        content: value,
                                        name: name
                                    }));
                                }({
                                    value: value.data,
                                    name: name
                                }), !1;
                            }
                        });
                    });
                }, function() {});
            }), name;
        }, this.insertUploadedFile = function(o) {
            var data = this.getUploadConfig();
            if (data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(o.name)) return o.data && (data = dataToHtml(ed, o.data)) && ed.execCommand("mceInsertTemplate", !1, {
                content: data
            }), !0;
            return !1;
        }, this.getUploadURL = function(file) {
            var data = this.getUploadConfig();
            return !!(data && data.filetypes && new RegExp(".(" + data.filetypes.join("|") + ")$", "i").test(file.name)) && ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=templatemanager";
        }, this.getUploadConfig = function() {
            return params.upload || {};
        }, this.processContent = function(html) {
            var settings, frag;
            return ed.settings.validate && (settings = {
                forced_root_block: !1
            }, frag = new HtmlDomParser({}, ed.schema).parse(html, settings), html = new HtmlSerializer(settings, ed.schema).serialize(frag, {})), 
            html;
        };
    });
}();