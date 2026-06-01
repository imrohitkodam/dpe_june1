<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
jimport('joomla.html.html');
jimport('joomla.html.parameter');
jimport('joomla.utilities.date');
jimport('techjoomla.common');
jimport('techjoomla.tjmail.mail');

use Dompdf\Dompdf;
use Dompdf\Options;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Helper\UserGroupsHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\LogEntry;
/**
 * Tjlms Main helper.
 *
 * @since  1.0.0
 */
class ComtjlmsHelper
{
	public $sociallibraryobj = null;

	public $TjGeoHelper      = null;

	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$params            = ComponentHelper::getParams('com_tjlms');
		$socialintegration = $params->get('social_integration');

		// Load main file
		jimport('techjoomla.jsocial.jsocial');
		jimport('techjoomla.jsocial.joomla');

		if ($socialintegration != 'none')
		{
			if ($socialintegration == 'jomsocial')
			{
				jimport('techjoomla.jsocial.jomsocial');
			}
			elseif ($socialintegration == 'easysocial')
			{
				jimport('techjoomla.jsocial.easysocial');
			}
		}

		$path = JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

		if (!class_exists('TjlmsCoursesHelper'))
		{
			JLoader::register('TjlmsCoursesHelper', $path);
			JLoader::load('TjlmsCoursesHelper');
		}

