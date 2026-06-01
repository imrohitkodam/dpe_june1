<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjlms
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2016 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Factory;


/**
 * Certificatetemplate controller class.
 *
 * @since       1.6
 * @deprecated  1.3.32 Use TJCertificate template controller instead
 */
class TjlmsControllerCertificatetemplate extends FormController
{
	public $view_list;
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'certificates';
		parent::__construct();
	}

	/**
	 * Method to load certificate template on ajax call for change template on template form.
	 *
	 * @return  json
	 *
	 * @since   1.3.14
	 */
	public function loadTemplate()
	{
		$jinput = Factory::getApplication()->input;
		$id = $jinput->get("id");
		$template = new \stdClass;

		if (empty($id))
		{
			$templatePath = JPATH_SITE . '/administrator/components/com_tjlms/certificate_default.php';

			if (File::exists($templatePath))
			{
				require_once $templatePath;
				$template->body = $certificate['message_body'];
			}
		}
		else
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');

			$tjlmsModelCertificatetemplate = BaseDatabaseModel::getInstance('Certificatetemplate', 'TjlmsModel');
			$template = $tjlmsModelCertificatetemplate->getItem($id);
		}

		echo new JsonResponse($template);
		jexit();
	}
}
