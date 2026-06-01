<?php
/*
# Plugin Matomo (formerly Piwik) based Plugin by it-conserv.de
# ------------------------------------------------------------------------
# Author    it-conserv.de
# Copyright (C) 2018 it-conserv.de All Rights Reserved.
# License - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: it-conserv.de
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Matomo system plugin
 */
class plgSystemplg_matomo extends JPlugin
{
	/**
	 * Constructor
	 *
	 * For php4 compatibility we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object	$subject The object to observe
	 * @param 	array   $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	public function __construct(&$subject, $param )
	{
		parent::__construct( $subject, $param );

		$hcode = $this->params->def('hcode');
		$optout = $this->params->def('optout');
	}

	/**
	 * Do something onAfterInitialise
	 */
	function onAfterRender()
	{
		$mainframe = JFACTORY::getApplication();
		
		if($mainframe->isClient('administrator') || strpos($_SERVER["PHP_SELF"], "index.php") === false)
		{
			return;
		}

		//$piwik = $this->params->get('tcode', '');
		$hcode = $this->params->def('hcode','');
		$optout = $this->params->def('optout','');
		
		$buffer = $mainframe->getBody();
		
		//ersetze optout
		$buffer = str_replace('{matomo_opt_out}', $optout, $buffer);
		
		//setze analytics code
		$pos = strrpos($buffer, "</head>");
		if($pos > 0)
		{
			$buffer = substr($buffer, 0, $pos).$hcode.substr($buffer, $pos);

			$mainframe->setBody($buffer);
		}

		return true;
		
		
	}

}
?>