		$this->sociallibraryobj = $this->getSocialLibraryObject();
	}

	/**
	 * Function to get component params
	 *
	 * @param   STRING  $component  Component name
	 *
	 * @return  ARRAY  $params
	 *
	 * @since  1.0.0
	 */
	public function getcomponetsParams($component = 'com_tjlms')
	{
		return ComponentHelper::getParams($component);
	}

	/**
	 * Function to genrate PDF
	 *
	 * @param   Array   $certData   Array of html content and params
	 * @param   STRING  $pdffile    File path
	 * @param   STRING  $course_id  Course ID
	 * @param   STRING  $user_id    User iD
	 * @param   STRING  $download   Allow download
	 *
	 * @return  STRING pd file path
	 *
	 * @since  1.0.0
	 */
	public function generatepdf($certData, $pdffile, $course_id, $user_id, $download = 0)
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		$html        = $certData['html'];
		$params      = new Registry($certData['params']);
		$pageSize    = $params->get('certifcate_page_size', 'A4');
		$orientation = $params->get('orientation', 'portrait');
		$font        = $params->get('certificate_font', 'DeJaVu Sans');
		$style       = '';

		// If the pagesize is custom then get the correct size and width.
		if ($pageSize === 'custom')
		{
			$height   = $params->get('certificate_pdf_width', '80') * 28.3465;
			$width    = $params->get('certificate_pdf_height', '80') * 28.3465;
			$pageSize = array(0, 0, $width, $height);
		}

		// If the font is custom then get the custmized font.
		if ($font === 'custom')
		{
			$font      = $params->get('certificate_custom_font', 'DeJaVu Sans');
			$fontArray = explode(',', $font);

			foreach ($fontArray as $fontName)
			{
				$fontName = str_replace(' ', '', ucfirst($fontName));
				$link = '<link href="http://fonts.googleapis.com/css?family=' . $fontName . '" rel="stylesheet" type="text/css">';
				$style .= $link;
			}
		}

		require_once JPATH_SITE . "/libraries/techjoomla/dompdf/autoload.inc.php";

		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' . $style . '</head><body>' . $html . '</body></html>';

		if (get_magic_quotes_gpc())
		{
			$html = stripslashes($html);
		}

		// Set font for the pdf download.
		$options   = new Options;
		$options->setDefaultFont($font);

		$dompdf    = new DOMPDF($options);
		$dompdf->loadHTML($html);

		// Set the page size and oriendtation.
		$dompdf->setPaper($pageSize, $orientation);

		$dompdf->render();

		if ($download == 1)
		{
			$cerficatePdfName = File::makeSafe(Text::sprintf("COM_TJLMS_COURSE_CERTIFICATE_DOWNLOAD_FILE_NAME", $course_id, $user_id) . ".pdf");
			$dompdf->stream($cerficatePdfName, array("Attachment" => 1));
			jexit();
		}
	}

	/**
	 * Function used to get enrolled user
	 *
	 * @param   INT    $c_id     Course ID
	 * @param   ARRAY  $options  Optional parameter
	 *
	 * @return  mixed Enrolled users or boolean
	 *
	 * @since  1.0.0
	 */
	public function getCourseEnrolledUsers($c_id = 0, $options = array())
	{
		try
		{
			$db     = Factory::getDBO();
			$select = '*';

			if (isset($options['IdOnly']) && $options['IdOnly'] == 1)
			{
				$select = 'user_id';
			}

			$getResultType = isset($options['getResultType']) ? $options['getResultType'] : "loadObjectList";

			$query = $db->getQuery(true);
			$query->select($select);
			$query->from($db->qn('#__tjlms_enrolled_users', 'a'));
			$query->join('inner', '`#__users` AS u ON a.user_id = u.id');
			$query->where($db->qn('a.course_id') . '=' . $db->q($c_id));

			$state = 1;

			if (isset($options['state']))
			{
				$state = $options['state'];
			}

			if (is_array($state))
			{
				ArrayHelper::toInteger($state);
				$query->where($db->qn('a.state') . 'IN (' . implode(',', $db->q($state)) . ')');
			}
			else
			{
				$query->where($db->qn('a.state') . '=' . $db->q((int) $state));
			}

			if (isset($options['user_id']))
			{
				$query->where($db->qn('a.user_id') . '=' . $db->q((int) $options['user_id']));
			}

			// Set limit to reduce the load
			// We will refactor this function as per the use case
			if (isset($options['limit']))
			{
				$query->setLimit($options['limit']);
			}

			$db->setQuery($query);

			return $db->$getResultType();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to replace tags from certificate to actual result
	 *
	 * @param   ARRAY  $cer_data  Certificate data
	 *
	 * @return  STRING Message body
	 *
	 * @since  1.0.0
	 */
	public function tagreplace($cer_data)
	{
		$message_body = '';

		if (isset($cer_data['msg_body']))
		{
			$message_body = $cer_data['msg_body'];
			preg_match_all("/\[.*?\]/", strip_tags($cer_data['msg_body']), $matches);
			$message_body = stripslashes($message_body);

			foreach ($cer_data as $index => $data)
			{
				$message_body = str_ireplace('[' . $index . ']', $data, $message_body);
			}

			$jsFilePath = JPATH_ROOT . '/components/com_community/libraries/core.php';
			$esFilePath = JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

			foreach ($matches[0] as $fieldTag)
			{
				$res       = '';
				$field     = str_replace("[", " ", $fieldTag);
				$field     = str_replace("]", " ", $field);
				$field_arr = explode(":", $field);

				if (trim($field_arr[0]) == 'esfield' && file_exists($esFilePath))
				{
					require_once $esFilePath;
					$esuser = Foundry::user();
					$res    = $esuser->getFieldValue($field_arr[1]);

					// Since we are printing user data, lets implode array and return string
					if (is_array($res))
					{
						$res = implode(",", $res);
					}

					if (!empty($res))
					{
						$message_body = str_replace($fieldTag, $res, $message_body);
					}
					else
					{
						$message_body = str_replace($fieldTag, " ", $message_body);
					}
				}

				if (trim($field_arr[0]) == 'jsfield' && file_exists($jsFilePath))
				{
					include_once $jsFilePath;
					$jsprofile = CFactory::getUser();
					$res       = $jsprofile->getInfo($field_arr[1]);

					if (!empty($res))
					{
						$message_body = str_replace($fieldTag, $res, $message_body);
					}
					else
					{
						$message_body = str_replace($fieldTag, " ", $message_body);
					}
				}
			}
		}

		return $message_body;
	}

	/**
	 * Function to check if the user is admin
	 *
	 * @param   object  $user  User object
	 *
	 * @return  integer
	 *
	 * @since  1.0.0
	 */
	public function checkAdmin($user)
	{
		if ($user->get('isRoot'))
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Function to get Item id
	 *
	 * @param   STRING  $link  URL
	 *
	 * @return  int|boolean  Item id or false
	 *
	 * @since  1.0.0
	 */
	public function getitemid($link)
	{
		$itemid = 0;

		if (strpos($link, 'index.php?option=com_tjlms&view=courses') !== false)
		{
			$link = 'index.php?option=com_tjlms&view=courses&courses_to_show=all';
		}

		$parsedLinked = array();
		parse_str($link, $parsedLinked);

		$layout = '';

		if (isset($parsedLinked['layout']))
		{
			$layout = $parsedLinked['layout'];
		}

		if (isset($parsedLinked['view']))
		{
			if ($parsedLinked['view'] == 'course')
			{
				$tjlmsCoursesHelper   = new TjlmsCoursesHelper;

				if (isset($parsedLinked['id']))
				{
					$itemid    = $tjlmsCoursesHelper->getCourseItemid($parsedLinked['id'], $layout);
				}

				if (!$itemid)
				{
					$link = 'index.php?option=com_tjlms&view=courses&courses_to_show=all';
				}
			}

			if ($parsedLinked['view'] == 'buy' || $parsedLinked['view'] == 'certificate' ||  $parsedLinked['view'] == 'attempts')
			{
				$tjlmsCoursesHelper   = new TjlmsCoursesHelper;

				if (isset($parsedLinked['course_id']))
				{
					$itemid    = $tjlmsCoursesHelper->getCourseItemid($parsedLinked['course_id'], $layout);
				}
			}

			if ($parsedLinked['view'] == 'lesson')
			{
				$tjlmLessonHelper  = new TjlmsLessonHelper;

				if ($parsedLinked['lesson_id'])
				{
					$itemid    = $tjlmLessonHelper->getLessonItemid($parsedLinked['lesson_id']);
				}
			}
		}

		if (!$itemid)
		{
			$mainframe = Factory::getApplication();

			if ($mainframe->isClient('site'))
			{
				$menu     = $mainframe->getMenu();
				$menuItem = $menu->getItems('link', $link, true);

				if (!empty($menuItem))
				{
					$itemid = $menuItem->id;
				}
			}

			if (!$itemid)
			{
				try
				{
					$db    = Factory::getDBO();
					$query = $db->getQuery(true);
					$query->select($db->quoteName('id'));
					$query->from($db->quoteName('#__menu'));
					$query->where($db->quoteName('link') . ' LIKE ' . $db->Quote($link));
					$query->where($db->quoteName('published') . '=' . $db->Quote(1));
					$query->where($db->quoteName('type') . '=' . $db->Quote('component'));
					$db->setQuery($query);
					$itemid = $db->loadResult();
				}
				catch (Exception $e)
				{
					return false;
				}
			}

			if (!$itemid)
			{
				$input  = Factory::getApplication()->input;
				$itemid = $input->get('Itemid', 0);
			}
		}

		return $itemid;
	}

	/**
	 * Function to get Order info
	 *
	 * @param   INT  $orderid  Order ID
	 * @param   INT  $step_id  Checkout step
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getorderinfo($orderid = '0', $step_id = '')
	{
		$db             = Factory::getDBO();
		$comtjlmsHelper = new comtjlmsHelper;

		if (empty($orderid))
		{
			return 0;
		}

		try
		{
			if ($step_id == 'step_select_subsplan')
			{
				$query = $db->getQuery(true);
				$query->select('o.*');
				$query->from($db->qn('#__tjlms_orders', 'o'));
				$query->where($db->qn('o.id') . '=' . $db->q((int) $orderid));

				$db->setQuery($query);
				$orderlist['order_info'] = $db->loadObjectList();

				return $orderlist;
			}
			else
			{
				$query = $db->getQuery(true);
				$query->select('o.*,u.*,o.order_id as orderid_with_prefix');
				$query->from($db->qn('#__tjlms_orders', 'o'));
				$query->join('LEFT', '#__tjlms_users as u ON o.id = u.order_id');
				$query->where($db->qn('o.id') . '=' . $db->q((int) $orderid));

				$db->setQuery($query);
				$order_result = $db->loadObjectList();

				if (empty($order_result))
				{
					return;
				}

				$tjlmsparams = ComponentHelper::getParams('com_tjlms');
				$dateFormat  = $tjlmsparams->get('date_format_show', 'Y-m-d H:i:s');

				$orderlist['order_info']                 = $order_result;
				$orderlist['order_info'][0]->local_cdate = HTMLHelper::date($orderlist['order_info'][0]->cdate, $dateFormat);

				if (isset($orderlist['order_info'][0]->country_code) && !empty($orderlist['order_info'][0]->country_code))
				{
					$orderlist['order_info'][0]->country_code = $comtjlmsHelper->getCountryById($orderlist['order_info'][0]->country_code);
				}

				if (isset($orderlist['order_info'][0]->state_code) && !empty($orderlist['order_info'][0]->state_code))
				{
					$orderlist['order_info'][0]->state_code = $comtjlmsHelper->getRegionById($orderlist['order_info'][0]->state_code);
				}

				$query = $db->getQuery(true);
				$query->select('i.plan_id,CONCAT(s.duration," ",s.time_measure) as order_item_name, s.price');
				$query->from($db->qn('#__tjlms_order_items', 'i'));
				$query->join('LEFT', '#__tjlms_subscription_plans as s ON s.id=i.plan_id');
				$query->where($db->qn('i.order_id') . '=' . $db->q((int) $orderid));
				$query->group($db->qn('i.plan_id'));

				$db->setQuery($query);
				$orderlist['items'] = $db->loadObjectList();

				return $orderlist;
			}
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * This function Checks whether order user and current logged use is same or not
	 *
	 * @param   INT  $orderuser  User ID
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function getorderAuthorization($orderuser)
	{
		$user = Factory::getUser();

		if ($user->id == $orderuser)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * This function get the view path
	 *
	 * @param   STRING  $component      Component name
	 * @param   STRING  $viewname       View name
	 * @param   STRING  $layout         Layout
	 * @param   STRING  $searchTmpPath  Site
	 * @param   STRING  $useViewpath    Site
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function getViewpath($component, $viewname, $layout = 'default', $searchTmpPath = 'SITE', $useViewpath = 'SITE')
	{
		$app = Factory::getApplication();

		$searchTmpPath = ($searchTmpPath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$useViewpath   = ($useViewpath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;

		$layoutname = $layout . '.php';

		$override = $searchTmpPath . '/' . 'templates' . '/' . $app->getTemplate() . '/' . 'html' . '/' . $component . '/' . $viewname . '/' . $layoutname;

		if (File::exists($override))
		{
			return $view = $override;
		}
		else
		{
			return $view = $useViewpath . '/' . 'components' . '/' . $component . '/' . 'views' . '/' . $viewname . '/' . 'tmpl' . '/' . $layoutname;
		}
	}

	/**
	 * Function ised to get the way to display price
	 *
	 * @param   String  $price  Amount to be displayed
	 * @param   STRING  $curr   Currency
	 *
	 * @return formatted price-currency string
	 *
	 * @since  1.0.0
	 */
	public function getFromattedPrice($price, $curr = null)
	{
		$curr_sym                   = $this->getCurrencySymbol();
		$params                     = ComponentHelper::getParams('com_tjlms');
		$currency_display_format    = $params->get('currency_display_format');
		$currency_display_formatstr = '';
		$currency_display_formatstr = str_replace('{AMOUNT}', " " . $price, $currency_display_format);
		$currency_display_formatstr = str_replace('{CURRENCY_SYMBOL}', " " . $curr_sym, $currency_display_formatstr);
		$html                       = '';
		$html                       = $currency_display_formatstr;

		return $html;
	}

	/**
	 * Function used to get the currency symbol
	 *
	 * @param   STRING  $currency  Currency
	 *
	 * @return  STRING  Currency symbol
	 *
	 * @since  1.0.0
	 */
	public function getCurrencySymbol($currency = '')
	{
		$params   = ComponentHelper::getParams('com_tjlms');
		$curr_sym = $params->get('currency_symbol');

		if (empty($curr_sym))
		{
			$curr_sym = $params->get('currency');
		}

		return $curr_sym;
	}

	/**
	 * Function used to ID from order id
	 *
	 * @param   INT  $order_id  Currency
	 *
	 * @return  INT  Order Id
	 *
	 * @since  1.0.0
	 */
	public function getIDFromOrderID($order_id)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select($db->qn('id'));
			$query->from($db->qn('#__tjlms_orders'));
			$query->where($db->qn('order_id') . '=' . $db->q($order_id));

			$db->setQuery($query);
			$result = $db->loadResult();

			return $result;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get number of likes for an item.
	 *
	 * @param   INT     $item_id  Item id
	 * @param   STRING  $element  Element name
	 *
	 * @return  INT  Likes number
	 *
	 * @since  1.0.0
	 */
	public function getLikesForItem($item_id, $element)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn('l.like_cnt'));
			$query->from($db->qn('#__jlike_content', 'l'));
			$query->where($db->qn('l.element') . '=' . $db->q($element));
			$query->where($db->qn('l.element_id') . '=' . $db->q((int) $item_id));
			$db->setQuery($query);
			$likesforCourse = $db->loadResult();

			if (empty($likesforCourse))
			{
				$likesforCourse = 0;
			}

			return $likesforCourse;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get number of likes and Dislikes for an item.
	 *
	 * @param   INT     $item_id  Item id
	 * @param   STRING  $element  Element name
	 *
	 * @return  ARRAY  Likes number
	 *
	 * @since  1.0.0
	 */
	public function getItemJlikes($item_id, $element)
	{
		$result = array();

		if ($item_id &&  $element)
		{
			$db = Factory::getDBO();
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
			$jlikeContent = Table::getInstance('Content', 'JlikeTable', array('dbo', $db));
			$jlikeContent->load(array('element' => $element, 'element_id' => (int) $item_id));

			$result['likes']    = !empty($jlikeContent->like_cnt) ? (int) $jlikeContent->like_cnt : 0;
			$result['dislikes'] = !empty($jlikeContent->dislike_cnt) ? (int) $jlikeContent->dislike_cnt : 0;
		}

		return $result;
	}

	/**
	 * Function used as a push activity into Shika activity stream.
	 *
	 * @param   INT     $actor_id       user who perform the action ID
	 * @param   STRING  $action         action performed by the user
	 * @param   INT     $parent_id      Parent element ID.
	 * @param   STRING  $element_title  title for the element
	 * @param   INT     $element_id     Child element ID
	 * @param   STRING  $element_url    Child element URL
	 * @param   STRING  $params         additional info if provided
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function addActivity($actor_id, $action, $parent_id = 0, $element_title = '', $element_id = '', $element_url = '', $params = '')
	{
		try
		{
			$db = Factory::getDBO();

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$activityTable = Table::getInstance('activity', 'TjlmsTable', array('dbo', $db));

			$data              = new stdClass;
			$data->actor_id    = $actor_id;
			$data->action      = $action;
			$data->parent_id   = $parent_id;
			$data->element     = $element_title;
			$data->element_id  = $element_id;
			$data->element_url = $element_url;
			$data->params      = $params;
			$data->added_time  = Factory::getDate()->toSql();

			if ($activityTable->save($data))
			{
				return true;
			}

			return false;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get social library object depending on the integration set.
	 *
	 * @param   STRING  $integration_option  Soical integration set
	 *
	 * @return  Object Soical library object
	 *
	 * @since 1.0.0
	 */
	public function getSocialLibraryObject($integration_option = '')
	{
		if (!$integration_option)
		{
			$params             = $this->getcomponetsParams();
			$integration_option = $params->get('social_integration', 'joomla');
		}

		if ($integration_option == 'jomsocial')
		{
			$SocialLibraryObject = new JSocialJomSocial;
		}
		elseif ($integration_option == 'easysocial')
		{
			$SocialLibraryObject = new JSocialEasySocial;
		}
		else
		{
			$SocialLibraryObject = new JSocialJoomla;
		}

		return $SocialLibraryObject;
	}

	/**
	 * This function extracts the non-tags string and returns a correctly formatted string
	 * It can handle all html entities e.g. &amp;, &quot;, etc..
	 *
	 * @param   string        $s       To do
	 * @param   integer       $srt     To do
	 * @param   integer       $len     To do
	 * @param   bool/integer  $strict  If this is set to 2 then the last sentence will be completed.
	 * @param   string        $suffix  A string to suffix the value, only if it has been chopped.
	 *
	 * @return  STRING
	 *
	 * @since  1.0.0
	 */
	public function html_substr($s, $srt, $len = null, $strict = false, $suffix = null)
	{
		if (is_null($len))
		{
			$len = strlen($s);
		}

		$string = preg_replace_callback("#>([^<]+)<#",
					function ($a) use ($len, $srt)
					{
						static $strlen = 0;

						if ( $strlen >= $len)
						{
							return "><";
						}

						$html_str = html_entity_decode($a[1]);
						$subsrt   = max(0, ($srt - $strlen));

						$sublen = (
									empty($strict) ? $len - $strlen :
									max(
										@strpos(
										$html_str, ($strict === 2 ? '.' : ' ') .
										($len - $strlen + $subsrt - 1)
										), $len - $strlen
									)
								);

						$new_str = substr($html_str, $subsrt, $sublen);

						$strlen += $new_str_len = strlen($new_str);

						$suffix = (!empty($suffix) ? ($new_str_len === $sublen? $suffix : "") : "");

						return ">" . htmlentities($new_str, ENT_QUOTES, "UTF-8") . $suffix . "<";
					},
					">$s<"
				);

		return preg_replace(
				array(
						"#<[^/][^>]+>(?R)*</[^>]+>#",
						"#(<(b|h)r\s?/?>){2,}$#is"
					),
					"",
					trim(
						rtrim(
							ltrim(
								$string,
							">"),
						"<")
					)
				);
	}

	/**
	 * Function to course order details
	 *
	 * @param   String  $where  Where condition for query
	 *
	 * @return  mixed Details of course order or boolean
	 *
	 * @snce 1.0.0
	 */
	public function getallCourseDetailsByOrder($where = '')
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('c.id as course_id,c.title,c.image,c.storage,o.*,oi.*');
			$query->from($db->qn('#__tjlms_orders', 'o'));
			$query->join('LEFT', '#__tjlms_order_items as oi ON oi.order_id = o.id');
			$query->join('LEFT', '#__tjlms_courses as c ON c.id = oi.course_id');

			$query->where($where);
			$db->setQuery($query);

			return $db->loadObjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to get Country
	 *
	 * @param   INT  $countryId  Country Code
	 *
	 * @return  STRING Country name
	 *
	 * @snce 1.0.0
	 */
	public function getCountryById($countryId)
	{
		$TjGeoHelper = JPATH_ROOT . DS . 'components/com_tjfields/helpers/geo.php';

		if (!class_exists('TjGeoHelper'))
		{
			JLoader::register('TjGeoHelper', $TjGeoHelper);
			JLoader::load('TjGeoHelper');
		}

		$this->TjGeoHelper = new TjGeoHelper;

		return $this->TjGeoHelper->getCountryNameFromId($countryId);
	}

	/**
	 * Function to get region
	 *
	 * @param   INT  $regionId  Region Code
	 *
	 * @return  STRING region name
	 *
	 * @snce 1.0.0
	 */
	public function getRegionById($regionId)
	{
		$TjGeoHelper = JPATH_ROOT . DS . 'components/com_tjfields/helpers/geo.php';

		if (!class_exists('TjGeoHelper'))
		{
			JLoader::register('TjGeoHelper', $TjGeoHelper);
			JLoader::load('TjGeoHelper');
		}

		$this->TjGeoHelper = new TjGeoHelper;

		return $this->TjGeoHelper->getRegionNameFromId($regionId);
	}

	/**
	 * Function to send mail
	 *
	 * @param   STRING  $recipient       Email address of reciever
	 * @param   STRING  $subject         Email Subject
	 * @param   STRING  $body            Email Body
	 * @param   STRING  $bcc_string      BCC Email address
	 * @param   INT     $singlemail      Single mail
	 * @param   STRING  $attachmentPath  Attachmen Path
	 * @param   STRING  $cc_array        CC Email address
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function sendmail($recipient, $subject, $body, $bcc_string, $singlemail = 1, $attachmentPath = "", $cc_array = array())
	{
		jimport('joomla.utilities.utility');
		$mainframe = Factory::getApplication();

		try
		{
			if (!$mainframe->getCfg('mailfrom'))
			{
				return JError::raiseError(404, Text::_('COM_TJLMS_EMAIL_ERROR_NO_FROMEMAIL'));
			}

			$from      = $mainframe->getCfg('mailfrom');
			$fromname  = $mainframe->getCfg('fromname');
			$recipient = trim($recipient);
			$mode      = 1;
			$cc        = array();
			$bcc       = array();

			if ($singlemail == 1)
			{
				if ($bcc_string)
				{
					$bcc = explode(',', $bcc_string);
				}
				else
				{
					$bcc = array(
						'0' => $mainframe->getCfg('mailfrom')
					);
				}
			}

			if (!empty($cc_array))
			{
				$cc = $cc_array;
			}

			$attachment = null;

			if (!empty($attachmentPath))
			{
				$attachment = $attachmentPath;
			}

			return Factory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment);
		}
		catch (Exception $e)
		{
			JError::raiseError(404, $e->getMessage());
		}

		return true;
	}

	/**
	 * Function to get html and send invoice mail
	 *
	 * @param   INT  $id  Order ID
	 *
	 * @return  boolean
	 *
	 * @since 1.0.0
	 */
	public function sendInvoiceEmail($id)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$orderItemid    = $comtjlmsHelper->getItemId('index.php?option=com_tjlms&view=orders');
		$order          = $comtjlmsHelper->getorderinfo($id);

		$TjlmsCoursesHelper          = new TjlmsCoursesHelper;
		$this->order_mail['courses'] = $TjlmsCoursesHelper->getcourseInfo($order['order_info'][0]->course_id);
		$this->order_mail['order']   = $order['order_info'][0];

		$this->orderinfo        = $order['order_info'];
		$this->orderitems       = $order['items'];
		$this->orders_site      = 1;
		$this->orders_email     = 1;
		$this->order_authorized = 1;
		$billemail = '';

		if ($this->orderinfo[0]->address_type == 'BT')
		{
			$billemail = $this->orderinfo[0]->user_email;
		}
		elseif ($this->orderinfo[1]->address_type == 'BT')
		{
			$billemail = $this->orderinfo[1]->user_email;
		}

		$oWithSuf  = $order['order_info'][0]->orderid_with_prefix;
		$processor = $order['order_info'][0]->processor;
		$orderUrl  = 'index.php?option=com_tjlms&view=orders&layout=order&orderid=' . $oWithSuf . '&processor=' . $processor . '&Itemid=' . $orderItemid;

		$currenturl = Uri::root() . substr(Route::_($orderUrl, false), strlen(Uri::base(true)) + 1);
		$body       = Text::_('COM_TJLMS_INVOICE_EMAIL_BODY');
		$status     = $order['order_info'][0]->status;

		if ($status == 'I')
		{
			$body = Text::_('COM_TJLMS_ORDER_PLACED_EMAIL_BODY');
		}

		$invoicebody = TjMail::TagReplace($body, $this->order_mail);

		$invoicehtml = '<div class=""><div><span>' . $invoicebody . '</span></div>';

		// Check for view override
		$view = $comtjlmsHelper->getViewpath('com_tjlms', 'orders', 'invoice', 'SITE', 'SITE');
		ob_start();
		$usedinemail = 1;
		include $view;
		$invoicehtml .= ob_get_contents();
		ob_end_clean();

		$invoicehtml .= '<div class=""><div><span>' . Text::_("COM_TJLMS_INVOICE_LINK") . '</span></div>';
		$invoicehtml .= '<div><span><a href="' . $currenturl . '">' . Text::_("COM_TJLMS_CLICK_HERE") . '</a></span></div></div>';

		$subject = Text::sprintf('COM_TJLMS_INVOICE_EMAIL_SUBJECT');

		if ($status == 'I')
		{
			$subject = Text::sprintf('COM_TJLMS_ORDER_PLACED_EMAIL_SUBJECT');
		}

		$subject = TjMail::TagReplace($subject, $this->order_mail);

		// TRIGGER After Process Payment. Call the plugin and get the result
		PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('tjlms_OnBeforeInvoiceEmail', array(
																	$billemail,
																	$subject,
																	$invoicehtml
																)
															);

		// SEND INVOICE EMAIL
		$comtjlmsHelper->sendmail($billemail, $subject, $invoicehtml, '', 0, '');

		return true;
	}

	/**
	 * Function to add User to social groups
	 *
	 * @param   INT  $actor_id   Course ID
	 * @param   INT  $course_id  Course ID
	 * @param   INT  $state      state
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function addUserToGroup($actor_id, $course_id, $state = 1)
	{
		$tjlmsCoursesHelper = new TjlmsCoursesHelper;
		$groupId            = $tjlmsCoursesHelper->getCourseColumn($course_id, 'group_id');

		if (empty($groupId) || (is_object($groupId) && $groupId->group_id == 0))
		{
			return false;
		}
		else
		{
			$this->sociallibraryobj->addMemberToGroup($groupId->group_id, Factory::getUser($actor_id), $state);
		}

		return true;
	}

	/**
	 * This function return array of js files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$jsFilesArray                  Js file's array.
	 * @param   int    &$firstThingsScriptDeclaration  load script 1st
	 *
	 * @return   ARRAY  $jsFilesArray All JS files to be load
	 *
	 * @since  1.0.0
	 */
	public function getTjlmsJsFiles(&$jsFilesArray, &$firstThingsScriptDeclaration)
	{
		$app       = Factory::getApplication();
		$input     = $app->input;
		$option    = $input->getCmd('option', '');
		$view      = $input->getCmd('view', '');
		$extension = $input->getCmd('extension', '');

		$config = Factory::getConfig();
		$debug  = $config->get('debug');

		$loadminifiedJs = '';

		if ($debug == 0)
		{
			$loadminifiedJs = '.min';
		}

		// Backend Js files
		if ($app->isClient('administrator'))
		{
			if ($option == "com_tjlms")
			{
				$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlms_admin.js';

				// Load the view specific js
				switch ($view)
				{
					// @TODO - get rid off two auto.js files
					case "modules":
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/jquery.form.js';
							$jsFilesArray[] = 'media/techjoomla_strapper/js/akeebajqui.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/ajax_file_upload.js';

							// $jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjmodules.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						break;
					case 'attemptreport':
					case 'course':
					case 'coupon':
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						break;

						case "dashboard":
						case "teacher_report":
							$jsFilesArray[] = 'components/com_tjlms/assets/js/morris.min.js';
							$jsFilesArray[] = 'components/com_tjlms/assets/js/raphael.min.js';
							$jsFilesArray[] = 'media/system/js/validate.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						break;
				}
			}

			if ($option == "com_categories" && ($extension == "com_tjlms" || $extension == "com_tmt.questions"))
			{
				$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/cat_helper.js';
			}
		}
		else
		{
			if ($option == "com_tjlms")
			{
				// Needed for all view
				$jsFilesArray[]    = 'media/com_tjlms/js/tjlms' . $loadminifiedJs . '.js';
				//$jsFilesArray[]    = 'components/com_tjlms/assets/js/tjService.js';// DPE hack
				$jsFilesArray[]    = 'components/com_tjlms/assets/js/sco' . $loadminifiedJs . '.js';
				$tjAnalyticsPlugin = PluginHelper::getPlugin('system', 'tjanalytics');

				// Load the view specific js
				switch ($view)
				{
					case "buy":
						$jsFilesArray[] = 'components/com_tjlms/assets/js/fuelux2.3loader.min.js';
						$jsFilesArray[] = 'components/com_tjlms/assets/js/steps' . $loadminifiedJs . '.js';

						if ($tjAnalyticsPlugin)
						{
							$jsFilesArray[] = 'media/tjanalytics/js/google.min.js';
						}

					break;

					case "coupon":
						$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlms_admin.js';
					break;

					case "course":
						// Check id native sharing is enable
						$tjlmsparams = ComponentHelper::getParams('com_tjlms');

						if ($tjlmsparams->get('social_sharing') && $tjlmsparams->get('social_shring_type') == 'native')
						{
							$jsFilesArray[] = 'components/com_tjlms/assets/js/native_share' . $loadminifiedJs . '.js';
						}

						if ($tjAnalyticsPlugin)
						{
							$jsFilesArray[] = 'media/tjanalytics/js/google.min.js';
						}

					break;

					case "orders":
						if ($tjAnalyticsPlugin)
						{
							$jsFilesArray[] = 'media/tjanalytics/js/google.min.js';
						}
					break;
				}
			}
		}

		$reqURI = Uri::root();

		// If host have wwww, but Config doesn't.
		if (isset($_SERVER['HTTP_HOST']))
		{
			if ((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
			{
				$reqURI = str_replace("://", "://www.", $reqURI);
			}
			elseif ((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
			{
				// Host do not have 'www' but Config does
				$reqURI = str_replace("www.", "", $reqURI);
			}
		}

		// Defind first thing script declaration.
		$loadFirstDeclarations          = "var root_url = '" . $reqURI . "';";
		$firstThingsScriptDeclaration[] = $loadFirstDeclarations;

		return $jsFilesArray;
	}

	/**
	 * This function return array of css files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$cssFilesArray  Css file's array.
	 *
	 * @return   ARRAY  $cssFilesArray All Css files to be load
	 *
	 * @since  1.0.0
	 */
	public function getTjlmsCssFiles(&$cssFilesArray)
	{
		$app    = Factory::getApplication();
		$input  = $app->input;
		$option = $input->getCmd('option', '');
		$view   = $input->getCmd('view', '');

		$config = Factory::getConfig();
		$debug  = $config->get('debug');

		$loadminifiedCss = '';

		if ($debug == 0)
		{
			$loadminifiedCss = '.min';
		}

		// Backend Css files
		if ($app->isClient('administrator'))
		{
			if ($option == "com_tjlms")
			{
				$cssFilesArray[] = 'media/com_tjlms/css/common.css';
				$cssFilesArray[] = 'media/com_tjlms/font-awesome/css/font-awesome.min.css';
				$cssFilesArray[] = 'media/com_tjlms/css/tjlms_backend.css';
				$cssFilesArray[] = 'media/com_tjlms/vendors/artificiers/artficier.css';

				switch ($view)
				{
					case 'dashboard':
						// $cssFilesArray[] = 'media/com_tjlms/bootstrap3/css/bootstrap.min.css';
						$cssFilesArray[] = 'media/techjoomla_strapper/css/bootstrap.j3.css';
						$cssFilesArray[] = 'media/com_tjlms/css/tjdashboard-sb-admin.css';
					break;
				}
			}
		}
		else
		{
			if ($option == "com_tjlms")
			{
				$cssFilesArray[] = 'media/com_tjlms/css/common.css';
				$cssFilesArray[] = 'media/com_tjlms/css/tjlms' . $loadminifiedCss . '.css';
				$cssFilesArray[] = 'media/com_tjlms/font-awesome/css/font-awesome.min.css';

				switch ($view)
				{
					case 'buy':
						$cssFilesArray[] = 'components/com_tjlms/assets/css/tjlms_steps' . $loadminifiedCss . '.css';
						$cssFilesArray[] = 'components/com_tjlms/assets/css/fuelux2.3.1' . $loadminifiedCss . '.css';
						break;
				}
			}
		}

		return $cssFilesArray;
	}

	/**
	 * Converts date in UTC
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return  date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInUtc($date)
	{
		// Change date in UTC
		$user   = Factory::getUser();
		$config = Factory::getConfig();
		$offset = $user->getParam('timezone', $config->get('offset'));

		if (!empty($date) && $date != '0000-00-00 00:00:00')
		{
			$udate = Factory::getDate($date, $offset);
			$date  = $udate->toSQL();
		}

		return $date;
	}

	/**
	 * converts date into local time
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return   date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInLocal($date)
	{
		if (!empty($date) && $date != '0000-00-00 00:00:00')
		{
			// Create JDate object set to now in the users timezone.
			$date = HTMLHelper::date($date, 'Y-m-d H:i:s', true);
		}

		return $date;
	}

	/**
	 * SOrt given array with the provided column and provided order
	 *
	 * @param   ARRAY   $array   array of data
	 * @param   STRING  $column  column name
	 * @param   STRING  $order   order in which array has to be sort
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	public function multi_d_sort($array, $column, $order)
	{
		if (isset($array) && count($array))
		{
			foreach ($array as $key => $row)
			{
				$orderby[$key] = $row->$column;
			}

			if ($order == 'asc')
			{
				array_multisort($orderby, SORT_ASC, $array);
			}
			else
			{
				array_multisort($orderby, SORT_DESC, $array);
			}
		}

		return $array;
	}

	/**
	 * Wrapper to Route to handle itemid We need to try and capture the correct itemid for different view
	 *
	 * @param   string   $url    Absolute or Relative URI to Joomla resource.
	 * @param   boolean  $xhtml  Replace & by &amp; for XML compliance.
	 * @param   integer  $ssl    Secure state for the resolved URI.
	 *
	 * @return  mixed url with Itemid
	 *
	 * @since  1.0
	 */
	public function tjlmsRoute($url, $xhtml = true, $ssl = null)
	{
		static $tjlmsitemid = array();

		$url_components = parse_url($url);
		parse_str($url_components['query'], $params);
	
		if (!empty($params['id']) || !empty($params['cid']))
		{
			$id = !empty($params['id']) ? $params['id'] : $params['cid'];
			$tjlmsitemid[$url] = self::getcourseItemId($id);
		}
		elseif (empty($tjlmsitemid[$url]))
		{
			$tjlmsitemid[$url] = self::getitemid($url);	
		}

		$pos = strpos($url, '#');

		if ($pos === false)
		{
			if (isset($tjlmsitemid[$url])
				&&  (strpos($url, 'Itemid=') === false
				&& strpos($url, 'com_tjlms') !== false))
			{
				$url .= '&Itemid=' . $tjlmsitemid[$url];
			}
		}
		elseif (isset($tjlmsitemid[$url]))
		{
			$url = str_ireplace('#', '&Itemid=' . $tjlmsitemid[$url] . '#', $url);
		}

		$routedUrl = Route::link('site', $url, $xhtml, $ssl, true);

		return $routedUrl;
	}

	/**
	 * Method to log the comment in provided file
	 *
	 * @param   String  $filename  filename
	 * @param   String  $filepath  filepath
	 * @param   Array   $params    params : Params includes userid, logEntryTitle, desc, component, logType
	 *
	 * @return  VOID
	 *
	 * @since  1.0
	 */
	public function techjoomlaLog($filename, $filepath, $params = array())
	{
		$userid  = $params['userid'];
		$desc    = $params['desc'];

		$options = "{DATE}\t{TIME}\t{PRIORITY}\t{USER}\t{DESC}";
		jimport('joomla.log.log');
		Log::addLogger(
				array(
					'text_file' => $filename,
					'text_entry_format' => $options,
					'text_file_path' => $filepath
				),
				Log::ALL, $params['component']
			);

		$logEntry = new LogEntry(
						$params['logEntryTitle'], $params['logType'], $params['component']
					);
		$logEntry->desc = json_encode($desc);
		$logEntry->user = $userid;
		Log::add($logEntry);
	}

	/**
	 * Function used to get users enrollment and account details
	 *
	 * @param   INT  $courseId       Course ID
	 * @param   INT  $enrolled_user  USER ID
	 *
	 * @return  mixed  $getEnrollmentDetails  details or false
	 *
	 * @since  1.0.0
	 */
	public function getEnrollmentDetails($courseId, $enrolled_user)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('e.id as enrollment_id, e.*,u.id as user_id , u.*');
			$query->from($db->quoteName('#__tjlms_enrolled_users', 'e'));
			$query->join('INNER', $db->quoteName('#__users', 'u') .
					' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('e.user_id') . ')');
			$query->where($db->quoteName('e.course_id') . ' = ' . $db->quote($courseId));
			$query->where($db->quoteName('e.user_id') . ' = ' . $db->quote($enrolled_user));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get user details
	 *
	 * @param   INT  $user_id  USER DETAILS
	 *
	 * @return  mixed  USER DETAILS or false
	 *
	 * @since  1.0.0
	 */
	public function getUserDetails($user_id)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('u.*');
			$query->from($db->qn('#__users', 'u'));
			$query->where($db->qn('u.id') . '=' . $db->q((int) $user_id));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get course creator and his account details.
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  mixed  $courseCreator  Creator of the course
	 *
	 * @since  1.0.0
	 */
	public function getCourseCreatorDetails($courseId)
	{
		try
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('c.created_by, u.*');
			$query->from($db->qn('#__tjlms_courses', 'c'));
			$query->join('INNER', '#__users as u ON u.id =c.created_by');
			$query->where($db->qn('c.id') . '=' . $db->q((int) $courseId));
			$db->setQuery($query);

			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to get test data
	 *
	 * @param   INT  $lesson_id  Lesson ID
	 * @param   INT  $user_id    user_id
	 *
	 * @return  mixed  $testData  Test Data or false
	 *
	 * @since  1.0.0
	 */
	public function getTestData($lesson_id, $user_id)
	{
		try
		{
			$testData = new stdClass;

			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select($db->qn(array('t.test_id', 'test.total_marks', 'test.type')));
			$query->from($db->qn('#__tjlms_tmtquiz', 't'));
			$query->join('INNER', '#__tmt_tests as test ON test.id = t.test_id');
			$query->where($db->qn('t.lesson_id') . '=' . $db->q($lesson_id));
			$db->setQuery($query);
			$testData = $db->loadObject();

			if (!empty($testData->type) && $testData->type == 'plain')
			{
				return $testData;
			}
			else
			{
				$ltquery = $db->getQuery(true);
				$ltquery->select($db->qn('t.id'));
				$ltquery->from($db->qn('#__tjlms_lesson_track', 't'));
				$ltquery->where($db->qn('t.lesson_id') . '=' . $db->q((int) $lesson_id));
				$ltquery->where($db->qn('t.user_id') . '=' . $db->q((int) $user_id));
				$ltquery->order($db->qn('id'), 'DESC');
				$ltquery->setLimit(1);

				// Return id for set based quiz
				$query = $db->getQuery(true);
				$query->select($db->qn('ta.test_id'));
				$query->from($db->qn('#__tmt_tests_attendees', 'ta'));
				$query->where('ta.invite_id =(' . $ltquery . ')');

				$db->setQuery($query);
				$testData = $db->loadObject();
			}

			return $testData;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function used to revenue data
	 *
	 * @param   INT  $data  Data
	 *
	 * @return  mixed  $revenueData  Revenue Data or false
	 *
	 * @since  1.0.0
	 */
	public function getrevenueData($data)
	{
		try
		{
			$user     = Factory::getUser();
			$olUserid = $user->id;
			$isroot   = $user->authorise('core.admin');

			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('SUM(o.amount) as amount, DATE(o.cdate) as date');
			$query->from($db->qn('#__tjlms_orders', 'o'));
			$query->join('LEFT', '#__tjlms_courses as c ON c.id = o.course_id');
			$query->where('o.status="C"');

			if (!$isroot)
			{
				$query->where($db->qn('created_by') . '=' . $db->q($olUserid));
			}

			if (isset($data['course_id']) && $data['course_id'] != '')
			{
				$query->where($db->qn('o.course_id') . '=' . $db->q((int) $data['course_id']));
			}

			if (isset($data['start']) && $data['start'] != '' && isset($data['end']) && $data['end'] != '')
			{
				$query->where("( o.cdate BETWEEN " . $db->quote($data['start']) . " AND " . $db->quote($data['end']) . " )");
			}

			$query->group('DATE(o.cdate)');

			$db->setQuery($query);

			return $db->loadObjectlist();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get all Text for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		Text::script('COM_TJLMS_WANTED_TO_APPLY_COP_BUT_NOT_APPLIED');
		Text::script('COM_TJLMS_REQUIRE_FIELDS');
		Text::script('COM_TJLMS_TERMS_AND_CONDITION');
		Text::script('COM_TJLMS_MAX_USER_VALIDATION');
		Text::script('COM_TJLMS_SELECT_FILL_DATES');
		Text::script('COM_TJLMS_INVALID_DATE_FORMAT');
		Text::script('COM_TJLMS_START_DATE_GT_THAN_DUE_DATE');
		Text::script('COM_TJLMS_START_DATE_GT_THAN_TODAY');
		Text::script('COM_TJLMS_SELECT_GROUP_TO_ASSIGN');
		Text::script('COM_TJLMS_SELECT_USER_TO_RECOMMEND');
		Text::script('COM_TJLMS_SELECT_COURSE_TO_ENROLL');
		Text::script('COM_TJLMS_MESSAGE_SELECT_ENROLL_ITEMS');
		Text::script('COM_TJLMS_SELECT_GROUP_TO_ASSIGN');
		Text::script('COM_TJLMS_SELECT_COURSE_TO_ENROLL');
		Text::script('COM_TJLMS_MESSAGE_SELECT_ASSIGN_ITEMS');
		Text::script('COM_TJLMS_SELECT_GROUP_TO_ASSIGN');
		Text::script('COM_TJLMS_INVALID_START_DATE_FORMAT');
		Text::script('COM_TJLMS_INVALID_DUE_DATE_FORMAT');
		Text::script('TJLMS_SELECT_SUBS_PLAN');
		Text::script('COM_TJLMS_ENTER_COP_COD');
		Text::script('COM_TJLMS_ASSESSMENTS_SCORE');
		Text::script('COM_TJLMS_PREV_BUTTON');
		Text::script('COM_TJLMS_ASSESSMENTS_ARE_YOU_SURE');
		Text::script('COM_TJLMS_ALLOWED_FILE_EXTENSION_ERROR_MSG');
		Text::script('COM_TJLMS_ALLOWED_FILE_SIZE_ERROR_MSG');
		Text::script('COM_TJLMS_MAX_NUMBER_OF_FILE_UPLOAD_ERROR_MSG');

		// 1.3.8
		Text::script('COM_TJLMS_ASSESSMENT_MARKS_MISMATCH');
		Text::script('COM_TJLMS_SUCCESS_UPLOAD');
		Text::script('COM_TJLMS_QUIZ_CONFIRM_BOX');
		Text::script('COM_TJLMS_MIN_NO_OF_ASSESSMENT_VALIDATION_MSG');
		Text::script('COM_TJLMS_REMOVE_ASSOCIATE_FILE_MESSAGE');
		Text::script('COM_TJLMS_MESSAGE_SELECT_RECO_ITEMS');
	}

	/**
	 * Function to get order status
	 *
	 * @param   INT  $order_id  Order ID
	 *
	 * @return  String of result
	 *
	 * @since   1.0.0
	 */
	public function getOrderStatus($order_id)
	{
		// Get a db connection.
		$db = Factory::getDbo();

		// Add Table Path
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		$orderTbl = Table::getInstance('Orders', 'TjlmsTable', array('dbo', $db));
		$orderTbl->load(array('id' => (int) $order_id));

		return $orderTbl->status;
	}

	/**
	 * Function of format number
	 *
	 * @param   int      $n          number
	 * @param   boolean  $precision  precision number to format
	 *
	 * @return  sting  	 Formatted value
	 *
	 * @since   1.0
	 */
	public function custom_number_format($n, $precision = 1)
	{
		if ($n < 1000)
		{
			$n_format = $n;
		}
		elseif ($n < 1000000)
		{
			// Anything less than a million
			$n_format = number_format($n / 1000, $precision) . 'K';
		}
		elseif ($n < 1000000000)
		{
			// Anything less than a billion
			$n_format = number_format($n / 1000000, $precision) . 'M';
		}
		else
		{
			// At least a billion
			$n_format = number_format($n / 1000000000, $precision) . 'B';
		}

		return $n_format;
	}

	/**
	 * Change seconds to readable format
	 *
	 * @param   int  $init  Time in second
	 *
	 * @return  string
	 */
	public function secToHours($init)
	{
		$hours   = floor($init / 3600);
		$minutes = floor(((int) $init / 60) % 60);
		$seconds = $init % 60;

		if ($hours)
		{
			return Text::sprintf('COM_TJLMS_HOURS_FORMAT', $hours, $minutes, $seconds);
		}
		elseif ($minutes)
		{
			return Text::sprintf('COM_TJLMS_MINUTES_FORMAT', $minutes, $seconds);
		}
		else
		{
			return Text::sprintf('COM_TJLMS_SECONDS_FORMAT', $seconds);
		}
	}

	/**
	 * Method to get allow rating to bought the product user
	 *
	 * @param   INTEGER  $access     View ACL Id
	 * @param   BOOLEAN  $recursive  Fetch Child groups as well
	 *
	 * @return mixed
	 *
	 * @since 3.0
	 */
	public function getACLGroups($access, $recursive=true)
	{
		$groups = array();

		if (!empty($access))
		{
			try
			{
				$db		= Factory::getDBO();
				$query 	= $db->getQuery(true);
				$query->select($db->qn('rules'));
				$query->from($db->qn('#__viewlevels'));
				$query->where($db->qn('id') . '=' . $db->q((int) $access));
				$db->setQuery($query);
				$rules = $db->loadResult();
			}
			catch (Exception $e)
			{
				return false;
			}

			if ($rules)
			{
				$rules = json_decode($rules, true);
				ArrayHelper::toInteger($rules);
				$level = false;

				if (!empty($rules))
				{
					$allGroups = UserGroupsHelper::getInstance()->getAll();

					foreach ($allGroups as $groupId => $group)
					{
						if ($recursive && $level !== false && $group->level > $level)
						{
							++$levelStr;
							$levelStr = ($group->level < $levelStr) ? ($group->level) : ($levelStr);
							$groups[$groupId] = $group;
							$group->title  = str_repeat('- ', $levelStr) . $group->title;
						}
						elseif (in_array($groupId, $rules) && !array_key_exists($groupId, $groups))
						{
							$groups[$groupId] = $group;
							$level = $group->level;
							$levelStr = 0;
							$group->title  = str_repeat('- ', $levelStr) . $group->title;
						}
						else
						{
							$level = false;
						}
					}
				}
			}
		}

		return $groups;
	}

	/**
	 * Function to get Item id
	 *
	 * @param   INTEGER  $courseId  Id of the course
	 *
	 * @return  INT  Item id
	 *
	 * @since  4.0.2
	 */
	public function getcourseItemId($courseId)
	{
		$app  = Factory::getApplication();
		$menu = $app->getMenu();
		
		$itemid = 0;
		
		if ($courseId)
		{
			/*Get the itemid of the menu which is pointed to individual course URL*/
			$menuItem = $menu->getItems('link', 'index.php?option=com_tjlms&view=course&id=' . $courseId, true);
			
			if (!empty($menuItem))
			{
				return $menuItem->id;
			}

			/*Get the itemid of the menu which is pointed to course category URL*/
			$tjlmsCoursesHelper = new tjlmsCoursesHelper;
			$courseInfo = $tjlmsCoursesHelper->getcourseInfo($courseId);
			
			if (is_object($courseInfo))
			{
				if ($courseInfo->published == 1)
				{
					$menuItem = $menu->getActive();
						
					if (!empty($menuItem))
					{
						if ($menuItem->getParams()->get('show_courses_from_cat') == $courseInfo->catid)
						{
							return $menuItem->id;
						}

						$cat_details = $this->getCatDetail($courseInfo->catid);

						if ($menuItem->getParams()->get('show_courses_from_cat') == $cat_details['parent_id'])
						{
							return $menuItem->id;
						}
					}
				}
			}

			/*Get the itemid of the menu which is pointed to courses URL*/
			$menuItem = $menu->getActive();

			if ($menuItem)
			{
				return $menuItem->id;
			}
		}

		return $itemid;
	}

	/**
	 * This function return category detail
	 *
	 * @param   INT     $catid      The cat id whose child categories are to be taken
	 *
	 * @param   STRING  $extension  The extension whose cats are to be taken
	 *
	 * @return  array | false
	 *
	 * @since   4.0.2
	 */
	public static function getCatDetail($catid, $extension = 'com_tjlms')
	{
		try
		{
			$db   = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn(array('id','title','parent_id','path')));
			$query->from($db->qn('#__categories'));
			$query->where($db->qn('extension') . ' = ' . $db->q($extension));
			$query->where($db->qn('id') . ' = ' . $db->q((int) $catid));
			$db->setQuery($query);

			return $db->loadAssoc();
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}
