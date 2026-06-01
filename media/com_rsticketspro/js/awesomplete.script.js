var initAwesomplete = function(id, allowEditor, messageboxId) {
    window.addEventListener('DOMContentLoaded', function() {
        var timeoutticket_search;
        var queryInput = document.querySelector('#' + id);
        var awesomplete = new Awesomplete(queryInput, {
            filter: function() {
                return true;
            },
            sort: false,
            list: [],
            maxItems: 50,
        });

        queryInput.addEventListener('input', function (evt) {
            var inputText = evt.target.value;

            if (timeoutticket_search) {
                clearTimeout(timeoutticket_search);
            }

            timeoutticket_search = window.setTimeout(function() {
                var xmlHttp = new XMLHttpRequest();
                var params = [
                    'option=com_rsticketspro',
                    'view=kbresults',
                    'format=json',
                    'filter_search=' + encodeURIComponent(inputText)
                ];
                xmlHttp.open('POST', Joomla.getOptions('system.paths').base + '/index.php?option=com_rsticketspro', true);
                xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xmlHttp.send(params.join('&'));

                xmlHttp.onreadystatechange = function() {
                    if (this.readyState === 4) {
                        var data = JSON.parse(this.responseText);

                        awesomplete.list = data.list;
                        awesomplete.evaluate();
                    }
                }
            }, 500);
        });

        queryInput.addEventListener('awesomplete-selectcomplete', function(evt) {
            this.value = '';
            var cid = evt.text.value;
            var xmlHttp = new XMLHttpRequest();
            var params = [
                'option=com_rsticketspro',
                'view=article',
                'format=json',
                'cid=' + encodeURIComponent(cid)
            ];
            xmlHttp.open('POST', Joomla.getOptions('system.paths').base + '/index.php?option=com_rsticketspro', true);
            xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xmlHttp.send(params.join('&'));

            xmlHttp.onreadystatechange = function() {
                if (this.readyState === 4) {
                    var data = JSON.parse(this.responseText);
                    if (allowEditor) {
                        Joomla.editors.instances[messageboxId].setValue(data.text);
                    } else {
                        document.getElementById(messageboxId).value = data.text;
                    }
                }
            }
        });
    });
}