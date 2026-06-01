/* jce - 2.9.95 | 2025-10-14 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2025 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function() {
    var each = tinymce.each;
    tinymce.PluginManager.add("microdata", function(ed, url) {
        ed.addCommand("mceMicrodata", function() {
            ed.windowManager.open({
                file: ed.getParam("site_url") + "index.php?option=com_jce&task=plugin.display&plugin=microdata",
                size: "mce-modal-square-medium"
            }, {
                plugin_url: url
            });
        }), ed.addButton("microdata", {
            title: "microdata.desc",
            cmd: "mceMicrodata"
        }), ed.onNodeChange.add(function(ed, cm, n, co) {
            cm.setDisabled("microdata", co), cm.setActive("microdata", co && n.getAttribute("itemprop"));
        }), ed.onInit.add(function(ed) {
            ed.settings.compress.css || ed.dom.loadCSS(url + "/css/content.css");
        }), ed.onPreInit.add(function() {
            var attribs = [ "itemscope", "itemtype", "itemid", "itemprop", "itemref" ].concat([ "about", "rel", "rev", "resource", "property", "datatype", "typeof" ]);
            ed.schema.addValidElements("meta[itemprop|content|id|class|name|http-equiv|charset]"), 
            ed.schema.addValidElements("link[href|itemprop|id|class|rel|media|hreflang|type|sizes]"), 
            each(ed.schema.elements, function(v, k) {
                var elmAttribs;
                /\w+/.test(k) && v.attributes && (elmAttribs = attribs.slice(0), 
                each(elmAttribs, function(name) {
                    return !name || (v.attributes[name] ? (elmAttribs.splice(elmAttribs.indexOf(name), 1), 
                    !0) : void (v.attributes[name] = "itemscope" == name ? {
                        defaultValue: "itemscope"
                    } : {}));
                }), v.attributesOrder.concat(v.attributesOrder, elmAttribs), k = ed.schema.children[k]) && k.span && (k.meta = {}, 
                k.link = {});
            }), ed.parser.addAttributeFilter("itemscope,itemtype,itemid,itemprop,itemref,content", function(nodes, name) {
                for (var node, v, i = nodes.length; i--; ) v = (node = nodes[i]).attr(name), 
                "content" === name && "meta" === node.name || ("itemscope" === name && (v = "itemscope"), 
                "itemtype" === name && -1 === v.indexOf("://") && (v = "https://schema.org/" + v));
            }), ed.formatter.register({
                microdata: {
                    inline: "span",
                    onformat: function(elm, fmt, vars) {
                        each(vars, function(value, key) {
                            ed.dom.setAttrib(elm, key, value);
                        });
                    }
                },
                "microdata-remove": {
                    selector: "*",
                    attributes: [ "itemprop" ],
                    remove: "emtpy"
                }
            });
        });
    });
}();