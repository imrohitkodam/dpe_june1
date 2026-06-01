<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;

// Load JavaScript message titles
Text::script('ERROR');
Text::script('WARNING');
Text::script('NOTICE');
Text::script('MESSAGE');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
$user = Factory::getUser();
$session = Factory::getSession();



$params  = ComponentHelper::getParams('com_dpe');
$logtypes = $params->get('rsticketLogtype', '0', 'INT');
$logList = [];

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');

foreach($logtypes as $key => $logtype)
{
	$typeDetails = Table::getInstance('Type', 'TjucmTable');
	$logData = $typeDetails->load(array('alias' => $logtype));
	$logList[$key]['text'] = $typeDetails->title;
	$logList[$key]['value'] = $typeDetails->unique_identifier;	

	// Check user have permission to view records of assigned cluster
		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			// Check user has permission for mentioned cluster
			if (!RBACL::check($user->id, 'com_cluster', 'core.viewitemlist.' . $typeDetails->id, 'com_tjucm'))
			{
				// Provide access on ucm list view if user is assignee
				$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
				JLoader::register('DpeMainHelper', $mainHelper);

				$dpeMainHelper = new DpeMainHelper;
				$assignedUsers = $dpeMainHelper->getFieldValues($user->id, null, $typeDetails->unique_identifier);

				if (empty($assignedUsers))
				{
					unset($logList[$key]);
				}
			}
		}

}


if ($session->get('myTickets', 'off') == 'on')
{
	$myticket = '&filter[myticket]=on';
}
// Get School Information
if (!empty($this->ticket->id) && $this->ticket->id != 0)
{
	JLoader::register('DpeModelRsticketspro', JPATH_SITE . '/components/com_dpe/models/rsticketspro.php');
	$dpeModelRsticketspro = BaseDatabaseModel::getInstance('Rsticketspro', 'DpeModel', array('ignore_request' => true));
	$this->ticket->school = $dpeModelRsticketspro->getTicketXrefData($this->ticket->id);

	// Get users by roles

	$clustertable = ClusterFactory::table('Clusters');
	$clustertable->load(array('id' => $this->ticket->school['agency_id']));

	// Get cluster users on edit ticket
	$rsticketModel = DPE::model('rsticket');
	$client = 'com_multiagency';
	$this->clusterUsers = $rsticketModel->getUsersByActions(array('core.adduser'), $client, $clustertable->client_id);
	$staffUsers = $rsticketModel->getUsersByActions(array('core.view.all'), $client, $clustertable->client_id);

	if (!empty($staffUsers))
	{
		$this->clusterUsers = array_merge($this->clusterUsers, $staffUsers, ($publicUsers)?$publicUsers:array());
	}

	// Get dpe admin roles
	if ($user->authorise('core.manageall', 'com_cluster'))
	{
		$dpeAdminUsers = $rsticketModel->getUsersByActions(array('core.create'), 'com_multiagency');
		$this->clusterUsers = array_merge($this->clusterUsers, $dpeAdminUsers);
	}

	
}

// Get create Ticket list menu
$mainframe          = Factory::getApplication();
$menu               = $mainframe->getMenu();
$ticketListMenuItem = $menu->getItems('link', 'index.php?option=com_dpe&view=rsticketspro', true );
$backLink           = Route::_('index.php?option=com_dpe&view=rsticketspro'. $myticket .'&Itemid=' . $ticketListMenuItem->id, false, 0);

// Get Current url for notification manager widget
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);
$urlId = '';
$input = $mainframe->input;

if ($input->getInt('id'))
{
	$urlId = '&id=' . $input->getInt('id');
}

$currentUrl =  'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . $urlId . $extraParams .'&Itemid=' . $input->get('Itemid');

 // Createion of log in Ticket
$logLink = URI::root().'index.php?option=com_rsticketspro&view=ticket&id='.$input->getInt('id').':'.str_replace(' ', '-', $this->ticket->subject);

// Get the log id.
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
$fieldStatusTableValue = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
$fieldStatusTableValue->load(array('value'=> $logLink));
$ucmLogid = $fieldStatusTableValue->content_id;

Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');

	$typeDetails = Table::getInstance('LogticketExtend', 'DpeTable');
	$logData = $typeDetails->load(array('ticketId' => $this->ticket->id));
    $logIdXref = $typeDetails->logId;

