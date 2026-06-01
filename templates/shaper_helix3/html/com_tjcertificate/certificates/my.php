<?php
/**
 * @package     TJCertificate
 * @subpackage  com_tjcertificate
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal', 'a.modal');
HTMLHelper::_('jquery.token');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder == 'ci.id';
PluginHelper::importPlugin('content');

$options['relative'] = true;

HTMLHelper::script('media/com_tjcertificate/vendors/html2canvas/js/html2canvas.js');
HTMLHelper::script('media/com_tjcertificate/vendors/loader/js/loadingoverlay.min.js');

HTMLHelper::_('script', 'com_tjcertificate/tjCertificateService.min.js', $options);
HTMLHelper::_('script', 'com_tjcertificate/certificate.min.js', $options);
HTMLHelper::StyleSheet('media/com_tjcertificate/css/tjCertificate.css');
HTMLHelper::script('com_tjcertificate/certificateImage.min.js', $options);


if (!$this->user->authorise('core.manageall', 'com_cluster'))
{
	if (!RBACL::check($this->user->id, 'com_cluster', 'core.viewShika', 'com_tjlms'))
	{
		$app = Factory::getApplication();

		$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'));

		return;
	}
}

$certificateStatusArr = array(Text::_('COM_TJCERTIFICATE_CERTIFICATE_PENDING_TEXT') => -1, Text::_('JUNPUBLISHED') => 0, Text::_('JPUBLISHED') => 1);


// Get create cetificate MenuItem
$mainframe          = Factory::getApplication();
$menu               = $mainframe->getMenu();



$cetificateMenuItem = $menu->getItems('link', 'index.php?option=com_tjcertificate&view=certificate', true );
$trainingrecordMenuItem = $menu->getItems('link', 'index.php?option=com_tjcertificate&view=trainingrecord&layout=edit', true );
$this->layout = 'my';
$this->option = 'com_tjcertificate';
$this->view = 'certificates';
$this->Itemid = $menu ->getActive()->id;


$client = $this->state->get('filter.client');
$agencyId = $this->state->get('filter.agency_id');    
$state =   $this->state->get('filter.state'); 

JHtml::_('jquery.framework'); // Ensure jQuery is loaded

// JHtml::script('media/system/js/mootools-core.js', array('version' => 'auto', 'relative' => true));
// JHtml::script('media/system/js/mootools-more.js', array('version' => 'auto', 'relative' => true));
JHtml::script('media/system/js/modal.js', array('version' => 'auto', 'relative' => true));
JHtml::stylesheet('media/system/css/modal.css', array('version' => 'auto', 'relative' => true));
?>
<script>
	const certRootUrl = "<?php echo Uri::root(); ?>";

	   function uploadImage(image, certificateId) {
	var result = false;
	jQuery.ajax({
		url: certRootUrl + "index.php?option=com_tjcertificate&task=certificate.uploadCertificate",
		type: 'POST',
		data: {
			image: image,
			certificateId: certificateId
		},
		success: function(data) {
			result = data;

			setTimeout(function () {
				if (screen.width < 1200) {
					const viewport = document.querySelector("meta[name=viewport]");
					if (viewport) {
						viewport.setAttribute("content", "width=device-width");
					}
				}
				jQuery.LoadingOverlay("hide");
			}, 1000);
		}
	});

	return result; // Note: This won’t contain the actual result due to async behavior
}

function generateImage($html, $item) {
	return new Promise((resolve, reject) => {
		// Ensure jQuery and html2canvas are available
		if (typeof jQuery === 'undefined' || typeof html2canvas === 'undefined') {
			console.error('jQuery or html2canvas is not available');
			reject('Missing dependencies');
			return;
		}

		html2canvas($html, {
			scrollX: 0,
			scrollY: -window.scrollY,
			allowTaint: true,
			useCORS: true
		}).then(function(canvas) {
			const imageData = canvas.toDataURL('image/png');

			jQuery.ajax({
				url: certRootUrl + "index.php?option=com_tjcertificate&task=certificate.uploadCertificate",
				type: 'POST',
				data: {
					image: imageData,
					certificateId: $item 
				},
				success: function(data) {
					setTimeout(function () {
				if (screen.width < 1200) {
					const viewport = document.querySelector("meta[name=viewport]");
					if (viewport) {
						viewport.setAttribute("content", "width=device-width");
					}
				}
				jQuery.LoadingOverlay("hide");
			}, 1000);
					console.log('Uploaded certificate: ' + $item);
					resolve();
				},
				error: function(xhr) {
					console.error("Upload failed for certificate " + $item, xhr.responseText);
					reject(xhr.responseText);
				}
			});

		}).catch(function(error) {
			console.error("html2canvas failed:", error);
			reject(error);
		});
	});
}

function checkImageExists(imageUrl) {
  return new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    xhr.open('HEAD', imageUrl, true);

    xhr.onload = function () {
      resolve(xhr.status === 200);
    };

    xhr.onerror = function () {
      resolve(false);
    };

    xhr.send();
  });
}

</script>


<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#bulkCertBtn').on('click', function(e) {
        e.preventDefault();


        // Show loader
        $('#loader').fadeIn();	

        $.ajax({
            url: 'index.php?option=com_tjcertificate&task=certificates.fetchCertificatesForBulkDownload',
            type: 'POST',
            dataType: 'json',
            data: {
                agency_id: '<?php echo $agencyId; ?>',
                client: '<?php echo $client; ?>',
                state: '<?php echo $state; ?>',
                '<?php echo JSession::getFormToken(); ?>': 1
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    let html = '<h4>Downloaded Certificates</h4><ul>';
                    $('#certificateModalOverlay').fadeIn();


                    response.data.forEach(function(cert) {
                        html += '<div id="certificateContent' + cert.id + '" style="width: 1196px; height: auto;">' + cert.generated_body + '</div>';
                    });

                    $('#certificateModalContent').html(html);


                    // Generate certificate images
					const generatePromises = response.data.map(cert => {
						const imageUrl = `${certRootUrl}media/com_tjcertificate/certificates/${cert.unique_certificate_id}.png`;
						const certEl = document.getElementById('certificateContent' + cert.id);

							return checkImageExists(imageUrl).then(exists => {
								if (exists) {
								return Promise.resolve();  // Image exists, skip generating
								} else {
								return generateImage(certEl, cert.unique_certificate_id); // Image does not exist, generate it
								}
							});
						});


					Promise.all(generatePromises).then(() => {
					
						// Create and submit ZIP download form
						const form = $('<form>', {
							method: 'POST',
							action: 'index.php?option=com_tjcertificate&task=certificates.bulkCertificateDownload',
							style: 'display: none;'
						});

						form.append($('<input>', {
							type: 'hidden',
							name: 'agency_id',
							value: '<?php echo $agencyId; ?>'
						}));

						response.data.forEach(function(cert, index) {
							form.append($('<input>', {
								type: 'hidden',
								name: 'certificates[' + index + '][unique_certificate_id]',
								value: cert.unique_certificate_id
							}));
						});

						form.append($('<input>', {
							type: 'hidden',
							name: '<?php echo JSession::getFormToken(); ?>',
							value: 1
						}));

						$('body').append(form);

						 // Hide loader right before triggering download
						 $('#loader').fadeOut();

						form.submit();
						form.remove();

						$('#certificateModalContent').html(
							'<div class="alert alert-success">Certificates downloaded successfully.</div>'

						);
						setTimeout(() => {
							$('#certificateModalOverlay').fadeOut();
						}, 2000);

					});

                } else {
					$('#loader').fadeOut();
                    alert(response.message || 'No certificates found.');
                }
            },
            error: function(xhr) {
				$('#loader').fadeOut();
                console.error("AJAX Error", xhr.responseText);
            }
        });
    });


    // Close modal
    $('#closeModalBtn').on('click', function() {
        $('#certificateModalOverlay').fadeOut();
    });

});


</script>
<div id="ziploader" style="display: none;">
    <div class="zipspinner">Loading...</div>
</div>
<!-- Custom Modal -->
<div id="certificateModalOverlay">
  <div id="certificateModalContainer">
	<button id="closeModalBtn">×</button>
    <div id="certificateModalContent" style="padding-top: 20px;">
      <!-- AJAX content will be injected here -->
    </div>
  </div>
</div>

<div class="tj-page tjBs3 certificates-list">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcertificate&view=certificates&layout=my&Itemid='.$menu->getActive()->id); ?>" method="post" name="adminForm" id="adminForm">
			<div class="tj-search-filters mb-3">
				<?php
			// Search tools bar
				echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));

				if( $this->user->authorise('core.manageall', 'com_cluster') || $this->manage){
					?>
				</div>



				<a href=""
					id="bulkCertBtn"
					class="btn btn-primary bulkcertbtn">
					<i class="fa fa-download" aria-hidden="true"></i>
					<?php echo Text::_("COM_TJCERTIFICATE_BULK_CERTIFICATE_DOWNLOAD"); ?>
					</a>


			<?php }
			if ($this->create)
			{
				$recordFormLink = 'index.php?option=com_tjcertificate&view=trainingrecord&layout=edit';
				$addRecordLink = Route::_($recordFormLink . '&Itemid=' . $trainingrecordMenuItem->id);?>
				<div>
					<a class="btn btn-primary btn-small pull-right mb-4" href="<?php echo $addRecordLink;?>">
						<i class="fa fa-plus me-2"></i><?php echo Text::_('COM_TJCERTIFICATE_ADD_EXTERNAL_CERTIFICATE'); ?>
					</a>
				</div>
				<?php
			}
			?>
			<?php
			if ($this->manage)
			{
				$recordsFormLink = 'index.php?option=com_tjcertificate&view=bulktrainingrecord&layout=edit';
				$addRecordsLink = Route::_($recordsFormLink);?>
				<div>
					<a class="btn btn-primary btn-small pull-right mb-4 me-3" href="<?php echo $addRecordsLink;?>">
						<i class="fa fa-plus me-2"></i><?php echo Text::_('COM_TJCERTIFICATE_ADD_EXTERNAL_CERTIFICATES'); ?>
					</a>
				</div>
				<?php
			}
			?>
			<div class="clearfix"></div>
			<?php
			if (empty($this->items))
			{
				?>
				<div class="alert alert-info alert-no-items ">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
				<?php
			}
			else
			{
				?>
				<table class="table table-striped" id="certificateList">
					<thead>
						<tr>
							<!-- <th width="1%" class="nowrap center hidden-phone"></th> -->
							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_CERTIFICATE_ID'); ?>
							</th>
							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_TYPE'); ?>
							</th>
							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_NAME'); ?>
							</th>

							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_USERNAME'); ?>
							</th>
							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_USER_EMAIL'); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_ISSUED_DATE', 'ci.issued_on', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJCERTIFICATE_CERTIFICATE_LIST_VIEW_EXPIRY_DATE', 'ci.expired_on', $listDirn, $listOrder); ?>
							</th>
							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_STATUS'); ?>
							</th>
							<th>
								<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_ACTIONS'); ?>
							</th>
						</tr>

					</thead>
<!--
						<tfoot>
							<tr>
								<td colspan="10">
									<?php //echo $this->pagination->getListFooter(); ?>
								</td>
							</tr>
						</tfoot>
					-->
					<tbody>
						<?php
						$urlOpts = array ();
						$urlOpts['popup'] = true;

						foreach ($this->items as $i => $item)
						{
							$certificateObj = TJCERT::Certificate($item->id);
							$data = Factory::getApplication()->triggerEvent('onGetCertificateClientData', array($item->client_id, $item->client));
							?>

							<tr class="<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->id; ?>">
								<td class="has-context">
									<div class="pull-left break-word">
										<?php if (!$item->is_external) {?>
											<a href="<?php echo $certificateObj->getUrl('',false); ?>">
												<?php echo $this->escape($item->unique_certificate_id); ?>
											</a>
										<?php } ?>
										<?php if ($item->is_external) {?>
											<a href="<?php echo $certificateObj->getUrl('',false, true); ?>">
												<?php echo $this->escape($item->unique_certificate_id); ?>
											</a>
										<?php } ?>
									</div>
								</td>
								<td>
									<?php
									$client = str_replace(".", "_", $item->client);
									$client = strtoupper("COM_TJCERTIFICATE_CLIENT_" . $client);
									echo TEXT::_($client);
									?>
								</td>
								<td>
									<?php
									if ($item->is_external)
									{
										echo $item->name;
									}
									else
									{
										echo ($data[0]->title ? $data[0]->title : "-");
									}
									?>
								</td>
								<td><?php echo $item->uname; ?></td>
								<td><?php echo $item->email; ?></td>
								<td><?php echo $certificateObj->getFormatedDate($item->issued_on); ?></td>
								<td>
									<?php
									if (!empty($item->expired_on) && $item->expired_on != '0000-00-00 00:00:00')
									{
										echo $certificateObj->getFormatedDate($item->expired_on);
									}
									else
									{
										echo '-';
									}
									?>
								</td>

								<td>
									<?php
									echo $certificateStatus = array_search($item->state, $certificateStatusArr);
									?>
								</td>

								<td>
									<div class="hide">
										<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
									</div>
									<!-- If user have manage permission then permission to edit and delete any record -->
									<?php 
									$editLink = Route::_('index.php?option=com_tjcertificate&view=trainingrecord&layout=edit&id=' . $item->id ).'&Itemid=' . $trainingrecordMenuItem->id;

									if ($this->manage && $item->is_external)
										{ ?>
											<a class="d-inline-block" href="<?php echo $editLink; ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
												<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
											</a>
											<?php
												// If user have delete all permission then can delete all records
											if ($this->delete)
											{
												?>
												<a class="d-inline-block p-1" onclick="certificate.deleteItem('<?php echo $item->id; ?>', this)" data-message="<?php echo Text::_('COM_TJCERTIFICATE_DELETE_CERTIFICATE_MESSAGE');?>" class="btn btn-mini delete-button" type="button" title="<?php echo Text::_('JACTION_DELETE'); ?>"><i class="fa fa-trash-o"></i>
													<?php
												}
												?>
												<?php
											} ?>

											<?php
											if ((!$this->manage && $this->manageOwn) && ($item->is_external && $item->state != 1))
												{ ?>
													<a class="d-inline-block" href="<?php echo $editLink; ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
														<i class="fa fa-pencil-square-o" aria-hidden="true"></i>
													</a>
													<?php
													if ($this->deleteOwn && $item->user_id == $this->user->id)
														{ ?>
															<a class="d-inline-block p-1" onclick="certificate.deleteItem('<?php echo $item->id; ?>', this)" data-message="<?php echo Text::_('COM_TJCERTIFICATE_DELETE_CERTIFICATE_MESSAGE');?>" class="btn btn-mini delete-button" type="button" title="<?php echo Text::_('JACTION_DELETE'); ?>"><i class="fa fa-trash-o"></i>
																<?php
															} ?>
															<?php
														} ?>

														<?php
														if ($this->manage && $item->is_external)
															{ ?>
																<a class="btn-micro hasTooltip d-inline-block px-1" onclick="Joomla.listItemTask('cb<?php echo $i;?>', 'certificates.<?php echo ($item->state == -1 ||$item->state == 0)  ? 'publish' : 'unpublish';?>')" class="btn btn-mini" type="button">
																	<?php
																	if ($item->state == -1 || $item->state == 0)
																		{ ?>
																			<i class="fa fa-times" title="<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_MARK_AS_PUBLISH_TEXT'); ?>"></i>
																			<?php
																		}
																		elseif ($item->state == 1)
																			{ ?>
																				<i class="fa fa-check-square" title="<?php echo Text::_('COM_TJCERTIFICATE_CERTIFICATE_MARK_AS_UNPUBLISH_TEXT'); ?>"></i>
																				<?php
																			} ?>
																			<?php
																		} ?>
																		<?php
																		if (!$item->is_external || !($this->manageOwn || $this->manage))
																		{
																			echo "-";
																		}
																		?>

																	</td>
																</tr>
																<?php
															}
															?>
															<tbody>
															</table>
															<?php
														}
														?>
														<input type="hidden" name="task" value="" />
														<input type="hidden" name="boxchecked" value="0" />
														<?php echo HTMLHelper::_('form.token'); ?>
														<div class="col-xs-12">
															<div class="pager" id="pagination">
																<?php echo $this->pagination->getPagesLinks(); ?>
																<!-- <hr class="hr hr-condensed"/> -->
															</div>
														</div>
													</form>
												</div>
											</div>

