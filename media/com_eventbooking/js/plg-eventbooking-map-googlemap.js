(function (document) {
    document.addEventListener('DOMContentLoaded', function () {
        var zoomLevel = Joomla.getOptions('mapZoomLevel');
        var location = Joomla.getOptions('mapLocation');
        var home = new google.maps.LatLng(location.lat, location.long);

        var mapOptions = {
            zoom: zoomLevel,
            streetViewControl: true,
            scrollwheel: Joomla.getOptions('scrollwheel', false),
            center: home,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        var map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);

        var marker = new google.maps.Marker({
            position: home,
            map: map,
            title: location.name
        });

        google.maps.event.trigger(map, "resize");

        var infowindow = new google.maps.InfoWindow({
            content: Joomla.getOptions('bubbleText')
        });

        var openOptions = {
            map: map,
            shouldFocus: false
        };

        google.maps.event.addListener(marker, 'click', function () {
            infowindow.open(openOptions, marker);
        });

        infowindow.open(openOptions, marker);
    });
})(document);