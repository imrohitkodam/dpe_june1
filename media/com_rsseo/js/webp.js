document.addEventListener('DOMContentLoaded', function(event) {
	var hasWebpSupport;

	function loadImages() {
		var images = document.querySelectorAll('img');
		var original, webp;

		if (images.length > 0) {
			for (var i = 0; i < images.length; i++) {
				original = images[i].getAttribute('data-orig');
				webp = images[i].getAttribute('data-webp');
				if (original && webp) {
					images[i].setAttribute('src', hasWebpSupport ? webp : original);
				}
			}
		}
	}

	var Tester = new Image();
	Tester.onload = function() {
		hasWebpSupport = Tester.width > 0 && Tester.height > 0;

		loadImages();
	};

	Tester.onerror = function() {
		hasWebpSupport = false;

		loadImages();
	};

	Tester.setAttribute('src', 'data:image/webp;base64,UklGRh4AAABXRUJQVlA4TBEAAAAvAAAAAAfQ//73v/+BiOh/AAA=');
});