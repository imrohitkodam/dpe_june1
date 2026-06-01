jQuery(function($) {

	$('.nr_gmap').each(function() {
		el = $(this);
		(function(el) {
			loadMap(el);
		})(el);
	});

	function loadMap(el) {
		var gmap;
		var marker;
		var id          = el.attr('id');
		var coordinates = el.val();
		coordinates     = (coordinates.length) ? coordinates.split(',') : el.data('coordinates').split(',')

		gmap = new google.maps.Map(document.getElementById(id + '_map'), {
			center: {
				lat: parseFloat(coordinates[0]),
				lng: parseFloat(coordinates[1])
			},
			zoom: el.data('zoom')
		});

		if (coordinates.length) {
			marker = new google.maps.Marker({
				position: {
					lat: parseFloat(coordinates[0]),
					lng: parseFloat(coordinates[1])
				},
				map: gmap
			});
		}

		gmap.addListener("click", function(e) {
			placeMarkerAndPanTo(e.latLng, gmap);
		});

		// set a click listener for the Settings tab in order for the google map to re-render correctly
		$(document).on('click', 'a[href="#attrib-brand"]', function(event) {
			refreshMap();
		});

		// Maps need to be re-rendered on hidden divs
		showOn = el.closest(".control-group").data("showon");
		if (showOn !== undefined) {
			$('input[name="' + showOn[0].field + '"]').change(function() {
				refreshMap();
			})
		}

		$(document).on('blur', '#' + id, function(event) {
			if (checkCoordinates(el.val())) {
				newCoordinates = el.val().split(',');
				newCoordinates = new google.maps.LatLng({
					lat: parseFloat(newCoordinates[0]),
					lng: parseFloat(newCoordinates[1])
				});
				placeMarkerAndPanTo(newCoordinates, gmap);
				gmap.panTo(newCoordinates);
			} else {
				alert(Joomla.JText.strings.NR_WRONG_COORDINATES);
				el.val(coordinates.join(','));
			}
		});

		function refreshMap() {
			var center = gmap.getCenter();
			google.maps.event.trigger(gmap, 'resize');
			gmap.setCenter(center);
		}

		function placeMarkerAndPanTo(latLng, map) {
			if (typeof marker == "undefined") {
				marker = new google.maps.Marker({
					position: latLng,
					map: gmap
				});
			}
			marker.setPosition(latLng);
			updateInput(latLng);
		}

		function updateInput(latLng) {
			$("#" + id).val(latLng.lat() + "," + latLng.lng());
		}

		function checkCoordinates(latlng) {
			var pattern = new RegExp(/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/);
			return pattern.test(latlng);
		}
	}
});