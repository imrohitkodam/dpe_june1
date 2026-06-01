<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$document = Factory::getDocument();
$pluginUrl = Juri::root(true) . '/plugins/' . $this->_type . '/' . $this->_name;
$pluginLibUrl = $pluginUrl . '/pdfjs';
$document->addStyleSheet($pluginUrl . '/pdfjs/web/viewer.css');
$document->addCustomTag('<link rel="resource" type="application/l10n" href="' . $pluginUrl . '/pdfjs/web/locale/locale.properties">');
?><div id="outerContainer">

	<div id="loadingBar">
		<div class="progress">
			<div class="glimmer">
			</div>
		</div>
	</div>
    <div id="sidebarContainer">
        <div id="toolbarSidebar">
            <div class="splitToolbarButton toggled">
                <button id="viewThumbnail" class="toolbarButton toggled" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_THUMBNAIL_DESC')?>" tabindex="2" data-l10n-id="thumbs">
                    <span data-l10n-id="thumbs_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_THUMBNAIL')?></span>
                </button>
                <button id="viewOutline" class="toolbarButton" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOCUMENT_OUTLINE_DESC')?>" tabindex="3" data-l10n-id="document_outline">
                    <span data-l10n-id="document_outline_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOCUMENT_OUTLINE')?></span>
                </button>
                <button id="viewAttachments" class="toolbarButton" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ATTACHMENTS_DESC')?>" tabindex="4" data-l10n-id="attachments">
                    <span data-l10n-id="attachments_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ATTACHMENTS')?></span>
                </button>
            </div>
        </div>
        <div id="sidebarContent">
            <div id="thumbnailView">
            </div>
            <div id="outlineView" class="hidden">
            </div>
            <div id="attachmentsView" class="hidden">
            </div>
        </div>
    </div>
    <!-- sidebarContainer -->

    <div id="mainContainer">
        <div class="findbar hidden doorHanger" id="findbar">
            <div id="findbarInputContainer">
                <input id="findInput" class="toolbarField" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND')?>" placeholder="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_DESC')?>" tabindex="91" data-l10n-id="find_input">
                <div class="splitToolbarButton">
                    <button id="findPrevious" class="toolbarButton findPrevious fa fa-arrow-left" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_PREV_DESC')?>" tabindex="92" data-l10n-id="find_previous">
                        <span data-l10n-id="find_previous_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_PREV')?></span>
                    </button>
                    <div class="splitToolbarButtonSeparator"></div>
                    <button id="findNext" class="toolbarButton findNext fa fa-arrow-right" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_NEXT_DESC')?>" tabindex="93" data-l10n-id="find_next">
                        <span data-l10n-id="find_next_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_NEXT')?></span>
                    </button>
                </div>
            </div>

            <div id="findbarOptionsContainer">
                <input type="checkbox" id="findHighlightAll" class="toolbarField" tabindex="94">
                <label for="findHighlightAll" class="toolbarLabel" data-l10n-id="find_highlight"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_HIGHLIGHT_ALL')?></label>
                <input type="checkbox" id="findMatchCase" class="toolbarField" tabindex="95">
                <label for="findMatchCase" class="toolbarLabel" data-l10n-id="find_match_case_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_NEXT')?></label>
                <span id="findResultsCount" class="toolbarLabel hidden"></span>
            </div>

            <div id="findbarMessageContainer">
                <span id="findMsg" class="toolbarLabel"></span>
            </div>
        </div>
        <!-- findbar -->

        <div id="secondaryToolbar" class="secondaryToolbar hidden doorHangerRight">
            <div id="secondaryToolbarButtonContainer">
                <button id="secondaryPresentationMode" class="secondaryToolbarButton presentationMode visibleLargeView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRESENTATION_MODE_DESC')?>" tabindex="51" data-l10n-id="presentation_mode">
                    <span data-l10n-id="presentation_mode_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRESENTATION_MODE')?></span>
                </button>

                <button id="secondaryOpenFile" class="secondaryToolbarButton openFile visibleLargeView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_OPEN_DESC')?><" tabindex="52" data-l10n-id="open_file">
                    <span data-l10n-id="open_file_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_OPEN')?><</span>
                </button>

                <button id="secondaryPrint" class="secondaryToolbarButton print visibleMediumView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRINT')?>" tabindex="53" data-l10n-id="print">
                    <span data-l10n-id="print_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRINT_DESC')?></span>
                </button>

                <button id="secondaryDownload" class="secondaryToolbarButton download visibleMediumView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOWNLOAD_DESC')?>" tabindex="54" data-l10n-id="download">
                    <span data-l10n-id="download_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOWNLOAD')?></span>
                </button>

                <a href="#" id="secondaryViewBookmark" class="secondaryToolbarButton bookmark visibleSmallView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CURRENT_VIEW_DESC')?>" tabindex="55" data-l10n-id="bookmark">
                    <span data-l10n-id="bookmark_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CURRENT_VIEW')?></span>
                </a>

                <div class="horizontalToolbarSeparator visibleLargeView"></div>


                <div class="horizontalToolbarSeparator"></div>

                <button id="documentProperties" class="secondaryToolbarButton documentProperties" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOCUMENT_PROPERTIES_DESC')?>" tabindex="62" data-l10n-id="document_properties">
                    <span data-l10n-id="document_properties_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOCUMENT_PROPERTIES')?></span>
                </button>
            </div>
        </div>
        <!-- secondaryToolbar -->

        <div class="toolbar">
            <div id="toolbarContainer">
                <div id="toolbarViewer">
                    <div id="toolbarViewerLeft">
                        <button id="sidebarToggle" class="toolbarButton" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_TOGGLE_SIDEBAR_DESC')?>" tabindex="11" data-l10n-id="toggle_sidebar">
                            <span data-l10n-id="toggle_sidebar_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_TOGGLE_SIDEBAR')?></span>
                        </button>
                        <div class="toolbarButtonSpacer"></div>
                        <button id="viewFind" class="toolbarButton fa fa-search" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND_DESC')?>" tabindex="12" data-l10n-id="findbar">
                            <span data-l10n-id="findbar_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIND')?></span>
                        </button>
                        <div class="splitToolbarButton hiddenSmallView">
                            <button class="toolbarButton pageUp fa fa-backward" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PREVIOUS_DESC')?>" id="previous" tabindex="13" data-l10n-id="previous">
                                <span data-l10n-id="previous_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PREVIOUS')?></span>
                            </button>
                            <div class="splitToolbarButtonSeparator"></div>
                            <button class="toolbarButton pageDown fa fa-forward" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_NEXT_DESC')?>" id="next" tabindex="14" data-l10n-id="next">
                                <span data-l10n-id="next_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_NEXT')?></span>
                            </button>
                        </div>
                        <input type="number" id="pageNumber" class="toolbarField pageNumber" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PAGE')?>" value="1" size="4" min="1" tabindex="15" data-l10n-id="page">
                        <span id="numPages" class="toolbarLabel"></span>
                    </div>
                    <div id="toolbarViewerRight">

                        <button id="openFile" class="toolbarButton openFile hiddenLargeView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_OPEN')?>" tabindex="32" data-l10n-id="open_file">
                            <span data-l10n-id="open_file_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_OPEN_DESC')?></span>
                        </button>

                        <button id="print" class="toolbarButton print hiddenMediumView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRINT_DESC')?>" tabindex="33" data-l10n-id="print">
                            <span data-l10n-id="print_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRINT')?></span>
                        </button>

                        <button id="download" class="toolbarButton download hiddenMediumView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOWNLOAD_DESC')?>" tabindex="34" data-l10n-id="download">
                            <span data-l10n-id="download_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_DOWNLOAD')?></span>
                        </button>
                        <a href="#" id="viewBookmark" class="toolbarButton bookmark hiddenSmallView" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CURRENT_VIEW_DESC')?>" tabindex="35" data-l10n-id="bookmark">
                            <span data-l10n-id="bookmark_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CURRENT_VIEW')?></span>
                        </a>

                        <div class="verticalToolbarSeparator hiddenSmallView hidden"></div>

						<button id="firstPage" class="toolbarButton firstPage fa fa-fast-backward" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_GTFP')?>" tabindex="56" data-l10n-id="first_page">
						</button>
						<button id="lastPage" class="toolbarButton lastPage fa fa-fast-forward" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_GTLP')?>" tabindex="57" data-l10n-id="last_page">
						</button>

						<div class="verticalToolbarSeparator"></div>

						<button id="pageRotateCw" class="toolbarButton rotateCw fa fa-repeat" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ROTATE')?>" tabindex="58" data-l10n-id="page_rotate_cw">
						</button>
						<button id="pageRotateCcw" class="toolbarButton rotateCcw fa fa-undo" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ROTATE_COUNTER')?>" tabindex="59" data-l10n-id="page_rotate_ccw">
						</button>

						<div class="verticalToolbarSeparator"></div>

						<button id="cursorSelectTool" class="toolbarButton selectTool toggled" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_TEXT_SELECTION')?>" tabindex="60" data-l10n-id="cursor_text_select_tool">
						</button>
						<button id="cursorHandTool" class="toolbarButton handTool" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_HAND_SELECTION')?>" tabindex="61" data-l10n-id="cursor_hand_tool">
						</button>
						<div class="verticalToolbarSeparator hiddenSmallView hidden"></div>
                        <button id="presentationMode" class="toolbarButton presentationMode hiddenLargeView fa fa-arrows-alt" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRESENTATION_MODE_DESC')?>" tabindex="31" data-l10n-id="presentation_mode">
                            <span data-l10n-id="presentation_mode_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PRESENTATION_MODE')?></span>
                        </button>
                        <button id="secondaryToolbarToggle" class="toolbarButton" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_TOOLS_DESC')?>" tabindex="36" data-l10n-id="tools">
                            <span data-l10n-id="tools_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_TOOLS')?></span>
                        </button>
                    </div>
                    <div id="toolbarViewerMiddle">
                        <div class="splitToolbarButton">
                            <button id="zoomIn" class="toolbarButton zoomIn fa fa-search-plus" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_IN_DESC')?>" tabindex="22" data-l10n-id="zoom_in">
                                <span data-l10n-id="zoom_in_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_IN')?></span>
                            </button>
                            <div class="splitToolbarButtonSeparator"></div>
                            <button id="zoomOut" class="toolbarButton zoomOut fa fa-search-minus" title="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_OUT_DESC')?>" tabindex="21" data-l10n-id="zoom_out">
                                <span data-l10n-id="zoom_out_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_OUT')?></span>
                            </button>
                        </div>
                        <span id="scaleSelectContainer" class="dropdownToolbarButton">
                  <select id="scaleSelect" title="Zoom" tabindex="23" data-l10n-id="zoom">
                    <option id="pageAutoOption" title="" value="auto" selected="selected" data-l10n-id="page_scale_auto">
						<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_AUTOMATIC_ZOOM')?>
                    </option>
                    <option id="pageActualOption" title="" value="page-actual" data-l10n-id="page_scale_actual">
						<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_ACTUAL_SIZE')?>
                    </option>
                    <option id="pageFitOption" title="" value="page-fit" data-l10n-id="page_scale_fit">
						<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_PAGE_FIT')?>
                    </option>
                    <option id="pageWidthOption" title="" value="page-width" data-l10n-id="page_scale_width">
						<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ZOOM_PAGE_WIDTH')?>
                    </option>
                    <option id="customScaleOption" title="" value="custom" disabled="disabled" hidden="true"></option>
                    <option title="" value="0.5" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 50 }'>50%</option>
                    <option title="" value="0.75" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 75 }'>75%</option>
                    <option title="" value="1" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 100 }'>100%</option>
                    <option title="" value="1.25" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 125 }'>125%</option>
                    <option title="" value="1.5" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 150 }'>150%</option>
                    <option title="" value="2" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 200 }'>200%</option>
                    <option title="" value="3" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 300 }'>300%</option>
                    <option title="" value="4" data-l10n-id="page_scale_percent" data-l10n-args='{ "scale": 400 }'>400%</option>
                  </select>
                </span>
                    </div>
                </div>
            </div>
        </div>

        <menu type="context" id="viewerContextMenu">
            <menuitem id="contextFirstPage" label="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FIRST_PAGE')?>" data-l10n-id="first_page"></menuitem>
            <menuitem id="contextLastPage" label="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_LAST_PAGE')?>" data-l10n-id="last_page"></menuitem>
            <menuitem id="contextPageRotateCw" label="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ROTATE')?>" data-l10n-id="page_rotate_cw"></menuitem>
            <menuitem id="contextPageRotateCcw" label="<?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ROTATE_COUNTER')?>" data-l10n-id="page_rotate_ccw"></menuitem>
        </menu>

        <div id="viewerContainer" tabindex="0">
            <div id="viewer" class="pdfViewer"></div>
        </div>

        <div id="errorWrapper" hidden='true'>
            <div id="errorMessageLeft">
                <span id="errorMessage"></span>
                <button id="errorShowMore" data-l10n-id="error_more_info">
                    More Information
                </button>
                <button id="errorShowLess" data-l10n-id="error_less_info" hidden='true'>
                    Less Information
                </button>
            </div>
            <div id="errorMessageRight">
                <button id="errorClose" data-l10n-id="error_close">
                    Close
                </button>
            </div>
            <div class="clearBoth"></div>
            <textarea id="errorMoreInfo" hidden='true' readonly="readonly"></textarea>
        </div>
    </div>
    <!-- mainContainer -->

    <div id="overlayContainer" class="hidden">
        <div id="passwordOverlay" class="container hidden">
            <div class="dialog">
                <div class="row">
                    <p id="passwordText" data-l10n-id="password_label"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_ENTER_PASSWORD')?></p>
                </div>
                <div class="row">
                    <input type="password" id="password" class="toolbarField">
                </div>
                <div class="buttonRow">
                    <button id="passwordCancel" class="overlayButton"><span data-l10n-id="password_cancel"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CANCEL')?></span>
                    </button>
                    <button id="passwordSubmit" class="overlayButton"><span data-l10n-id="password_ok"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_OK')?></span>
                    </button>
                </div>
            </div>
        </div>
        <div id="documentPropertiesOverlay" class="container hidden">
            <div class="dialog">
                <div class="row">
                    <span data-l10n-id="document_properties_file_name"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FILE_NAME')?></span>
                    <p id="fileNameField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_file_size"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_FILE_SIZE')?></span>
                    <p id="fileSizeField">-</p>
                </div>
                <div class="separator"></div>
                <div class="row">
                    <span data-l10n-id="document_properties_title"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_TITLE')?></span>
                    <p id="titleField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_author"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_AUTHOR')?></span>
                    <p id="authorField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_subject"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_SUBJECT')?></span>
                    <p id="subjectField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_keywords"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_KEYWORDS')?></span>
                    <p id="keywordsField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_creation_date"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CREATION_DATE')?></span>
                    <p id="creationDateField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_modification_date"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_MODIFICATION_DATE')?></span>
                    <p id="modificationDateField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_creator"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CREATOR')?></span>
                    <p id="creatorField">-</p>
                </div>
                <div class="separator"></div>
                <div class="row">
                    <span data-l10n-id="document_properties_producer"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PDF_PRODUCER')?></span>
                    <p id="producerField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_version"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PDF_VERSION')?></span>
                    <p id="versionField">-</p>
                </div>
                <div class="row">
                    <span data-l10n-id="document_properties_page_count"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PAGE_COUNT')?></span>
                    <p id="pageCountField">-</p>
                </div>
                <div class="buttonRow">
                    <button id="documentPropertiesClose" class="overlayButton"><span data-l10n-id="document_properties_close"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CLOSE')?></span>
                    </button>
                </div>
            </div>
        </div>
        <div id="printServiceOverlay" class="container hidden">
            <div class="dialog">
                <div class="row">
                    <span data-l10n-id="print_progress_message"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_PREPARING_PRINTING')?></span>
                </div>
                <div class="row">
                    <progress value="0" max="100"></progress>
                    <span data-l10n-id="print_progress_percent" data-l10n-args='{ "progress": 0 }' class="relative-progress">0%</span>
                </div>
                <div class="buttonRow">
                    <button id="printCancel" class="overlayButton"><span data-l10n-id="print_progress_close"><?php echo Text::_('PLG_TJDOCUMENT_PDFVIEWER_CLOSE')?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- overlayContainer -->

</div>
<!-- outerContainer -->
<div id="printContainer"></div>
<script>
var tjPdfViewer = typeof tjPdfViewer == "undefined" ? {} : tjPdfViewer;
tjPdfViewer.tjDocPdfUrl = '<?php echo $data['source']?>';
tjPdfViewer.tjDocLibUrl = '<?php echo $pluginLibUrl?>';
tjPdfViewer.docData = <?php echo json_encode($data)?>;
location.hash= "page=" + <?php echo (isset($data['current']) && $data['current']) ? $data['current'] : 1?>;
jQuery(document).ready(function(){
	if (parent.document.getElementById('sbox-content') && parent.document.getElementById('sbox-content').childNodes.length)
	{
		parent.document.getElementById('sbox-content').childNodes[0].height = "100%";
	}
})
</script>
<script src="<?php echo $pluginUrl . '/pdfjs/build/pdf.js';?>"></script>
<script src="<?php echo $pluginUrl . '/pdfjs/web/viewer.js';?>"></script>