if($ucmLogid || $logIdXref)
{
	Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
	$tjucmFormType = Table::getInstance('data', 'TjucmTable');
	$tjucmFormType->load(array('id'=> ($ucmLogid)?$ucmLogid:$logIdXref));

	$tjucmFormTypeName = Table::getInstance('type', 'TjucmTable');
	$tjucmFormTypeName->load(array('unique_identifier'=> $tjucmFormType->client));

	$logType = $tjucmFormTypeName->title;

	JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
	$appendUrl .= "&client=" . $tjucmFormType->client;
	$link = 'index.php?option=com_tjucm&view=items' . $appendUrl;
	$tjUcmFrontendHelper = new TjucmHelpersTjucm;
	$itemId = $tjUcmFrontendHelper->getItemId($link);

	$finalLogId = ($ucmLogid) ? $ucmLogid : $logIdXref; 

	$logLink = Route::_('index.php?option=com_tjucm&task=itemform.edit&id=' . $finalLogId . $appendUrl, false);
}


?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'ticket.cancel' || task == 'ticket.updateinfo' || task == 'ticket.savetimespent' || task == 'ticket.updatefields' || document.formvalidator.isValid(document.getElementById('adminForm')))
		{
			Joomla.submitform(task, document.getElementById('adminForm'));

			if (typeof button != 'undefined' && task == 'ticket.reply')
			{
				button.disabled = true;
			}
		}
		else
		{
			if (typeof button != 'undefined' && task == 'ticket.reply')
			{
				button.disabled = false;
			}

			alert('<?php echo $this->escape(Text::_('COM_RSTICKETSPRO_PLEASE_COMPLETE_ALL_FIELDS'));?>');
		}
	}
</script>
<div>
<a class="fs-16 font-600 cursor-pointer confirm-navigation" href="<?php echo $backLink;?>">
  <i class="fa fa-arrow-left mr-10" aria-hidden="true"></i><?php echo Text::_('COM_DPE_BACK_BUTTON');?>
</a>


</div>
<div> 
<?php
$dpeAdmin       = $user->authorise('core.manageall', 'com_cluster');

// Decode stored admin access and emails for the ticket's school
$dpeAllowAdmin = json_decode($this->ticket->school['dpe_allow_admin']);
$ccOrgEmails   = json_decode($this->ticket->school['emails']);
$isAllowed     = $dpeAllowAdmin->is_allow ?? false;

if (!$dpeAdmin && $isAllowed) {

    // Ensure fallback to arrays if null
    $adminUserIds    = $dpeAllowAdmin->email ?? [];
    $ccAllowedEmails = $ccOrgEmails->email ?? [];
    $creatorUserId   = $dpeAllowAdmin->user_id ?? 0;

    // Check if current user is allowed
    $isEmailAllowed = in_array($user->email, $ccAllowedEmails);
    $isAdminAllowed = in_array($user->id, $adminUserIds);
    $isCreator      = ($user->id == $creatorUserId);

    // If none of the conditions are met, deny access
    if (!$isEmailAllowed && !$isAdminAllowed && !$isCreator) {
        $app = Factory::getApplication();
        $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
        return;
    }
}

?>

	<h2 class="float-right rsticketno"><?php echo Text::_('COM_RSTICKETSPRO_TICKET_NO') .' '. $this->ticket->id?></h2>
	
	<?php if($ucmLogid)
	{?>

		<a  class="float-right  fs-5 fw-bold" href="<?php echo $logLink;?>" id="ucmlogid" target='_blank'><?php echo ucwords(Text::sprintf('COM_DPE_LINK_TO_LOG_FROM_TICKET',$logType));?></a>

	<?php }
	else{
    	
    	if(!$logIdXref)
    	{?>

    	<a class="float-right  fs-5 fw-bold" href="javascript:void(0)" id="ucmticketlogId"> Create Log</a> 

		<div class="btn-group pr-10 float-right  fs-5 fw-bold hide" id="logtype">
			<fieldset id="filter-bar">
				<div class="filter-select fltrt">
					<select name="logList[]" id = "logList" data-placeholder="<?php echo Text::_('COM_DPE_RSTICKET_CHOOSE_LOG_SELECT_LABLE'); ?>" class="inputbox">
						<option value=""><?php echo Text::_('COM_DPE_RSTICKET_CHOOSE_LOG_SELECT'); ?> </option>
						<?php echo HTMLHelper::_('select.options', $logList, 'value', 'text');?>
					</select>
				</div>
			</fieldset>
		</div>
    	<?php
    }
    else
    {
    ?>
    	<a  class="float-right  fs-5 fw-bold" href="<?php echo $logLink;?>" id="editLogId" target='_blank'><?php echo ucwords(Text::sprintf('COM_DPE_LINK_TO_LOG_FROM_TICKET',$logType));?></a>

    <?php 
	}

	?>
		
		<div id ='logMessage'>

		</div>
		<input type="hidden" name="logid" id = 'logid'>
	<?php }?>
	
