(function (document, Joomla) {
    Joomla.submitbutton = function (pressbutton) {
        if (pressbutton === 'new_item') {
            newLanguageItem();
        } else if (pressbutton === 'apply' || pressbutton === 'save') {
            let values = [];
            let newKeys = [];
            let newValues = [];

            document.querySelectorAll('.eb-language-item-value').forEach(function (item) {
                values.push(item.value);
            });

            document.querySelectorAll('.eb-new-key').forEach(function (item) {
                newKeys.push(item.value);
            });

            document.querySelectorAll('.eb-new-value').forEach(function (item) {
                newValues.push(item.value);
            });

            document.getElementById('translate_values').value = values.join('@@@');
            document.getElementById('translate_new_keys').value = newKeys.join('@@@');
            document.getElementById('translate_new_values').value = newValues.join('@@@');

            document.getElementById('translate_filter_search').value = document.getElementById('filter_search').value;
            document.getElementById('translate_filter_language').value = document.getElementById('filter_language').value;
            document.getElementById('translate_filter_item').value = document.getElementById('filter_item').value;

            Joomla.submitform(pressbutton, document.getElementById('translateForm'));

        } else {
            Joomla.submitform(pressbutton);
        }
    };

    function newLanguageItem() {
        const table = document.getElementById('lang_table');
        const row = table.insertRow(1);
        const cell0 = row.insertCell(0);
        cell0.innerHTML = '<input type="text" name="extra_keys[]" class="eb-new-key" size="50" />';
        const cell1 = row.insertCell(1);
        const cell2 = row.insertCell(2);
        cell2.innerHTML = '<input type="text" name="extra_values[]" class="eb-new-value" size="100" />';
    }

    function searchTable() {
        var tableBody = document.getElementById('eb-translation-table');
        var searchTerm = document.getElementById('filter_search').value.toLowerCase();

        tableBody.querySelectorAll('tr').forEach(function (tr) {
            var text = tr.textContent;
            var inputValue = tr.querySelector('input[type="text"]').value;

            if (inputValue.length) {
                text = text + '' + inputValue;
            }

            text = text.replace(/(\r\n|\n|\r)/gm, "").toLowerCase();

            if (text.indexOf(searchTerm) === -1) {
                tr.style.display = 'none';
            } else {
                tr.style.display = '';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var filterSearch = document.getElementById('filter_search');

        if (filterSearch.value) {
            searchTable();
        }

        filterSearch.addEventListener('change', searchTable);

        document.getElementById('eb-clear-button').addEventListener('click', function () {
            filterSearch.value = '';
            searchTable();
        });
    });
})(document, Joomla);