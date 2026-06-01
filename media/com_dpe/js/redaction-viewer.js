// @link WebViewerInstance: https://www.pdftron.com/api/web/WebViewerInstance.html
// @link WebViewerInstance.loadDocument: https://www.pdftron.com/api/web/WebViewerInstance.html#loadDocument__anchor
// @link WebViewerInstance.disableTools: https://www.pdftron.com/api/web/WebViewerInstance.html#disableTools__anchor
// @link WebViewerInstance.enableTools: https://www.pdftron.com/api/web/WebViewerInstance.html#enableTools__anchor
// @link WebViewerInstance.setToolMode: https://www.pdftron.com/api/web/WebViewerInstance.html#setToolMode__anchor

WebViewer(
  {
	path: Joomla.getOptions('system.paths').base + '/components/com_dpe/includes/WebViewer/lib',
	initialDoc: '',
	fullAPI: true,
	enableRedaction: true,
	extension: "pdf",
	licenseKey: "Data Protection Education Ltd.(dataprotection.education):OEM:Data Protection Education Knowledge Bank - Redaction Tool Module::B+:AMS(20220615):90A5A9AD04F7060A7360B13AC9A2537B60614FCB1E069D639DE515D42C2C1CF65482B6F5C7",
  },
  document.getElementById('viewer')
).then(instance => {
  samplesSetup(instance);


	const { annotManager, docViewer, Annotations, Search } = instance;
	const ResultCode = instance.iframeWindow.XODText.ResultCode;


	document.getElementById('file-picker').onchange = e => {
		const file = e.target.files[0];
		if (file) {
		  instance.loadDocument(file);
		}
	};

	  const toggleElement = (e, dataElement) => {
		// Enable/disable individual element
		if (e.target.checked) {
		  instance.enableElements([dataElement]);
		} else {
		  instance.disableElements([dataElement]);
		}
	  };

	// Disable unwanted elements
	instance.disableFeatures([instance.Feature.NotesPanel]);
	//~ instance.disableElements(['panToolButton']);
	instance.disableElements(['eraserToolButton']);
	instance.disableElements(['cropToolGroupButton']);
	instance.disableElements(['startFormEditingToolGroupButton']);
	instance.disableElements(['leftPanelButton']);
	instance.disableElements(['toolbarGroup-View']);
	instance.disableElements(['toolbarGroup-Annotate']);
	instance.disableElements(['toolbarGroup-Shapes']);
	instance.disableElements(['toolbarGroup-Insert']);
	instance.disableElements(['toolbarGroup-Measure']);
	instance.disableElements(['viewControlsButton']);
	instance.disableElements(['copyTextButton']);
	instance.disableElements(['textHighlightToolButton']);
	instance.disableElements(['textUnderlineToolButton']);
	instance.disableElements(['textSquigglyToolButton']);
	instance.disableElements(['annotationStyleEditButton']);
	instance.disableElements(['annotationDeleteButton']);
	instance.disableElements(['linkButton']);
	instance.disableElements(['textStrikeoutToolButton']);
	instance.disableElements(['searchButton']);
	instance.disableElements(['themeChangeButton']);
	instance.disableElements(['contextMenuPopup']);
	instance.disableElements(['selectToolButton']);
	instance.disableElements(['panToolButton']);
	instance.disableElements(['toolbarGroup-Edit']);


  docViewer.on('documentLoaded', () => {

 const searchListener = (searchPattern, options, results) => {
	// add redaction annotation for each search result
	const newAnnotations = results.map(result => {
	  const annotation = new Annotations.RedactionAnnotation();
	  annotation.PageNumber = result.pageNum;
	  annotation.Quads = result.quads.map(quad => quad.getPoints());
	  annotation.StrokeColor = new Annotations.Color(136, 39, 31);
	  return annotation;
	});

	annotManager.addAnnotations(newAnnotations);
	annotManager.drawAnnotationsFromList(newAnnotations);
  };

	document.getElementById('search-text').onclick = () => {
	var searchText = document.getElementById("SearchText").value;

	const searchPattern = searchText;
	// searchPattern can be something like "search*m" with "wildcard" option set to true

	// options default values are false
	const searchOptions = {
	  caseSensitive: false,  // match case
	  wholeWord: true,      // match whole words only
	  wildcard: false,      // allow using '*' as a wildcard value
	  regex: false,         // string is treated as a regular expression
	  searchUp: false,      // search from the end of the document upwards
	  ambientString: true,  // return ambient string as part of the result
	};

	// start search after document loads
	instance.addSearchListener(searchListener);
	instance.searchTextFull(searchPattern, searchOptions);
	}

  docViewer.setSearchHighlightColors({
	// setSearchHighlightColors accepts both Annotations.Color objects or 'rgba' strings
	searchResult: new Annotations.Color(0, 0, 255, 0.5),
	activeSearchResult: 'rgba(0, 255, 0, 0.5)'
  });


	document.getElementById('search-pattern').onclick = () => {

	var searchText = document.getElementById("pattern").value;

	const searchPattern = searchText;
	// searchPattern can be something like "search*m" with "wildcard" option set to true

	// options default values are false
	const searchOptions = {regex: true};

	// start search after document loads
	instance.addSearchListener(searchListener);
	instance.searchTextFull(searchPattern, searchOptions);
	}

	document.getElementById('downloadpdftron').onclick = () => {
	instance.downloadPdf({
			includeAnnotations: true,
			flatten: true,
		  });
	}

	document.getElementById('apply-redactions').onclick = () => {
	  instance.showWarningMessage({
		title: 'Apply redaction?',
		message: 'This action will permanently remove all items selected for ' + 'redaction. It cannot be undone.',
		onConfirm: () => {
		  instance.docViewer.getAnnotationManager().applyRedactions();
		  return Promise.resolve();
		},
	  });
	};
  });

  instance.setToolbarGroup('toolbarGroup-Edit');
  instance.setToolMode('AnnotationCreateRedaction');
});
