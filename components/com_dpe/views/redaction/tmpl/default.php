<?php
/**
 * @package TJ-UCM
 *
 * @author   TechJoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/pdftronstyle.css');
$document->addStyleSheet(Uri::root() . 'media/com_dpe/css/dropzone.css');

$document->addScript(Uri::root() . 'media/com_dpe/js/pdf.js');

$document->addScript(Uri::root() . 'media/com_dpe/js/jquery-3.7.1.min.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/dropzone.5.5.1.js');
$document->addScript(Uri::root() . 'media/com_dpe/js/jspdf_debug.js');

$user = Factory::getUser();

?>
<header>
	<div class="title pull-left"><?php echo Text::_('COM_DPE_PDF_TRON_HEADER_TITLE'); ?></div>
</div>
<div class="pdftronclose sample pull-right mr-30">
	<button class=""><a href="<?php echo Uri::root(); ?>"><?php echo Text::_('COM_DPE_PDF_TRON_HEADER_CLOSE_TEXT'); ?></a></button>
</div>
<div id="pdf-buttons" class="pdfbuttons">
	<button id="pdf-undo" class='btn 'onclick="undoAction()"><i class="fa fa-undo" aria-hidden="true"></i>
	</button>
	<button id="pdf-redo" class='btn '><i class="fa fa-redo" aria-hidden="true"></i>
	</button>
</header>
<aside>
	<div class="Redaction">
		<h2><?php echo Text::_('COM_DPE_PDF_TRON_SELECT_FILE_LBL'); ?></h2>
		<div class="dropzone dz-single dz-clickable dz-resize" id="dZUpload" >
		<div class="dz-wrapper dz-multiple">
			<div class="dz-message">
				<div class="dz-text btn btn-primary" style="background: #00a5e4;" >Click Here to Upload a File</div>
			</div>
		</div>

	</div>
	<hr />
	<h1><?php echo Text::_('COM_DPE_PDF_TRON_INSTRUCTION'); ?></h1>
	<p><?php echo Text::_('COM_DPE_PDF_TRON_INSTRUCTION_DETAILS'); ?></p>
	<h2><?php //echo Text::_('COM_DPE_PDF_TRON_SEARCH_LBL_REDUCTION'); ?></h2>
	<h2 style="text-align:center;"></h2>
	<div style="min-height:50px">

		<div id="download-image" class="downloadBtn" style="width: 100%;">Download   <i class="fa fa-download" aria-hidden="true"></i>
		</div>
		<br>
		<br>
		<div id="saveas-image" class="downloadBtn" style="width: 100%;">Save as   <i class="fa fa-download" aria-hidden="true"></i>
		</div>
	</div>
</div>
</aside>
<div id="dividion" class="pagecounter">
	<div id="page-count-container">
			<div id="pdf-current-page"> Page 1</div>
			of
			<div id="pdf-total-pages"></div>
		</div></div>

<div id="pdf-main-container">

	<div id="pdf-contents" class="canvasBorder">

		<div id="pdf-meta">

		</div>
		<div id="pdf-loader"></div>
		<canvas id="pdf-canvas"></canvas>
		<div id="page-loader"></div>
	</div>
</div>
<!--ga-tag-->

<script>
	jQuery(document).ready(function() {

		//jQuery('#page-count-container').css('margin-top', '-25px');
		var screenHeight = $(window).height();
		jQuery('#pdf-contents').css('height',screenHeight);
	});

	var __PDF_DOC,
	__CURRENT_PAGE,
	__TOTAL_PAGES,
	__fileUrl,file_Name,
	__PAGE_RENDERING_IN_PROGRESS = 0,
	__CANVAS = $('#pdf-canvas').get(0),
	__CANVAS_CTX = __CANVAS.getContext('2d'),
	renderContext,
	allPages = [];

	const img = new Image();
var storedRects = {}; // Object to store redactions for each canvas
var undoStack = []; // Stack for undo operations
var redoStack = []; // Stack for redo operations

// Function to clean the canvas
function cleanCanvas(){
	__CANVAS_CTX.clearRect(0, 0, __CANVAS_CTX.canvas.width, __CANVAS_CTX.canvas.height);
	img.src = __CANVAS.toDataURL();
}

// Function to handle undo action
function undoAction() {
	if (undoStack.length > 0) {
		const lastAction = undoStack.pop();
		redoStack.push(lastAction);

		if (storedRects[lastAction.canvasId]) {
			storedRects[lastAction.canvasId].pop();
		}
		const pageNumber = parseInt(lastAction.canvasId.replace('pdf-canvas', ''));
		showPage(pageNumber);
	}
}

// Function to handle redo action
function redoAction() {
	if (redoStack.length > 0) {
		const lastUndone = redoStack.pop();
		undoStack.push(lastUndone);

		if (!storedRects[lastUndone.canvasId]) {
			storedRects[lastUndone.canvasId] = [];
		}
		storedRects[lastUndone.canvasId].push(lastUndone.rect);

		const pageNumber = parseInt(lastUndone.canvasId.replace('pdf-canvas', ''));
		showPage(pageNumber);
	}
}


function showPage(page_no, prev) {
	const storedRectsData = storedRects;

	storedRects=[];
	buff = [];


	__PDF_DOC.getPage(page_no).then(function (page) {
		const width = window.screen.availWidth-550;
		var scale_required = width / page.getViewport({ scale: 1 }).width;
		var viewport = page.getViewport({ scale: scale_required });
		const canvas = document.getElementById('pdf-canvas'+ page_no);
		const ctx = canvas.getContext('2d');

		renderContext = {
			canvasContext: ctx,
			viewport: viewport
		};
      // Render the page contents in the canvas
      page.render(renderContext).then(function () {
      	ctx.fillStyle = "rgb(0 0 0)";
      	storedRectsData['pdf-canvas'+ page_no].forEach(rect => rect.draw(ctx));
      	storedRects = storedRectsData;
      })
  });
}

// Function to preload all pages
function preloadAllPages() {
	allPages = [];
	storedRects = {};
	undoStack = [];
	redoStack = [];

	$('#pdf-canvas').remove();
	const pdfContents = document.getElementById('pdf-contents');
	const promises = [];

	for (let i = 1; i <= __TOTAL_PAGES; i++) {
		promises.push(
			__PDF_DOC.getPage(i).then(function(page) {
				const width = window.screen.availWidth - 550;

				const scale_required = width / page.getViewport({ scale: 1 }).width;
				const viewport = page.getViewport({ scale: scale_required });
				const canvas = document.createElement('canvas');
				canvas.id = 'pdf-canvas' + i;
				canvas.className = 'pdf-canvas-class';

				const canvasContext = canvas.getContext('2d');

				canvas.width = width;
				canvas.height = viewport.height;
				__CANVAS.width =window.screen.availWidth - 720;
				__CANVAS.height = viewport.height - 250;
				const renderContext = {
					canvasContext: canvasContext,
					viewport: viewport
				};

				return page.render(renderContext).promise.then(function() {
					allPages.push({ index: i, canvas: canvas });
					initializeCanvas(canvas);
					return canvas;
				});
			})
			);
	}

	Promise.all(promises).then(() => {

		jQuery('#pdf-contents').css('height','auto');
		allPages.sort((a, b) => a.index - b.index);

		allPages.forEach(page => {
			pdfContents.appendChild(page.canvas);
		});

		$('download-image').removeClass('hide');
	});
}

// Next action on the PDF
$("#pdf-redo").on('click', redoAction);

// Download button
$("#download-image").on('click', function() {
	var userId = '<?php echo $user->id;?>';

	if (userId.length == 0)
	{
		return false;
	}
    let width = __CANVAS.width;
    let height = __CANVAS.height;

    if (width > height) {
        pdf = new jsPDF('l', 'px', [width, height]);
    } else {
        pdf = new jsPDF('p', 'px', [height, width]);
    }

    width = pdf.internal.pageSize.getWidth();
    height = pdf.internal.pageSize.getHeight();

    for (let i = 0; i < allPages.length; i++) {
        pdf.addImage(document.getElementById(allPages[i].canvas.id), 'PNG', 0, 0, width, height);
        if (i < (allPages.length - 1)) {
            pdf.addPage();
        }
    }

    
        pdf.save(file_Name);
    
});



$("#saveas-image").on('click', function() {


    let width = __CANVAS.width;
    let height = __CANVAS.height;

    if (width > height) {
        pdf = new jsPDF('l', 'px', [width, height]);
    } else {
        pdf = new jsPDF('p', 'px', [height, width]);
    }

    width = pdf.internal.pageSize.getWidth();
    height = pdf.internal.pageSize.getHeight();

    for (let i = 0; i < allPages.length; i++) {
        pdf.addImage(document.getElementById(allPages[i].canvas.id), 'PNG', 0, 0, width, height);
        if (i < (allPages.length - 1)) {
            pdf.addPage();
        }
    }

    // Prompt the user to enter a file name
    let fileName = prompt("Please enter a name for the file", file_Name);
    if (fileName) {
        if (!fileName.endsWith(".pdf")) {
            fileName += ".pdf";
        }
        pdf.save(fileName);
    }
});


// Function to show the PDF
function showPDF(pdf_url) {
	pdfjsLib.GlobalWorkerOptions.workerSrc = '<?php echo Uri::root(); ?>media/com_dpe/js/pdf.worker.js';
	pdfjsLib.getDocument(pdf_url).promise.then(function(pdf_doc) {
		__PDF_DOC = pdf_doc;
		__TOTAL_PAGES = __PDF_DOC.numPages;
		$("#pdf-total-pages").text(__TOTAL_PAGES);
		preloadAllPages();
	}).catch(function(error) {
		alert(error.message);
	});
}

// Function to hide the canvas
function hideCanvas() {
	$("#pdf-next, #pdf-prev, #pdf-redo, #pdf-undo").attr('disabled', 'disabled');
	$("#pdf-canvas").hide();
	$("#pdf-loader").hide();
	$("#dividion").hide();
	$("#download-image").addClass("disabled");
	$("#saveas-image").addClass("disabled");
}

// Function to show the canvas
function showCanvas() {
	$('.pdf-buttons').show();
	$("#pdf-next, #pdf-prev, #pdf-redo, #pdf-undo").removeAttr('disabled');
	$("#pdf-canvas").show();
	$("#pdf-loader").hide();
	$("#dividion").show();
	$("#download-image").removeClass("disabled");
	$("#page-loader").hide();
	$("#saveas-image").removeClass("disabled");
}

Dropzone.autoDiscover = false;
$(document).ready(function selection() {
	"use strict";
	hideCanvas();
	$("#dZUpload").dropzone({
		url: "upload_holder",
		addRemoveLinks: true,
		acceptedFiles: ".pdf",
		maxFiles: 1,
		success: function(file, response) {
		},
		error: function(file, response) {
		},
		init: function() {
			$("#pdf-loader").show();
			__fileUrl = this.files[0];
			this.on("maxfilesexceeded", function(file) {
				alert("No more files please!");
				this.removeFile(file);
			});
			this.on('addedfile', function(file) {

				if ('application/pdf' != file.type) {
					alert('Error: Not a PDF');
					this.removeFile(file);
				} else {
					file_Name = file.name;
					showPDF(URL.createObjectURL(this.files[0]));
				}
			});
			this.on("removedfile", function(file) {
				if ('application/pdf' == file.type) {
					$('.pdf-canvas-class').remove();
					allPages = [];
					cleanCanvas();
					hideCanvas();
				}
			});
		}
	});
});

// Initialize canvas and handle mouse events
function initializeCanvas(canvas) {
	const ctx = canvas.getContext("2d");
	const baseImage = new Image();

	const rect = (() => {
		var x1, y1, x2, y2;
		var show = false;

		function fix() {
			rect.x = Math.min(x1, x2);
			rect.y = Math.min(y1, y2);
			rect.w = Math.max(x1, x2) - Math.min(x1, x2);
			rect.h = Math.max(y1, y2) - Math.min(y1, y2);
		}

		function draw(ctx) {
			ctx.fillRect(this.x, this.y, this.w, this.h);
		}

		const rect = { x: 0, y: 0, w: 0, h: 0, draw };
		const API = {
			restart(point) {
				x2 = x1 = point.x;
				y2 = y1 = point.y;
				fix();
				show = true;
			},
			update(point) {
				x2 = point.x;
				y2 = point.y;
				fix();
				show = true;
			},
			toRect() {
				show = false;
				return Object.assign({}, rect);
			},
			draw(ctx) {
				if (show) {
					rect.draw(ctx);
				}
			},
			show: false,
		};
		return API;
	})();

	const mouse = {
		button: false,
		x: 0,
		y: 0,
		down: false,
		up: false,
		element: null,
		event(e) {
			const m = mouse;
			const rectCanv = canvas.getBoundingClientRect();
			m.x = (e.clientX - rectCanv.left) / (rectCanv.right - rectCanv.left) * canvas.width;
			m.y = (e.clientY - rectCanv.top) / (rectCanv.bottom - rectCanv.top) * canvas.height;
			const prevButton = m.button;
			m.button = e.type === "mousedown" ? true : e.type === "mouseup" ? false : mouse.button;
			if (!prevButton && m.button) {
				m.down = true;
			}
			if (prevButton && !m.button) {
				m.up = true;
			}
		},
		start(element) {
			mouse.element = element;
			"down,up,move".split(",").forEach(name => document.addEventListener("mouse" + name, mouse.event));
		}
	};

	mouse.start(canvas);

	function draw() {
		ctx.drawImage(baseImage, 0, 0, ctx.canvas.width, ctx.canvas.height);
		ctx.lineWidth = 1;
		ctx.strokeStyle = "black";
		if (storedRects[canvas.id]) {
			storedRects[canvas.id].forEach(rect => rect.draw(ctx));
		}
		ctx.strokeStyle = "red";
		rect.draw(ctx);
	}

	function mainLoop() {
		var refresh = true;
		if (__TOTAL_PAGES == allPages.length) showCanvas();
		if (refresh || mouse.down || mouse.up || mouse.button) {
			refresh = false;
			if (mouse.down) {
				mouse.down = false;
				rect.restart(mouse);
			} else if (mouse.button) {
				rect.update(mouse);
			} else if (mouse.up) {
				mouse.up = false;
				rect.update(mouse);
				let tempRect = rect.toRect();
				const m = mouse;
				if (isFinite(tempRect.x) && isFinite(tempRect.y) && isFinite(tempRect.w) && isFinite(tempRect.h)
					&& tempRect.w != 0 && tempRect.h != 0
					&& m.x > 0 && m.x < canvas.width && m.y > 0 && m.y < canvas.height) {
                    // Only if the mouse lands in the canvas, okay
                if (!storedRects[canvas.id]) {
                	storedRects[canvas.id] = [];
                }


                storedRects[canvas.id].push(tempRect);
                undoStack.push({ canvasId: canvas.id, rect: tempRect });


                    // Clear redo stack
                    redoStack = [];
                }

            }
            draw();
        }
        requestAnimationFrame(mainLoop);
    }

    requestAnimationFrame(mainLoop);
}

$(document).ready(function() {
	$(window).on('scroll', function() {
		let currentCanvasId = null;

		$('canvas').each(function() {
			if (isCanvasInView($(this))) {
				currentCanvasId = $(this).attr('id');
                return false; // Exit the loop once we find the first canvas in view
            }
        });

		if (currentCanvasId) {
			const pageNumber = getPageNumberFromCanvasId(currentCanvasId);

		if (pageNumber == NaN)
    	{
    		pageNumber = 1;
    	}
			updateCurrentPageNumber(pageNumber);
		}
	});

	function isCanvasInView($canvas) {
		const scrollTop = $(window).scrollTop();
		const windowHeight = $(window).height();
		const canvasTop = $canvas.offset().top;
		const canvasHeight = $canvas.height();

		return (
			(canvasTop < (scrollTop + windowHeight)) &&
			((canvasTop + canvasHeight) > scrollTop)
			);
	}

	function getPageNumberFromCanvasId(canvasId) {
        // Extract the page number from the canvas ID (e.g., "pdf-canvas1" -> 1)
        return parseInt(canvasId.replace('pdf-canvas', ''), 10);
    }

    function updateCurrentPageNumber(pageNumber) {


    	
    	$('#pdf-current-page').text('Page ' + pageNumber);
    }
});

</script>


