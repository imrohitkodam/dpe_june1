<?php

/**
 * @version     admin/classes/extension.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

class WatchfulliExtension
{
	/**
	 * @var object
	 */
	private $extension;

	/**
	 *
	 * @param   object  $extension
	 */
	public function __construct($extension)
	{
		$this->extension = $extension;
	}

	/**
	 * Get the variant (Core/Pro/Free/...)
	 *
	 * @return string
	 */
	public function getVariant()
	{
		switch (strtolower($this->extension->element))
		{
			case 'com_autotweet':
				return $this->getAutotweetVariant();
			case 'com_acymailing':
				return $this->getAcymailingVariant();
			case 'com_falang':
				return $this->getFalangVariant();
			case 'pkg_jcalpro':
				return $this->getJcalproVariant();
			case 'pkg_widgetkit':
				return $this->getWidgetkitVariant();
			case 'pkg_zoo':
				return $this->getZooVariant();
			case 'jch_optimize':
				return $this->getJchVariant();
            case 'easyimageresizer':
                return $this->getEasyImageResizerVariant();
		}

		$variant = $this->getLiveUpdateVariant();

		if ($variant)
		{
			return $variant;
		}

		return $this->getXmlVariant();
	}

    /**
     * @return string
     */
    private function getEasyImageResizerVariant(){
        if (strpos($this->extension->updateServer, 'pro') !== false)
        {
            return 'Pro';
        }
        return 'Free';
    }

    /**
     * @return string|null
     */
    private function getPathVersionFile() {
        $administrator_version_path = JPATH_ADMINISTRATOR . "/components/{$this->extension->element}/version.php";
        if (file_exists($administrator_version_path))
        {
            return $administrator_version_path;
        }

        $site_version_path = JPATH_SITE . "/components/{$this->extension->element}/version.php";
        if (file_exists($site_version_path))
        {
            return $site_version_path;
        }

        $component_version_path = str_replace("/pkg_", "/com_", $administrator_version_path);
        if (file_exists($component_version_path))
        {
            return $component_version_path;
        }

        $plugin_site_path =  JPATH_SITE . '/plugins/' . $this->extension->folder . '/' . $this->extension->element . '/version.php';
        if (file_exists($plugin_site_path))
        {
            return $plugin_site_path;
        }

        return null;
    }

    /**
     * @param $path
     * @param string|null $variableToDefine
     */
    private function loadVersionFile($path, $variableToDefine = null)
    {
        require_once $path;
        if (!empty($variableToDefine)) {
            define($variableToDefine, ${$variableToDefine});
        }
    }

	/**
	 * Return the variant or level field in the extension XML file
	 *
	 * @return string
	 */
	private function getXmlVariant()
	{
		$xml = WatchfulliHelper::readManifest($this->extension);

		if (isset($xml->variant))
		{
			return (string) $xml->variant;
		}

		if (isset($xml->level))
		{
			return (string) $xml->level;
		}

		return '';
	}

	/**
	 * Read version file for Acymailing xml
	 *
	 * @return string
	 */
	private function getAcymailingVariant()
	{
		$xmlPath = JPATH_ADMINISTRATOR . "/components/com_acymailing/acymailing.xml";
		$xml     = simplexml_load_file($xmlPath);

		return (string) $xml->level;
	}

	/**
	 * Get the variant for Autotweet component
	 *
	 * @return string
	 */
	private function getAutotweetVariant()
	{
		if (strpos($this->extension->updateServer, 'update-autotweetng-pro') !== false)
		{
			return 'Pro';
		}

		if (strpos($this->extension->updateServer, 'update-autotweetng-joocial') !== false)
		{
			return 'Joocial';
		}

		return '';
	}

	/**
	 * Get the variant for extensions that use Liveupdate
	 *
	 * @return string
	 */
	private function getLiveUpdateVariant()
	{
        $liveupdate_extensions = ['com_admintools', 'pkg_admintools', 'com_akeeba', 'pkg_akeeba', 'com_akeebabackup', 'pkg_akeebabackup', 'com_akeebasubs', 'com_ars', 'com_ats', 'com_hotspots', 'com_comment', 'com_matukio', 'com_cmigrator', 'com_tiles', 'com_ctransifex', 'acf'];
		// We need to check BEFORE including the "version.php" file, because it
		// may produce a fatal error if it's not in LiveUpdate format
		if (!in_array($this->extension->element, $liveupdate_extensions))
		{
			return '';
		}

        $pathVersionFile = $this->getPathVersionFile();
        if (empty($pathVersionFile))
        {
			return '';
		}

		switch ($this->extension->element)
		{
			case 'com_admintools':
			case 'pkg_admintools':
                $this->loadVersionFile($pathVersionFile);
                return (defined('ADMINTOOLS_PRO') && ADMINTOOLS_PRO) ? 'Pro' : 'Core';
            case 'com_akeeba':
			case 'pkg_akeeba':
            case 'com_akeebabackup':
            case 'pkg_akeebabackup':
			case 'com_ctransifex':
                $this->loadVersionFile($pathVersionFile);
            return (defined('AKEEBA_PRO') && AKEEBA_PRO) || (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO) ? 'Pro' : 'Core';
            case 'com_akeebasubs':
				return defined('AKEEBASUBS_PRO') ? 'Pro' : 'Core';
			case 'com_ars':
				return 'Core';
			case 'com_hotspots':
                $this->loadVersionFile($pathVersionFile);
                return defined('HOTSPOTS_PRO') ? 'Pro' : 'Core';
            case 'com_comment':
                $this->loadVersionFile($pathVersionFile);
                return defined('CCOMMENT_PRO') ? 'Pro' : 'Core';
            case 'acf':
                $this->loadVersionFile($pathVersionFile, 'NR_PRO');
                return defined("NR_PRO") ? 'Pro' : 'Core';
            case 'com_ats':
			case 'com_matukio':
			case 'com_cmigrator':
			case 'com_tiles':
				return 'Pro';
		}

		return '';
	}

	/**
	 * Get JCalPro variant
	 *
	 * @return string
	 */
	private function getJcalproVariant()
	{
		if (strpos($this->extension->updateServer, 'starter/list_stable.xml') !== false)
		{
			return "starter";
		}

		if (strpos($this->extension->updateServer, 'standard/list_stable.xml') !== false)
		{
			return "standard";
		}

		return '';
	}

	/**
	 * Get the Falang variant (Core/Pro)
	 *
	 * @return string
	 */
	private function getFalangVariant()
	{
        $pathVersionFile = $this->getPathVersionFile();
        if (empty($pathVersionFile))
        {
            return '';
        }
        $this->loadVersionFile($pathVersionFile);

		$version = new FalangVersion();

		return $version->_versiontype;
	}

	/**
	 * Get the JCH variant (FREE/PRO). We assume the en-GB language file has
	 * been loaded already.
	 *
	 * @return string
	 */
	private function getJchVariant()
	{
		if (Text::_('PLG_SYSTEM_JCH_OPTIMIZE') == 'System - JCH Optimize')
		{
			return "FREE";
		}
		else
		{
			return "PRO";
		}
	}

	/**
	 * Get the variant for Zoo component
	 *
	 * @return string
	 */
	private function getZooVariant()
	{
		if (empty($this->extension->updateServer))
		{
			return '';
		}

		if (strpos($this->extension->updateServer, 'zoo_full_') !== false)
		{
			return 'Pro';
		}

		if (strpos($this->extension->updateServer, 'zoo_') !== false)
		{
			return 'Free';
		}

		return '';
	}

	/**
	 * Get the variant for Widgetkit component. Please note:
	 * - version 1 has different lite / pro variants but does not
	 *   support remote updates, so we don't care about it
	 * - version 2 has only pro variant and supports remote updates
	 *
	 * @return string
	 */
	private function getWidgetkitVariant()
	{
		if (!empty($this->extension->updateServer))
		{
			return 'Pro';
		}

		return '';
	}

}
