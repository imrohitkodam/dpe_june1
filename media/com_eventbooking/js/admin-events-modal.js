(function (Joomla) {
	window.jSelectEbevent = function (id) {
		const editor = Joomla.getOptions('EBEditor');
		const tag = '{ebevent ' + id + '}';

		window.parent.Joomla.editors.instances[editor].replaceSelection(tag);

		if (Joomla.getOptions('isJoomla4')) {
			window.parent.Joomla.Modal.getCurrent().close();
		} else {
			window.parent.jModalClose();
		}
	};

	window.jSelectEbregister = function (id) {
		const editor = Joomla.getOptions('EBEditor');
		const tag = '{ebregister ' + id + '}';

		window.parent.Joomla.editors.instances[editor].replaceSelection(tag);

		if (Joomla.getOptions('isJoomla4')) {
			window.parent.Joomla.Modal.getCurrent().close();
		} else {
			window.parent.jModalClose();
		}
	};

	document.addEventListener('DOMContentLoaded', function () {
		let functionName;
		// Get the elements
		const elements = document.querySelectorAll('.select-link');

		for (let i = 0, l = elements.length; l > i; i++) {
			// Listen for click event
			elements[i].addEventListener('click', function (event) {
				event.preventDefault();
				functionName = event.target.dataset.function;

				if (functionName === 'jSelectEbevent' || functionName === 'jSelectEbregister') {
					window[functionName](event.target.dataset.id);
				} else {
					window.parent[functionName](event.target.dataset.id, event.target.dataset.title);
				}
			})
		}
	});
})(Joomla);