</div>
<div class="rsTicketDetail" id="rsTicketDetail">

	<?php
	if ($this->params->get('show_page_heading', 1))
	{
		?>
		<h2><?php echo $this->escape($this->ticket->subject); ?></h2>

		<?php
	}else{ ?>
		<h2><?php echo $this->escape($this->ticket->subject); ?></h2>
	<?php }

	if ($this->globalMessage)
	{
		?>
		<div class="row-fluid" id="ticket-global-message">
			<div class="alert alert-warning">
				<?php echo $this->globalMessage;?>
			</div>
		</div>
		<?php
	}
	?>

	<form action="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=ticket'); ?>"
		method="post" name="adminForm" id="adminForm" class="form-validate ucm-form-styling create-campaign my-support-view" enctype="multipart/form-data" autocomplete="off">
		<?php
		if ($this->ticketView == 'plain' || $this->isPrint)
		{
			?>
			<div class="row-fluid">
				<div class="span12" id="ticket-left-column">
					<?php echo $this->loadTemplate('messages'); ?>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span12" id="ticket-right-column">
					<?php
					foreach ($this->ticketSections as $layout => $title)
					{
						if ($layout == 'messages')
						{
							continue;
						}

						if (empty($this->ticket->fields) && $layout == 'custom_fields')
						{
							continue;
						}

						// Add the title
						$this->plain->addTitle($title, $layout);
						$content = $this->loadTemplate($layout);

						// Add the content
						$this->plain->addContent($content);
					}

					// Allow plugins to inject content here
					RSTicketsProHelper::trigger('onAfterTicketInformation', array($this->ticket, $this->plain));

					// Render the plain view
					$this->plain->render();
					?>
				</div>
			</div>
			<?php
		}
		elseif ($this->ticketView == 'accordion')
		{
			foreach ($this->ticketSections as $layout => $title)
			{
				if (empty($this->ticket->fields) && $layout == 'custom_fields')
				{
					continue;
				}

				// Add the tab title
				$this->accordion->addTitle($title, $layout);
				$content = $this->loadTemplate($layout);

				// Add the tab content
				$this->accordion->addContent($content);
			}

			// Allow plugins to inject content here
			RSTicketsProHelper::trigger('onAfterTicketInformation', array($this->ticket, $this->accordion));

			// Render tabs
			$this->accordion->render();
		}
		else
		{
			foreach ($this->ticketSections as $layout => $title)
			{
				if (empty($this->ticket->fields) && $layout == 'custom_fields')
				{
					continue;
				}

				// Add the tab title
				$this->tabs->addTitle($title, $layout);
				$content = $this->loadTemplate($layout);

				// Add the tab content
				$this->tabs->addContent($content);
			}

			// Allow plugins to inject content here
			RSTicketsProHelper::trigger('onAfterTicketInformation', array($this->ticket, $this->tabs));

			// Render tabs
			$this->tabs->render();
		}
		?>
		<div>
			<?php echo HTMLHelper::_('form.token'); ?>
			<input type="hidden" name="id" value="<?php echo $this->ticket->id; ?>" />
			<input type="hidden" name="cid" value="<?php echo $this->ticket->id; ?>" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="option" value="com_rsticketspro" />

			<!-- DPE Hack start to add hidden fields to create content for notification manager -->
			<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>
			<input type="hidden" name="element" id="element" value="com_rsticket.ticket"/>
			<input type="hidden" name="element_id" id="element_id" value="<?php echo $this->ticket->id;?>"/>
			<input type="hidden" name="cluster_id" id="cluster_id" value="<?php echo $this->ticket->school['agency_id'];?>"/>
			<input type="hidden" name="ticketUrl" id="ticketUrl" value=""/>

			<!-- DPE Hack end -->
		</div>
	</form>
