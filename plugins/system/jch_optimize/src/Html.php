<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/joomla-platform
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

namespace JchOptimize\Platform;

defined( '_JEXEC' ) or die( 'Restricted access' );

use JchOptimize\Core\Exception;
use JchOptimize\Core\FileRetriever;
use JchOptimize\Core\Logger;
use JchOptimize\Core\Interfaces\Html as HtmlInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\MenuItem;

class Html implements HtmlInterface
{
	protected $params;

	/**
	 *
	 * @param   Settings  $params
	 */
	public function __construct( $params )
	{
		$this->params = $params;
	}

	/**
	 * Returns HTML of the front page
	 *
	 * @return string
	 */
	public function getHomePageHtml()
	{
		try
		{
			JCH_DEBUG ? Profiler::mark( 'beforeGetHtml' ) : null;

			$response = $this->getHtml( $this->getSiteUrl() );

			JCH_DEBUG ? Profiler::mark( 'afterGetHtml' ) : null;

			return $response;
		}
		catch ( Exception $e )
		{
			Logger::log( $this->getSiteUrl() . ': ' . $e->getMessage(), $this->params );

			JCH_DEBUG ? Profiler::mark( 'afterGetHtml' ) : null;

			throw new \RuntimeException( 'Try reloading the front page to populate the Exclude options' );
		}
	}

	/**
	 *
	 * @return string
	 */
	protected function getSiteUrl()
	{
		$oSiteMenu    = $this->getSiteMenu();
		$oDefaultMenu = $oSiteMenu->getDefault();

		return $this->getMenuUrl( $oDefaultMenu );
	}

	public function getMainMenuItemsHtmls()
	{
		$oSiteMenu    = $this->getSiteMenu();
		$oDefaultMenu = $oSiteMenu->getDefault();

		$aAttributes = array(
			'menutype',
			'type',
			'level',
			'access',
			'home'
		);

		$aValues = array(
			$oDefaultMenu->menutype,
			'component',
			'1',
			'1',
			'0'
		);

		//Only need 5 menu items including the home menu
		$aMenus = array_slice( array_merge( array( $oDefaultMenu ), $oSiteMenu->getItems( $aAttributes, $aValues ) ), 0, 5 );

		$aHtmls = array();
		/** @var MenuItem $oMenuItem */
		foreach ( $aMenus as $oMenuItem )
		{
			$oMenuItem->link = $this->getMenuUrl( $oMenuItem );
			$aHtmls[] = $this->getHtml($oMenuItem->link);
		}

		return $aHtmls;
	}

	protected function getSiteMenu()
	{
		return Factory::getApplication( 'site' )->getMenu( 'site' );
	}

	protected function getMenuUrl( MenuItem $oMenuItem )
	{
		$oSiteRouter  = \JApplicationSite::getRouter();
		$bSefModeTest = version_compare( JVERSION, '4.0', '<' ) && $oSiteRouter->getMode() == JROUTER_MODE_SEF;
		$sMenuUrl     = $bSefModeTest ? 'index.php?Itemid=' . $oMenuItem->id : $oMenuItem->link . '&Itemid=' . $oMenuItem->id;

		return \JRoute::link( 'site', $sMenuUrl, true, \JRoute::TLS_IGNORE, true );
	}

	protected function getHtml( $sUrl )
	{
		$oFileRetriever = FileRetriever::getInstance();
		$sHtml          = $oFileRetriever->getFileContents( $sUrl . '?jchbackend=1' );

		if ( $oFileRetriever->response_code != 200 )
		{
			throw new Exception( 'Failed fetching HTML: ' . $sUrl . ' - Response code: ' . $oFileRetriever->response_code );
		}

		return $sHtml;
	}
}
