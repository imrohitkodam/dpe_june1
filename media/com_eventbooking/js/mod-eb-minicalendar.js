(function (document, Joomla) {
    function previousMonthClick() {
        const miniCalendarContainer = document.getElementById('eb-minicalendar-container');
        let itemId = miniCalendarContainer.querySelector('input[name="itemId"]').value;
        let month = miniCalendarContainer.querySelector('input[name="month_ajax"]').value;
        let year = miniCalendarContainer.querySelector('input[name="year_ajax"]').value;
        let categoryId = miniCalendarContainer.querySelector('input[name="category_id_ajax"]').value;

        if (month === "1") {
            month = 13;
            year--;
        }

        month--;

        reloadMiniCalendar(itemId, month, year, categoryId);
    }

    function nextMonthClick() {
        const miniCalendarContainer = document.getElementById('eb-minicalendar-container');
        let itemId = miniCalendarContainer.querySelector('input[name="itemId"]').value;
        let month = miniCalendarContainer.querySelector('input[name="month_ajax"]').value;
        let year = miniCalendarContainer.querySelector('input[name="year_ajax"]').value;
        let categoryId = miniCalendarContainer.querySelector('input[name="category_id_ajax"]').value;

        if (month === "12") {
            month = 0;
            year++;
        }

        month++;

        reloadMiniCalendar(itemId, month, year, categoryId);
    }

    function previousYearClick() {
        const miniCalendarContainer = document.getElementById('eb-minicalendar-container');
        var itemId = miniCalendarContainer.querySelector('input[name="itemId"]').value;
        var month = miniCalendarContainer.querySelector('input[name="month_ajax"]').value;
        var year = miniCalendarContainer.querySelector('input[name="year_ajax"]').value;
        var categoryId = miniCalendarContainer.querySelector('input[name="category_id_ajax"]').value;

        year--;

        reloadMiniCalendar(itemId, month, year, categoryId);
    }

    function bindEventListener() {
        document.getElementById('prev_month').addEventListener('click', previousMonthClick);
        document.getElementById('next_month').addEventListener('click', nextMonthClick);
        document.getElementById('prev_year').addEventListener('click', previousYearClick);
        document.getElementById('next_year').addEventListener('click', nextYearClick);
    }

    function nextYearClick() {
        const miniCalendarContainer = document.getElementById('eb-minicalendar-container');
        let itemId = miniCalendarContainer.querySelector('input[name="itemId"]').value;
        let month = miniCalendarContainer.querySelector('input[name="month_ajax"]').value;
        let year = miniCalendarContainer.querySelector('input[name="year_ajax"]').value;
        let categoryId = miniCalendarContainer.querySelector('input[name="category_id_ajax"]').value;

        year++;

        reloadMiniCalendar(itemId, month, year, categoryId);
    }

    function reloadMiniCalendar(itemId, month, year, categoryId) {
        Joomla.request({
            url: Joomla.getOptions('siteUrl') + '/index.php?option=com_eventbooking&view=calendar&layout=mini&format=raw&month=' + month + '&year=' + year + '&id=' + categoryId + '&Itemid=' + itemId,
            method: 'POST',
            onSuccess: function (html) {
                document.getElementById('calendar_result').innerHTML = html;
                var miniCalendarContainer = document.getElementById('eb-minicalendar-container');
                miniCalendarContainer.querySelector('input[name="month_ajax"]').value = month;
                miniCalendarContainer.querySelector('input[name="year_ajax"]').value = year;

                bindEventListener();
            },
            onError: function (error) {
                alert(error.statusText);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', bindEventListener);
})(document, Joomla);