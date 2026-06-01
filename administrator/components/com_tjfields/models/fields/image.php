<?php
/**
 * @version    SVN:<SVN_ID>
 * @package    TJFields
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_tjfields.models.fields.file', JPATH_ADMINISTRATOR);

/**
 * Form Field Image class
 * Supports a multi line area for entry of plain text with count char
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldImage extends JFormFieldFile
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'Image';

	/**
	 * The accepted file type list.
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $accept;

	/**
	 * The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 *
	 * @var    mixed
	 * @since  __DEPLOY_VERSION__
	 */
	protected $element;

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $layout = 'joomla.form.field.file';

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __get($name)
	{
		require_once JPATH_SITE . '/components/com_tjfields/helpers/tjfields.php';

		switch ($name)
		{
			case 'accept':

				return $this->accept;
				break;

			case 'element';

				return $this->element;
				break;
		}

		return parent::__get($name);
	}

	/**
	 *Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to the the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'accept':
				$this->accept = (string) $value;
				break;

			case 'multiple':
				$this->multiple = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   __DEPLOY_VERSION__
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->accept = (string) $this->element['accept'];
			$this->multiple = (string) $this->element['multiple'];
		}

		return $return;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getInput()
	{
		$layoutData = $this->getLayoutData();
		$html = $this->getRenderer($this->layout)->render($layoutData);

		if ($this->size)
		{
			$sizes = array();
			$sizes[] = HTMLHelper::_('number.bytes', ini_get('post_max_size'), '');
			$sizes[] = HTMLHelper::_('number.bytes', ini_get('upload_max_filesize'), '');
			$sizes[] = $this->size * 1024 * 1024;

			$maxSize = HTMLHelper::_('number.bytes', min($sizes));
			$fileMaxSize = '<strong>' . $maxSize . '</strong>';
			$html = str_replace(substr($html, strpos($html, '<strong>'), strpos($html, '</strong>')), $fileMaxSize, $html);
		}

		// Load backend language file
		$lang = Factory::getLanguage();
		$lang->load('com_tjfields', JPATH_SITE);

		if (!empty($layoutData["value"]))
		{
			$data = $this->buildData($layoutData);

			if (!empty($data->mediaLink))
			{
				$html .= '<div class="control-group">';
				$html .= $data->html;
				$html .= $this->renderImage($data, $layoutData);
				$html .= $this->canDownloadFile($data, $layoutData);
				$html .= $this->canDeleteFile($data, $layoutData);
				$html .= '</div>';
			}
		}

		return $html;
	}

	/**
	 * Method to render image file.
	 *
	 * @param   object  $data        file data.
	 * @param   array   $layoutData  layoutData
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function renderImage($data, $layoutData)
	{
		$path = Uri::root() . 'images/tjmedia/';

		if (!empty($data->tjFieldFieldTable))
		{
			$path .= str_replace(".", "/", $data->tjFieldFieldTable->get('client') . '/');
		}

		return '<img src="' . $path . $layoutData['value'] . '" height=
		"' . $layoutData['field']->element->attributes()->height . '"width="' . $layoutData['field']->element->attributes()->width . '" ></img><br>';
	}
	/**
	 * Method to required data for file.
	 *
	 * @param   array  $layoutData  layoutData
	 *
	 * @return  object
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected function buildData($layoutData)
	{
		$tjFieldHelper = new \TjfieldsHelper;
		$data = new \stdClass;

		$app = Factory::getApplication();
		$data->clientForm = $app->input->get('client', '', 'string');

		// Checking the field is from subfrom or not
		$formName = explode('.', $this->form->getName());
		$formValueId = $app->input->get('id', '', 'INT');
		$data->subFormFileFieldId = 0;
		$data->isSubformField = 0;
		$data->subformId = 0;

		if ($formName[0] === 'subform')
		{
			$data->isSubformField = 1;
			$formData = $tjFieldHelper->getFieldData(substr($formName[1], 0, -1));

			// Subform Id
			$data->subformId = $formData->id;
			$fileFieldData = $tjFieldHelper->getFieldData($layoutData['field']->fieldname);

			// File Field Id under subform
			$data->subFormFileFieldId = $fileFieldData->id;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ()
			{
				var fieldValue = "<?php echo $layoutData["value"]; ?>";
				var AttrRequired = jQuery('#<?php echo $layoutData["id"];?>').attr('required');

				if (typeof AttrRequired !== typeof undefined && AttrRequired !== false)
				{
					if (fieldValue)
					{
						jQuery('#<?php echo $layoutData["id"];?>').removeAttr("required");
						jQuery('#<?php echo $layoutData["id"];?>').removeClass("required");
					}
				}
			});
		</script>
		<?php
		$data->html = '<input fileFieldId="' . $layoutData["id"] . '" type="hidden" value="' . $layoutData["value"] . '" />';

		$fileInfo = new \SplFileInfo($layoutData["value"]);
		$data->extension = $fileInfo->getExtension();

		// Access based actions
		$data->user = Factory::getUser();

		$db = Factory::getDbo();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$data->tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable', array('dbo', $db));
		$data->tjFieldFieldTable->load(array('name' => $layoutData['field']->fieldname));

		// Get Field value details
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjfields/tables');
		$data->fields_value_table = Table::getInstance('Fieldsvalue', 'TjfieldsTable');
		$data->fields_value_table->load(array('value' => $layoutData['value']));

		$extraParamArray = array();
		$extraParamArray['id'] = $data->fields_value_table->id;

		// Creating media link by check subform or not
		if ($data->isSubformField)
		{
			$extraParamArray['subFormFileFieldId'] = $data->subFormFileFieldId;
		}

		// Get configured renderer for the file field
		if ($layoutData['field']->element['renderer'] instanceof SimpleXMLElement)
		{
			$renderer = $layoutData['field']->element['renderer']->__toString();
		}
		else
		{
			$renderer = 'download';
		}

		$data->mediaLink = $tjFieldHelper->getMediaUrl($layoutData["value"], $extraParamArray, $renderer);
		$data->renderer = $renderer;

		return $data;
	}
	/**
	 * Method to download file.
	 *
	 * @param   object  $data        file data.
	 * @param   array   $layoutData  layoutData
	 *
	 * @return  string
	 *
	 * @since    1.5
	 */
	protected function canDownloadFile($data, $layoutData)
	{
		$canView = 0;
		$canDownload = 0;
		$user = Factory::getUser();

		if ($data->user->authorise('core.field.viewfieldvalue', 'com_tjfields.group.' . $data->tjFieldFieldTable->group_id))
		{
			if ($data->isSubformField)
			{
				$canView = $data->user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->subFormFileFieldId);
			}
			else
			{
				$canView = $data->user->authorise('core.field.viewfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->id);
			}
		}

		if ($data->fields_value_table->user_id != null && $user->id == $data->fields_value_table->user_id)
		{
			$canDownload = true;
		}

		$downloadFile = '';

		if ($canView || $canDownload)
		{
			$renderer = $data->renderer;

			//DPE Hack
			$fileTitle = '';
			
			if($data->fields_value_table->value)
			{
				$fileTitle = substr($data->fields_value_table->value, strpos($data->fields_value_table->value, '_', 12) + 1);
			}		

			if ($renderer == 'download')
			{
				$downloadFile .= '<strong><a href="' . $data->mediaLink . '">' . $fileTitle . '</a></strong>';
			}
			else
			{
				HTMLHelper::_('behavior.modal');
				HTMLHelper::script('media/com_tjfields/js/ui/file.js');
				$extension = $data->extension;

				if (in_array($extension, array('ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'pps', 'ppsx')))
				{
					$mediaLink = 'https://view.officeapps.live.com/op/embed.aspx?src=' . $data->mediaLink;
				}
				elseif (in_array($extension, array('png', 'jpeg', 'jpg', 'gif')))
				{
					$mediaLink = $data->mediaLink;
				}
				else
				{
					$mediaLink = 'https://docs.google.com/gview?url=' . $data->mediaLink . '&embedded=true';
				}

				// Get Image Size
				$fieldParams     = json_decode($data->tjFieldFieldTable->params);
				$uploadPath      = $fieldParams->uploadpath;
				$widthHeight     = "";

				if ($uploadPath)
				{
					$absoluteImgPath = $uploadPath . '/' . $data->fields_value_table->value;

					if (file_exists($absoluteImgPath))
					{
						$imageObj        = new \Image($absoluteImgPath);
						$imageProperties = $imageObj->getImageFileProperties($absoluteImgPath);

						if (strpos($imageProperties->mime, 'image') !== false)
						{
							$widthHeight  = ", " . $imageProperties->width . ", " . $imageProperties->height;
						}
					}
				}

				$downloadFile = '<strong><a style="cursor:pointer;" onclick="tjFieldsFileField.previewMedia(\'' . $mediaLink . '\'' . $widthHeight . ');">' . $fileTitle . '</a></strong>';
			}
		}

		return $downloadFile;
	}

	/**
	 * Method to delete file.
	 *
	 * @param   object  $data        file data.
	 * @param   array   $layoutData  layoutData
	 *
	 * @return  string
	 *
	 * @since    1.5
	 */
	protected function canDeleteFile($data,$layoutData)
	{
		$canEdit = 0;

		if ($data->user->authorise('core.field.editfieldvalue', 'com_tjfields.group.' . $data->tjFieldFieldTable->group_id))
		{
			$canEdit = $data->user->authorise('core.field.editfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->id);
		}

		$canEditOwn = 0;

		if ($data->user->authorise('core.field.editownfieldvalue', 'com_tjfields.group.' . $data->tjFieldFieldTable->group_id))
		{
			$canEditOwn = $data->user->authorise('core.field.editownfieldvalue', 'com_tjfields.field.' . $data->tjFieldFieldTable->id);

			if ($canEditOwn && ($data->user->id != $data->fields_value_table->user_id))
			{
				$canEditOwn = 0;
			}
		}

		$deleteFiledata = '';

		// Add constant to the JS
		Text::script('COM_TJFIELDS_FILE_DELETE_CONFIRM');

		if (!empty($data->mediaLink) && ($canEdit || $canEditOwn) && $layoutData['required'] == '' && $data->fields_value_table->id)
		{
			$deleteFiledata .= '<span class="btn btn-remove"> <a id="remove_' . $layoutData["id"] . '" href="javascript:void(0);"
				onclick="deleteFile(\'' . base64_encode($layoutData["value"]) . '\',
				 \'' . $layoutData["id"] . '\', \'' . base64_encode($data->fields_value_table->id) . '\',
				  \'' . $data->subFormFileFieldId . '\',\'' . $data->isSubformField . '\');"><strong>'
				. Text::_("COM_TJFIELDS_FILE_DELETE") . '</strong></a> </span>';
		}

		return $deleteFiledata;
	}
}
