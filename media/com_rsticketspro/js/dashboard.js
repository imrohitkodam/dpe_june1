window.addEventListener('DOMContentLoaded', function() {
    var timeoutticket_search;
    var itemskb_urls;
    var queryInput = document.querySelector("#rsticketspro_searchinp");
    var awesomplete = new Awesomplete(queryInput, {
        filter: function() {
            return true;
        },
        sort: false,
        list: []
    });

    queryInput.addEventListener("input", function (evt) {
        var inputText = evt.target.value;

        if (timeoutticket_search) {
            clearTimeout(timeoutticket_search);
        }

        timeoutticket_search = window.setTimeout(function() {
            var searchIcon = document.getElementById('rstickets_search_icon');
            var loadingIcon = document.getElementById('rsticketspro_loading');

            searchIcon.style.display = 'none';
            loadingIcon.style.display = '';

            var xmlHttp = new XMLHttpRequest();
            var params = [
                'option=com_rsticketspro',
                'view=kbresults',
                'format=json',
                'kb_itemid=' + document.getElementsByName('kb_itemid')[0].value,
                'Itemid=' + document.getElementsByName('curr_itemid')[0].value,
                'filter_search=' + encodeURIComponent(inputText)
            ];
            xmlHttp.open('POST', Joomla.getOptions('system.paths').base + '/index.php?option=com_rsticketspro', true);
            xmlHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xmlHttp.send(params.join('&'));

            xmlHttp.onreadystatechange = function() {
                if (this.readyState === 4) {
                    var data = JSON.parse(this.responseText);

                    itemskb_urls = data.urls;

                    awesomplete.list = data.list;
                    awesomplete.evaluate();

                    loadingIcon.style.display = 'none';
                    searchIcon.style.display = '';
                }
            }
        }, 500);
    });

    queryInput.addEventListener('awesomplete-selectcomplete', function(evt) {
        this.value = '';

        if (typeof itemskb_urls[evt.text.value] !== 'undefined') {
            document.location.href = itemskb_urls[evt.text.value];
        }
    });
});