</div>

<script type="text/javascript">
	
	jQuery('document').ready(function(){

		jQuery("#ucmlogid").css('margin','43px -157px');
		jQuery("#ucmticketlogId").css('margin','45px -110px');
		jQuery("#logtype").css('margin','43px -324px');
		jQuery("#logList").css('width','147px');
		// jQuery("#editLogId").css('margin-top','52px');

		var ticketNoText = jQuery('.rsticketno').text().trim();

        var ticketNoWidth = '43px'+' -'+jQuery('.rsticketno').width()+'px';
       
        jQuery('#editLogId').css('margin', ticketNoWidth);



		jQuery(".rsticketno").css('margin-top','9px');

		if (jQuery(window).width() < 766) {

			var submitElement = jQuery('#rsTicketDetail');

			if (submitElement.length > 0) {
				submitElement.prepend('<br><br><br><br>');
			}
			jQuery("#logtype").css('margin','80px -139px');
		}

		jQuery('#ticketUrl').val(window.location.href)
	})
	jQuery('document').ready(function(){

		jQuery('#sbox-btn-close').click(function(){window.parent.SqueezeBox.close();})

	})
</script>
<script >    


	jQuery('#ucmticketlogId').click(function(){

		if(jQuery('#logtype').hasClass('hide'))
		{
			jQuery('#logtype').removeClass('hide');
		}
		else
		{
			jQuery('#logtype').addClass('hide');
		}
		

	})
	
	
</script> 
<script type="text/javascript">

	jQuery(document).ready(function() {
		jQuery('#logList').on('change', function() {
			
			var selectedValue = jQuery(this).val();
			if (selectedValue.length > 0)
			{
				jQuery.ajax({
					url: Joomla.getOptions('system.paths').root + "/index.php?option=com_dpe&task=rsticket.getItemId&format=json",
					type: "POST",
					dataType: 'json',
					data: {logClient: selectedValue},

					success:function(response)
					{    	

						if (response.data.success)
						{		
							var targetElement = $('#logForm');

							var url = response.data.url;

							var wwidth = jQuery(window).width() -250;
							var wheight = jQuery(window).height() - 150;

							SqueezeBox.open(url, {
								handler: 'iframe',
								closable: true,
								size: {
									x: wwidth,
									y: wheight
								},
								sizeLoading: {
									x: wwidth,
									y: wheight
								},
								classWindow: '',
								onClose: function()
								{
									if(jQuery('#logid').val().length > 0)
									{							
									
										var logId = jQuery('#logid').val();
										var ticketId = jQuery('#ticketId').val();


										jQuery.ajax({
										url: Joomla.getOptions('system.paths').root + "/index.php?option=com_dpe&task=rsticket.saveLogId&format=json",
										type: "POST",
										dataType: 'json',
										data: {logId: logId,ticketId:ticketId},

										success:function(response)
										{
											if(response.data.success)
											{
												alert('<?php echo Text::_('COM_DPE_RSTICKET_SAVE_LOG_FORM');?>');
												location.reload();
											}

										}
										});
								    }
								    else
								    {
								    	location.reload();
								    }
								},

							});
							
						}
					}
				})
			}
			


		});
	});
</script>
<?php if($dpeAdmin  ){?>
<script>
  (function($){
    $(document).ready(function(){
      $(document).on('click', 'a.confirm-navigation', function(e){
        e.preventDefault();
        var href = $(this).attr('href');

        // Ask the question
        var answer = window.confirm("Did you add your spent time by submitting the form? Press OK for Yes (proceed), Cancel for No (stay on page).");

        if (answer) {
          // proceed to link
          window.location.href = href;
        } else {
          // stay on the page (do nothing)
        }
      });
    });
  })(jQuery);
</script>
<?php } ?>