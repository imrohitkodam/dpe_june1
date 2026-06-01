<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Date\Date;

/**
 * Dpe utility class for common methods
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeUtilities
{
	/**
	 * Hold the class instance.
	 *
	 * @var    Object
	 * @since  __DEPLOY_VERSION__
	 */
	private static $instance = null;

	/**
	 * Returns the global Cluster object
	 *
	 * @return  DpeUtilities The object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getInstance()
	{
		if (self::$instance == null)
		{
			self::$instance = new DpeUtilities;
		}

		return self::$instance;
	}

	/**
	 * Get item id of url
	 *
	 * @param   string  $link  link
	 *
	 * @return  int  Itemid of the given link
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItemId($link)
	{
		$itemid = 0;
		$app    = Factory::getApplication();
		$menu   = $app->getMenu();

		if ($app->isClient('site'))
		{
			$items = $menu->getItems('link', $link);

			if (isset($items[0]))
			{
				$itemid = $items[0]->id;
			}
		}

		if (!$itemid)
		{
			try
			{
				$db = Factory::getDBO();
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
			$input  = $app->input;
			$itemid = $input->getInt('Itemid', 0);
		}

		return $itemid;
	}

	/**
	 * Method for Converting timestamp to time ago
	 *
	 * @param   DATETIME  $datetime  Any supported date and time format (2013-05-01 00:22:35, @1367367755)
	 *
	 * @return  string (4 months, 1 hour ago)
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function toLapsed($datetime)
	{
		if ($datetime == null)
		{
			return;
		}

		$nowdate = new Date(null,  'UTC');
		$ago     = new Date($datetime, 'UTC');

		$time    = $nowdate->toUnix(true) - $ago->toUnix(true);

		$tokens = array (
			1		=> 'COM_DPE_LAPSED_SECONDS_COUNT',
			60		=> 'COM_DPE_LAPSED_MINUTES_COUNT',
			3600	=> 'COM_DPE_LAPSED_HOURS_COUNT'
		);

		if ($time == 0)
		{
			return Text::_('COM_DPE_LAPSED_NOW');
		}

		$text = '';

		foreach ($tokens as $unit => $key)
		{
			if ($time < $unit || $time > (3600 * 24))
			{
				continue;
			}

			$units = floor($time / $unit);
			$text = Text::plural($key, $units);
		}

		if (!empty($text))
		{
			return $text;
		}

		return HTMLHelper::_('date', $datetime, (String) DPE::config()->get('dateFormat'), false);
	}

	/**
	 * Function to get extension from mime type
	 *
	 * @param   string  $mime_type  time in seconds
	 *
	 * @return  string
	 */
	public function mimeToExt($mime_type)
	{
		$mapping = array(
		'hqx'	=>	array('application/mac-binhex40','application/mac-binhex','application/x-binhex40','application/x-mac-binhex40'),
		'cpt'	=>	'application/mac-compactpro',
		'csv'	=>	array('text/x-comma-separated-values','text/comma-separated-values',
						'application/octet-stream','application/vnd.ms-excel',
						'application/x-csv','text/x-csv','text/csv','application/csv','application/excel','application/vnd.msexcel','text/plain'
						),
		'bin'	=>	array('application/macbinary','application/mac-binary','application/octet-stream','application/x-binary','application/x-macbinary'),
		'dms'	=>	'application/octet-stream',
		'lha'	=>	'application/octet-stream',
		'lzh'	=>	'application/octet-stream',
		'exe'	=>	array('application/octet-stream','application/x-msdownload'),
		'class'	=>	'application/octet-stream',
		'psd'	=>	array('application/x-photoshop','image/vnd.adobe.photoshop'),
		'so'	=>	'application/octet-stream',
		'sea'	=>	'application/octet-stream',
		'dll'	=>	'application/octet-stream',
		'oda'	=>	'application/oda',
		'pdf'	=>	array('application/pdf','application/force-download','application/x-download','binary/octet-stream'),
		'ai'	=>	array('application/pdf','application/postscript'),
		'eps'	=>	'application/postscript',
		'ps'	=>	'application/postscript',
		'smi'	=>	'application/smil',
		'smil'	=>	'application/smil',
		'mif'	=>	'application/vnd.mif',
		'xls'	=>	'application/vnd.ms-excel',
		'ppt'	=>	array('application/powerpoint','application/vnd.ms-powerpoint','application/vnd.ms-office','application/msword'),
		'pptx'	=> array('application/vnd.openxmlformats-officedocument.presentationml.presentation','application/x-zip','application/zip'),
		'wbxml'	=>	'application/wbxml',
		'wmlc'	=>	'application/wmlc',
		'dcr'	=>	'application/x-director',
		'dir'	=>	'application/x-director',
		'dxr'	=>	'application/x-director',
		'dvi'	=>	'application/x-dvi',
		'gtar'	=>	'application/x-gtar',
		'gz'	=>	'application/x-gzip',
		'gzip'  =>	'application/x-gzip',
		'php'	=>	array('application/x-httpd-php','application/php','application/x-php','text/php','text/x-php','application/x-httpd-php-source'),
		'php4'	=>	'application/x-httpd-php',
		'php3'	=>	'application/x-httpd-php',
		'phtml'	=>	'application/x-httpd-php',
		'phps'	=>	'application/x-httpd-php-source',
		'js'	=>	array('application/x-javascript','text/plain'),
		'swf'	=>	'application/x-shockwave-flash',
		'sit'	=>	'application/x-stuffit',
		'tar'	=>	'application/x-tar',
		'tgz'	=>	array('application/x-tar','application/x-gzip-compressed'),
		'z'	=>	'application/x-compress',
		'xhtml'	=>	'application/xhtml+xml',
		'xht'	=>	'application/xhtml+xml',
		'zip'	=>	array('application/x-zip','application/zip','application/x-zip-compressed','application/s-compressed','multipart/x-zip'),
		'rar'	=>	array('application/x-rar','application/rar','application/x-rar-compressed'),
		'mid'	=>	'audio/midi',
		'midi'	=>	'audio/midi',
		'mpga'	=>	'audio/mpeg',
		'mp2'	=>	'audio/mpeg',
		'mp3'	=>	array('audio/mpeg','audio/mpg','audio/mpeg3','audio/mp3'),
		'aif'	=>	array('audio/x-aiff','audio/aiff'),
		'aiff'	=>	array('audio/x-aiff','audio/aiff'),
		'aifc'	=>	'audio/x-aiff',
		'ram'	=>	'audio/x-pn-realaudio',
		'rm'	=>	'audio/x-pn-realaudio',
		'rpm'	=>	'audio/x-pn-realaudio-plugin',
		'ra'	=>	'audio/x-realaudio',
		'rv'	=>	'video/vnd.rn-realvideo',
		'wav'	=>	array('audio/x-wav','audio/wave','audio/wav'),
		'bmp'	=>	array('image/bmp','image/x-bmp','image/x-bitmap','image/x-xbitmap',
		'image/x-win-bitmap','image/x-windows-bmp','image/ms-bmp','image/x-ms-bmp','application/bmp','application/x-bmp','application/x-win-bitmap'),
		'gif'	=>	'image/gif',
		'jpeg'	=>	array('image/jpeg','image/pjpeg'),
		'jpg'	=>	array('image/jpg','image/pjpeg'),
		'jpe'	=>	array('image/jpeg','image/pjpeg'),
		'png'	=>	array('image/png',  'image/x-png'),
		'tiff'	=>	'image/tiff',
		'tif'	=>	'image/tiff',
		'css'	=>	array('text/css','text/plain'),
		'html'	=>	array('text/html','text/plain'),
		'htm'	=>	array('text/html','text/plain'),
		'shtml'	=>	array('text/html','text/plain'),
		'txt'	=>	'text/plain',
		'text'	=>	'text/plain',
		'log'	=>	array('text/plain','text/x-log'),
		'rtx'	=>	'text/richtext',
		'rtf'	=>	'text/rtf',
		'xml'	=>	array('application/xml','text/xml','text/plain'),
		'xsl'	=>	array('application/xml','text/xsl','text/xml'),
		'mpeg'	=>	'video/mpeg',
		'mpg'	=>	'video/mpeg',
		'mpe'	=>	'video/mpeg',
		'qt'	=>	'video/quicktime',
		'mov'	=>	'video/quicktime',
		'avi'	=>	array('video/x-msvideo','video/msvideo','video/avi','application/x-troff-msvideo'),
		'movie'	=>	'video/x-sgi-movie',
		'doc'	=>	'application/msword',
		'docx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/zip','application/msword','application/x-zip'),
		'dot'	=>	array('application/msword','application/vnd.ms-office'),
		'dotx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip','application/msword'),
		'xlsx'	=>	array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/zip','application/vnd.ms-excel','application/msword','application/x-zip'),
		'word'	=>	array('application/msword','application/octet-stream'),
		'xl'	=>	'application/excel',
		'eml'	=>	'message/rfc822',
		'json'  =>	array('application/json','text/json'),
		'pem'   =>	array('application/x-x509-user-cert','application/x-pem-file','application/octet-stream'),
		'p10'   =>	array('application/x-pkcs10','application/pkcs10'),
		'p12'   =>	'application/x-pkcs12',
		'p7a'   =>	'application/x-pkcs7-signature',
		'p7c'   =>	array('application/pkcs7-mime','application/x-pkcs7-mime'),
		'p7m'   =>	array('application/pkcs7-mime','application/x-pkcs7-mime'),
		'p7r'   =>	'application/x-pkcs7-certreqresp',
		'p7s'   =>	'application/pkcs7-signature',
		'crt'   =>	array('application/x-x509-ca-cert','application/x-x509-user-cert','application/pkix-cert'),
		'crl'   =>	array('application/pkix-crl','application/pkcs-crl'),
		'der'   =>	'application/x-x509-ca-cert',
		'kdb'   =>	'application/octet-stream',
		'pgp'   =>	'application/pgp',
		'gpg'   =>	'application/gpg-keys',
		'sst'   =>	'application/octet-stream',
		'csr'   =>	'application/octet-stream',
		'rsa'   =>	'application/x-pkcs7',
		'cer'   =>	array('application/pkix-cert','application/x-x509-ca-cert'),
		'3g2'   =>	'video/3gpp2',
		'3gp'   =>	'video/3gp',
		'mp4'   =>	'video/mp4',
		'm4a'   =>	'audio/x-m4a',
		'f4v'   =>	'video/mp4',
		'webm'	=>	'video/webm',
		'aac'   =>	'audio/x-acc',
		'm4u'   =>	'application/vnd.mpegurl',
		'm3u'   =>	'text/plain',
		'xspf'  =>	'application/xspf+xml',
		'vlc'   =>	'application/videolan',
		'wmv'   =>	array('video/x-ms-wmv','video/x-ms-asf'),
		'au'    =>	'audio/x-au',
		'ac3'   =>	'audio/ac3',
		'flac'  =>	'audio/x-flac',
		'ogg'   =>	'audio/ogg',
		'kmz'	=>	array('application/vnd.google-earth.kmz','application/zip','application/x-zip'),
		'kml'	=>	array('application/vnd.google-earth.kml+xml','application/xml','text/xml'),
		'ics'	=>	'text/calendar',
		'ical'	=>	'text/calendar',
		'zsh'	=>	'text/x-scriptzsh',
		'7zip'	=>	array('application/x-compressed','application/x-zip-compressed','application/zip','multipart/x-zip'),
		'wma'	=>	array('audio/x-ms-wma','video/x-ms-asf'),
		'jar'	=>	array('application/java-archive','application/x-java-application','application/x-jar','application/x-compressed'),
		'svg'	=>	array('image/svg+xml','application/xml','text/xml'),
		'vcf'	=>	'text/x-vcard',
		'odt'	=>	'application/vnd.oasis.opendocument.text',
		'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet'
		);

		if (($ext = array_search($mime_type, $mapping, true)))
		{
			return $ext;
		}

		foreach ($mapping as $ext => $mimes)
		{
			if (is_array($mimes) && in_array($mime_type, $mimes))
			{
				return $ext;
			}
		}

		return false;
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getLanguageConstant()
	{
		Text::script('COM_DPE_ASSIGNMENT_SELECT_USERS');
		Text::script('COM_DPE_SELECT_DATE_FOR_ASSIGNMENT');
		Text::script('COM_DPE_SELECT_SCHOOL_ROP');
		Text::script('COM_DPE_DEASSIGNMENT_SELECT_USERS');
		Text::script('COM_DPE_NO_USERS');
		Text::script('COM_DPE_DELETE_MESSAGE');
		Text::script('COM_DPE_COMPLIANCE_ASSIGN_USER_DUE_DATE_VALIDATION');
		Text::script('COM_DPE_COMPLIANCE_ASSIGN_USER_CONFIRMATION_MESSAGE');
		Text::script('COM_DPE_COMPLIANCE_DE_ASSIGN_USER_CONFIRMATION_MESSAGE');
		Text::script('COM_MULTIAGENCY_FORM_INVALID_SLA_NAME');
		Text::script('COM_MULTIAGENCY_FORM_INVALID_START_DATE');
	}

	/**
	 * Function to format number with ordinal number suffix
	 *
	 * @param   integer  $num  number
	 * 
	 * @return   string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function addOrdinalNumberSuffix($num)
	{
		if (!in_array(($num % 100), array(11,12,13)))
		{
			switch ($num % 10)
			{
				// Handle 1st, 2nd, 3rd
				case 1:
					return $num . 'st';

				case 2:
					return $num . 'nd';

				case 3:
					return $num . 'rd';
			}
		}

		return $num . 'th';
	}

	/**
	 * Function to convert urls to link from given text
	 *
	 * @param   string  $text  text
	 * 
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function urlToClickableLink($text)
	{
		
		$regPattern = "/(((http|https|ftp|ftps)\:\/\/)|(www\.))[a-zA-Z0-9\-\.]+\.[a-zA-Z]*(\:[0-9]+)?(\/\S*)?/";

		return preg_replace($regPattern, '<br/><a target="blank" rel="nofollow" href="$0" target="_blank">$0</a>', $text);
	}
}
