'use strict';
/** global: com_jgive */
export class Base {

    constructor(url, data, config) {
        this.url = url;
        this.data = data || {};

        if (typeof this.data === 'object') {
            this.data = JSON.stringify(this.data);
        }

        this.config = config || {};
        this.config.headers = {};

        if (typeof config != "undefined") {
            this.config.headers = config.headers;
        }
    }

    /**
     * @param   string  url       API Request URL
     * @param   config  object    Configuration object
     * @param   cb      function  Callback function
     */
    get(cb) {
        if (typeof cb !== 'function') {
            throw "base expects callback to be function";
        }

        return jQuery.ajax({
            type: "GET",
            url: this.url,
            headers: this.config.headers,
            beforeSend: function () {
            },
            success: function (res) {
                cb(null, res);
            },
            error: function (err) {
                cb(err, null);
            }
        });
    }

    /**
     * @param   string  url       API Request URL
     * @param   data    object    Data to post
     * @param   config  object    Configuration object which have headers
     * @param   cb      function  Callback function
     */
    post(cb) {
        if (typeof cb !== 'function') {
            throw "base expects callback to be function";
        }

        return jQuery.ajax({
            type: "POST",
            url: this.url,
            data: this.data,
            headers: this.config.headers,
            beforeSend: function () {
            },
            success: function (res) {
                cb(null, res);
            },
            error: function (err) {
                cb(err, null);
            }
        });
    }

    /**
     * @param   string  url       API Request URL
     * @param   config  object    Configuration object which have headers
     * @param   cb      function  Callback function
     */
    patch(url, data, config, cb) {
        data = data || {};
        config = config || {};
        config.headers = config.headers || {};

        if (typeof cb !== 'function') {
            throw "base expects callback to be function";
        }

        if (typeof data === 'object') {
            data = JSON.stringify(data);
        }

        return jQuery.ajax({
            type: "PATCH",
            url: url,
            data: data,
            headers: config.headers,
            beforeSend: function () {
            },
            success: function (res) {
                cb(null, res);
            },
            error: function (xhr) {
                cb(xhr, null);
            }
        });
    }
}
