/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function($) {
    var each = tinymce.each, htmlSchema = new tinymce.html.Schema({
        schema: "mixed"
    }), defaultMediaAttributes = {
        flash: {
            play: !0,
            loop: !0,
            menu: !0,
            swliveconnect: !1,
            allowfullscreen: !1
        },
        video: {
            autoplay: !1,
            loop: !1,
            controls: !1,
            muted: !1
        },
        audio: {
            autoplay: !1,
            loop: !1,
            controls: !1,
            muted: !1,
            preload: !1
        }
    };
    for (var y, ext, mimes = {}, items = "video/divx,divx,application/pdf,pdf,application/x-shockwave-flash,swf swfl,audio/mpeg,mpga mpega mp2 mp3,audio/ogg,ogg spx oga,audio/x-wav,wav,video/mpeg,mpeg mpg mpe,video/mp4,mp4 m4v,video/ogg,ogg ogv,video/webm,webm,video/quicktime,qt mov,video/x-flv,flv,video/3gpp,3gp,video/x-matroska,mkv".split(/,/), i = 0; i < items.length; i += 2) for (ext = items[i + 1].split(/ /), 
    y = 0; y < ext.length; y++) mimes[ext[y]] = items[i];
    function getTypeFromMime(mimetype) {
        return {
            "application/x-shockwave-flash": "flash",
            "video/quicktime": "quicktime",
            "video/divx": "divx",
            "video/mp4": "video",
            "video/ogg": "video",
            "video/webm": "video",
            "audio/mpeg": "audio",
            "audio/mp3": "audio",
            "audio/x-wav": "audio",
            "audio/ogg": "audio",
            "audio/webm": "audio",
            "video/x-flv": "video"
        }[mimetype] || "";
    }
    function removeQuery(s) {
        return s && (-1 !== s.indexOf("?") ? s = s.substr(0, s.indexOf("?")) : -1 !== s.indexOf("&") && (s = (s = s.replace(/&amp;/g, "&")).substr(0, s.indexOf("&")))), 
        s;
    }
    function getMimeFromUrl(url) {
        url = removeQuery(url);
        url = (url = Wf.String.getExt(url)).toLowerCase();
        return mimes[url] || !1;
    }
    var MediaManagerDialog = {
        settings: {
            filebrowser: {}
        },
        mediatypes: null,
        convertURL: function(url) {
            var query, n, ed = tinyMCEPopup.editor;
            return url && (query = "", 0 < (n = -1 === (n = url.indexOf("?")) ? (url = url.replace(/&amp;/g, "&")).indexOf("&") : n) && (query = url.substring(n + 1, url.length), 
            url = url.substr(0, n)), (url = ed.convertURL(url)) + (query ? "?" + query : ""));
        },
        init: function() {
            tinyMCEPopup.restoreSelection();
            var attribs, val, type, self = this, ed = tinyMCEPopup.editor, elm = ed.selection.getNode(), mediatype = "video", mediaApi = ed.plugins.media, params = ed.getParam("mediamanager", {});
            $("button#insert").on("click", function(e) {
                self.insert(), e.preventDefault();
            }), this.mediatypes = this.mapTypes(), Wf.init({
                classes: params.custom_classes
            }), WFPopups.setup({
                remove: function(e, el) {
                    ed.dom.remove(ed.dom.getParent(el, "a"), 1);
                }
            }), WFAggregator.setup(), /mce-object/.test(elm.className) ? (params = mediaApi.getMediaData(), 
            attribs = {}, elm = ed.dom.getParent(elm, "[data-mce-object]"), mediatype = params.mediatype || (val = elm.className, 
            val = /mce-object-(flash|quicktime|divx|audio|video|iframe)/.exec(val), 
            type = val ? val[1].toLowerCase() : type) || "video", (val = WFAggregator.isSupported(params.src)) && (mediatype = val), 
            each(params, function(value, name) {
                var tmp;
                return "mediatype" == name || ("innerHTML" == name && value ? (attribs.html = value.trim(), 
                !0) : ("class" == (name = htmlSchema.isValid("img", name) ? name : mediatype + "_" + name) && (name = "classes", 
                value = value.replace(/mce-(\S+)/g, "").replace(/\s+/g, " ").trim()), 
                "align" == name && (value = Wf.getAttrib(elm, "align")), attribs[name] = value, 
                void ("style" == name && (tmp = ed.dom.create("div", {
                    style: value
                }), attribs.align = Wf.getAttrib(tmp, "align"), each([ "top", "right", "bottom", "left" ], function(pos) {
                    attribs["margin_" + pos] = Wf.getAttrib(tmp, "margin-" + pos), 
                    ed.dom.setStyle(tmp, "margin-" + pos, "");
                }), each([ "width", "style", "color" ], function(at) {
                    attribs["border_" + at] = Wf.getAttrib(tmp, "border-" + at), 
                    ed.dom.setStyle(tmp, "border-" + at, "");
                }), each([ "width", "height" ], function(at) {
                    attribs[at] || (attribs[at] = Wf.getAttrib(tmp, at));
                }), ed.dom.setStyles(tmp, {
                    float: "",
                    "vertical-align": "",
                    margin: "",
                    width: "",
                    height: ""
                }), attribs[name] = tmp.style.cssText))));
            }), each(defaultMediaAttributes[mediatype], function(val, name) {
                if (attribs[mediatype + "_" + name]) return !0;
                attribs[mediatype + "_" + name] = val;
            }), $("#popup_list").prop("disabled", !0)) : WFPopups.getPopup(elm, 0, function(popup) {
                return attribs = {}, popup.type || (popup.type = getMimeFromUrl(popup.src)), 
                mediatype = getTypeFromMime(popup.type), each(popup, function(value, name) {
                    var key = name;
                    if ("src" !== name && "source" !== name || (value = self.convertURL(value)), 
                    "source" === name && (value = [ value ]), htmlSchema.isValid("img", name) || (name = mediatype + "_" + key), 
                    delete popup[key], "type" === key) return !0;
                    attribs[name] = value;
                }), popup;
            }), attribs ? ($("#insert").button("option", "label", tinyMCEPopup.getLang("update", "Update", !0)), 
            each([ "width", "height" ], function(key) {
                var value = attribs[key];
                $("#" + key).val(value).data("tmp", value);
            }), type = attribs.src || attribs.data || "", WFAggregator.isSupported(type) && (attribs = WFAggregator.setValues(mediatype, attribs)), 
            each(attribs, function(value, key) {
                var $na;
                return "width" === key || "height" === key || (Array.isArray(value) ? (each(value, function(val, i) {
                    $('input[name="' + key + '[]"]').eq(i).val(val).trigger("change");
                }), !0) : void (($na = $("#" + key)).length && ($na.is(":checkbox") ? $na.prop("checked", !!(value = "false" != value && "0" != value ? value : !1)).trigger("change") : $na.val(value))));
            }), "audio" != mediatype && "video" != mediatype || $(":input, select", "#" + mediatype + "_options").each(function() {
                $(this).is(":checkbox") ? $(this).prop("checked", !1) : $(this).val("");
            })) : Wf.setDefaults(this.settings.defaults), $("#media_type").val(mediatype).trigger("change"), 
            Wf.updateStyles(), attribs = attribs || {
                src: "",
                width: "",
                height: ""
            }, $("#src").filebrowser().on("filebrowser:onfileclick", function(e, file, data) {
                self.selectFile(file, data);
            }).on("filebrowser:onfiledetails", function(e, item, data) {
                var type;
                attribs.src !== data.url && ((type = self.getType(data.url)) && (type = WFAggregator.getAttributes(type, data.url), 
                $.each(type, function(key, value) {
                    value && (data[key] = value);
                })), $.each(data, function(key, value) {
                    if (!$.type(value)) return !0;
                    "width" != key && "height" != key || ($("#" + key).val(value).data("tmp", value).trigger("change"), 
                    attribs[key] = null);
                }));
            }).on("filebrowser:onfileinsert", function(e, file, data) {
                self.insert();
            }), $("#src").on("change", function() {
                var provider;
                this.value && !self.selectType(this.value) && (provider = mediaApi.isSupportedMedia(this.value)) && (provider = mediaApi.getMediaProps({
                    src: this.value
                }, provider), $.each(provider, function(key, value) {
                    "width" === key || "height" === key ? $("#" + key).val(value).trigger("change") : $("#" + key).val(value);
                }), $("#media_type").val("iframe").trigger("change"));
            }), $("#width, #height").on("change", function() {
                var n = $(this).attr("id"), v = this.value;
                "audio" === $("#media_type").val() && self.addStyle(n, v);
            }), $("#border").change(), $(".uk-equalize-checkbox").trigger("equalize:update"), 
            $(".uk-form-controls select:not(.uk-datalist)").datalist({
                input: !1
            }).trigger("datalist:update"), $(".uk-datalist").trigger("datalist:update"), 
            $(".uk-repeatable").on("repeatable:delete", function(e, ctrl, elm) {
                $(elm).find("input, select").eq(1).val("");
            });
        },
        getAttrib: function(node, attrib) {
            return Wf.getAttrib(node, attrib);
        },
        getSiteRoot: function() {
            return tinyMCEPopup.getParam("document_base_url").match(/.*:\/\/([^\/]+)(.*)/)[2];
        },
        setControllerHeight: function(t) {
            $("#controller_height").val(0);
        },
        isIframe: function(n) {
            return n && -1 !== n.className.indexOf("mce-object-iframe");
        },
        addStyle: function(style, value) {
            style = $("<div></div>").attr("style", $("#style").val()).css(style, value).get(0).style.cssText;
            $("#style").val(style);
        },
        insert: function() {
            var src = $("#src").val(), type = $("#media_type").val();
            return "" == src ? (Wf.Modal.alert(tinyMCEPopup.getLang("mediamanager_dlg.no_src", "Please select a file or enter in a link to a file")), 
            !1) : $("#width").val() && $("#height").val() ? void this.insertAndClose() : ("audio" === type && this.insertAndClose(), 
            WFPopups.isEnabled() && this.insertAndClose(), Wf.Modal.alert(tinyMCEPopup.getLang("mediamanager_dlg.no_dimensions", "A width and height value are required."), {
                close: function() {
                    $("#width, #height").map(function() {
                        if (!this.value) return this;
                    }).first().focus();
                }
            }), !1);
        },
        insertAndClose: function() {
            tinyMCEPopup.restoreSelection();
            var type, ed = tinyMCEPopup.editor, classes = [ "mce-object" ], attribs = {}, args = {}, data = {}, popupData = {}, boolAttrs = htmlSchema.getBoolAttrs(), mediatype = $("#media_type").val(), nodeName = mediatype, elm = ed.selection.getNode(), elm = ed.dom.getParent(elm, "[data-mce-object]"), innerHTML = (mediatype == WFAggregator.isSupported($("#src").val()) && (WFAggregator.onInsert(mediatype), 
            nodeName = WFAggregator.getType(mediatype)), nodeName = "iframe" === (type = nodeName) || "video" === type || "audio" === type ? nodeName : "object", 
            classes.push("mce-object-" + mediatype), $("input[id], select[id]").each(function() {
                var val = $(this).val();
                $(this).is(":checkbox") && (val = !!$(this).is(":checked")), data[this.id] = val;
            }), data.classes = $.trim(data.classes + " " + classes.join(" ")), (type = WFAggregator.isSupported(data.src)) && $.extend(!0, data, WFAggregator.getValues(type, data)), 
            delete data.mediatype, each(data, function(value, name) {
                return "classes" === name ? (attribs.class = value, !0) : !htmlSchema.isValid(nodeName, name) || void (attribs[name] = value);
            }), "audio" !== mediatype && (attribs["data-mce-width"] = attribs.width || 384, 
            attribs["data-mce-height"] = attribs.height || 216), ""), classes = ("object" === nodeName && (attribs.data = data.src, 
            attribs.type = function(type) {
                return {
                    flash: "application/x-shockwave-flash",
                    quicktime: "video/quicktime",
                    divx: "video/divx",
                    flv: "video/x-flv",
                    audio: "audio/mpeg",
                    video: "video/mpeg"
                }[type] || "";
            }(mediatype), "flash" === mediatype) && (data.flash_movie = data.src), 
            each(data, function(value, name) {
                return 0 !== name.indexOf(mediatype) || (name = name.replace(mediatype + "_", ""), 
                !(!defaultMediaAttributes[mediatype] || boolAttrs[name] || value !== defaultMediaAttributes[mediatype][name])) || "" === value || ("source" === name ? (each(value, function(source) {
                    if (!source) return !0;
                    var mimetype = getMimeFromUrl(source);
                    mimetype = (mimetype = mimetype || mediatype + "/mpeg").replace(/(audio|video)/, mediatype), 
                    innerHTML += '<source src="' + source + '" type="' + mimetype + '"></source>', 
                    popupData.source = source;
                }), !0) : (popupData[name] = value, "object" === nodeName ? (innerHTML += '<param name="' + name + '" value="' + value + '" />', 
                !0) : void (attribs[name] = value)));
            }), $("#html").val() && (innerHTML += $("#html").val()), ed.plugins.media);
            elm && classes.isMediaObject(elm) ? (attribs.innerHTML = innerHTML, 
            classes.updateMedia(attribs)) : WFPopups.isEnabled() && ($("#popup_text").is(":disabled") || "" != $("#popup_text").val()) ? (args = {
                type: getMimeFromUrl(attribs.src),
                data: popupData
            }, each(attribs, function(value, name) {
                if (0 === name.indexOf("data-mce-")) return !0;
                args[name] = value;
            }), WFPopups.createPopup(elm, args)) : (type = ed.dom.createHTML(nodeName, attribs, $.trim(innerHTML)), 
            ed.execCommand("mceInsertContent", !1, type, {
                skip_undo: 1
            })), ed.undoManager.add(), ed.nodeChanged(), tinyMCEPopup.close();
        },
        mapTypes: function() {
            var types = {}, mt = this.settings.media_types;
            return tinymce.each(tinymce.explode(mt, ";"), function(v, k) {
                v && v.replace(/([a-z0-9]+)=([a-z0-9,]+)/, function(a, b, c) {
                    types[b] = c.split(",");
                });
            }), types;
        },
        checkType: function(src) {
            src = getMimeFromUrl(src);
            return src && getTypeFromMime(src) || !1;
        },
        getType: function(v) {
            var type, path, x, data = {
                width: "",
                height: ""
            };
            return !!v && (path = removeQuery(v), (type = /\.([a-z0-9]{3,4}$)/i.test(path) ? this.checkType(path) : type) || (path = WFAggregator.isSupported(v)) && (data = WFAggregator.getAttributes(path, v), 
            type = path), "video" == type && (data = WFAggregator.getAttributes(type, v)), 
            x = 0, tinymce.each(data, function(value, key) {
                var $el;
                value && ("width" === key || "height" === key ? $("#" + key).val(value).trigger("change") : ($el = $("#" + key)).length ? $el.is(":checkbox") ? $el.attr("checked", !!parseFloat(value)).prop("checked", !!parseFloat(value)) : $el.val(value) : (key = key.substr(type.length + 1), 
                $el = $(".media_option." + type + " .uk-repeatable"), 0 < x && ($el.eq(0).clone(!0).appendTo($el.parent()), 
                $el = $(".media_option." + type + " .uk-repeatable")), ($el = $el.eq(x).find("input, select")).eq(0).val(key), 
                $el.eq(1).val(value), x++));
            }), type);
        },
        selectType: function(v) {
            v = this.getType(v);
            return !!v && ($("#media_type").val(v).trigger("change"), !0);
        },
        changeType: function(type) {
            type = type || $("#media_type").val();
            this.setControllerHeight(type), $(".media_option", "#media_tab").hide().filter("." + type).show();
        },
        checkPrefix: function(n) {
            /^\s*www./i.test(n.value) && confirm(tinyMCEPopup.getLang("mediamanager_dlg_is_external", !1, "The URL you entered seems to be an external link, do you want to add the required http:// prefix?")) && (n.value = "http://" + n.value);
        },
        setSourceFocus: function(n) {
            $("input.uk-active").removeClass("uk-active"), $(n).addClass("uk-active");
        },
        selectFile: function(file, data) {
            var name = data.title, src = data.url;
            $("#media_tab").hasClass("uk-active") ? $("input.uk-active", "#media_tab").val(src) : ($("#src").val(src), 
            MediaManagerDialog.selectType(name), data.width && data.height && ($("#width").val(data.width).data("tmp", data.width), 
            $("#height").val(data.height).data("tmp", data.height)), WFAggregator.isSupported(src) && WFAggregator.onSelectFile(name));
        }
    };
    window.MediaManagerDialog = MediaManagerDialog, tinyMCEPopup.onInit.add(MediaManagerDialog.init, MediaManagerDialog);
}(jQuery, tinyMCEPopup);