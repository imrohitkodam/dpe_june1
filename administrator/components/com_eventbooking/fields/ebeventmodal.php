<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\ParameterType;

class JFormFieldEBEventModal extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'ebeventmodal';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getInput()
	{
		$app = Factory::getApplication();

		// Load language
		$app->getLanguage()->load('com_eventbookingcommon', JPATH_ADMINISTRATOR);

		// The active weblink id field.
		$value = (int) $this->value > 0 ? (int) $this->value : '';

		// Create the modal id.
		$modalId = 'EBEvent_' . $this->id;

		/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = $app->getDocument()->getWebAssetManager();

		// Add the modal field script to the document head.
		$wa->useScript('field.modal-fields');

		// Script to proxy the select modal function to the modal-fields.js file.
		static $scriptSelect = null;

		if (is_null($scriptSelect))
		{
			$scriptSelect = [];
		}

		if (!isset($scriptSelect[$this->id]))
		{
			$wa->addInlineScript(
				"
			window.jSelectEBEvent_" . $this->id . " = function (id, title, catid, object, url, language) {
				window.processModalSelect('Event', '" . $this->id . "', id, title, catid, object, url, language);
			}",
				[],
				['type' => 'module']
			);
			Text::script('JGLOBAL_ASSOCIATIONS_PROPAGATE_FAILED');
			$scriptSelect[$this->id] = true;
		}

		// Setup variables for display.
		$linkWeblinks = 'index.php?option=com_eventbooking&amp;view=events&amp;layout=modal&amp;tmpl=component&amp;' . Session::getFormToken() . '=1';

		if ($value)
		{
			$modalTitle = Text::_('EB_CHANGE_EVENT');
		}
		else
		{
			$modalTitle = Text::_('EB_SELECT_EVENT');
		}

		$urlSelect = $linkWeblinks . '&amp;function=jSelectEBEvent_' . $this->id;

		if ($value)
		{
			/* @var \Joomla\Database\DatabaseDriver $db */
			$db    = Factory::getContainer()->get('db');
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__eb_events'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $value, ParameterType::INTEGER);
			$db->setQuery($query);

			try
			{
				$title = $db->loadResult();
			}
			catch (\RuntimeException $e)
			{
				$app->enqueueMessage($e->getMessage(), 'error');
			}
		}

		$title = empty($title) ? Text::_('EB_SELECT_EVENT') : htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The current weblink display field.
		$html = '<span class="input-group">';

		$html .= '<input class="form-control" id="' . $this->id . '_name" type="text" value="' . $title . '" readonly size="35">';

		$html .= '<button'
			. ' class="btn btn-primary' . ($value ? ' hidden' : '') . '"'
			. ' id="' . $this->id . '_select"'
			. ' data-bs-toggle="modal"'
			. ' type="button"'
			. ' data-bs-target="#ModalSelect' . $modalId . '">'
			. '<span class="icon-file" aria-hidden="true"></span> ' . Text::_('JSELECT')
			. '</button>';

		$html .= '<button'
			. ' class="btn btn-secondary' . ($value ? '' : ' hidden') . '"'
			. ' id="' . $this->id . '_clear"'
			. ' type="button"'
			. ' onclick="window.processModalParent(\'' . $this->id . '\'); return false;">'
			. '<span class="icon-times" aria-hidden="true"></span> ' . Text::_('JCLEAR')
			. '</button>';

		$html .= '</span>';

		$html .= HTMLHelper::_('bootstrap.renderModal', 'ModalSelect' . $modalId, [
			'title'      => $modalTitle,
			'url'        => $urlSelect,
			'height'     => '400px',
			'width'      => '800px',
			'bodyHeight' => 70,
			'modalWidth' => 80,
			'footer'     => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
				. Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
		]);

		if ($this->onchange)
		{
			$onChange = ' onchange="' . $this->onchange . '"';
		}
		else
		{
			$onChange = '';
		}

		// Note: class='required' for client side validation.
		$class = $this->required ? ' class="required modal-value"' : '';
		$html  .= '<input type="hidden" id="' . $this->id . '_id" ' . $class . ' data-required="' . (int) $this->required . '" name="' . $this->name
			. '" data-text="' . htmlspecialchars(Text::_('EB_SELECT_EVENT', true), ENT_COMPAT, 'UTF-8') . '" value="' . $value . '"' . $onChange . ' />';

		return $html;
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getLabel()
	{
		return str_replace($this->id, $this->id . '_name', parent::getLabel());
	}
}