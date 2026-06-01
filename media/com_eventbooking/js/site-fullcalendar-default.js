(function (document, Joomla) {
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('eb_full_calendar');
        var calendarOptions = Joomla.getOptions('calendarOptions');

        if (Joomla.getOptions('displayEventInTooltip')) {
            calendarOptions['eventDidMount'] = function (arg) {
                if (arg.event.extendedProps.tooltip) {
                    var element = jQuery(arg.el);
                    element.tooltip({
                        title: arg.event.extendedProps.tooltip,
                        trigger: 'hover',
                        placement: 'top',
                        container: 'body',
                        html: true,
                        sanitize: false
                    });
                }

                if (arg.event.extendedProps.eventFull) {
                    var eventContainerEl = arg.el.querySelector('.fc-event-title');

                    if (eventContainerEl) {
                        eventContainerEl.classList.add('eb-event-full');
                    }
                }
            }
        }

        var calendar = new FullCalendar.Calendar(calendarEl, calendarOptions);
        calendar.render();
    });
})(document, Joomla);