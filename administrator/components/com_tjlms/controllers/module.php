<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Jticketing
 * @author     Techjoomla <aniket_c@tekdi.net>
 * @copyright  2016 techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;


/**
 * Jticketing model.
 *
 * @since  1.6
 */
class TjlmsControllerModule extends FormController
{
	protected $view_list = '';

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		$this->view_list = 'modules';
		parent::__construct();
	}

	/**
	 * Method to  save module along with course ID
	 *
	 * @param   array  $key     post
	 * @param   array  $urlVar  post
	 *
	 * @return	null
	 *
	 * @since	1.0.0
	 */
	public function save($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$post = $input->post;
		$model = $this->getModel('module');
		$data = $post->get('tjlms_module', '', 'ARRAY');

		$error = false;

		// Validate the posted data.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');
			$error = true;
		}

		// Test whether the data is valid.
		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			$error = true;

			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof \Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}
		}

		// Save the module and the course ID along with that
		if (!$model->save($validData))
		{
			$error = true;
			$app->enqueueMessage($model->getError());
		}

		if ($error)
		{
			$message = Text::_('COM_TJLMS_MODULE_SAVE_ERROR');
		}
		else
		{
			$recordId = $model->getState($this->context . '.id');

			if ($data['id'] > 0)
			{
				$message = Text::_('COM_TJLMS_MODULE_UPDATED_SUCCESSFULLY');
			}
			else
			{
				$message = Text::_('COM_TJLMS_MODULE_CREATED_SUCCESSFULLY');
			}
		}

		echo new JsonResponse($recordId, $message, $error);
		jexit();
	}
}
