(function (document) {
    document.addEventListener('DOMContentLoaded', function(){
        var zoomLevel = Joomla.getOptions('mapZoomLevel');
        var location = Joomla.getOptions('mapLocation');

        var mymap = L.map('map_canvas', {
            center: [location.lat, location.long],
            zoom: zoomLevel,
            zoomControl: true,
            scrollWheelZoom: false
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
            maxZoom: 18,
            id: 'mapbox.streets',
            zoom: zoomLevel,
        }).addTo(mymap);

        var marker = L.marker([location.lat, location.long], {
            draggable: false,
            autoPan: true,
            title: location.name
        }).addTo(mymap);

        marker.bindPopup(location.popupContent);
        marker.openPopup();
    });
})(document);