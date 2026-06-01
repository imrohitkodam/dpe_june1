<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2025 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace GSD;

defined('_JEXEC') or die('Restricted Access');

use GSD\Json;
use GSD\Helper;
use GSD\Schemas\Helper as SchemaHelper;
use Joomla\CMS\Factory;
use NRFramework\Conditions\ConditionsHelper;

/**
 * Handles the generation of Google Knowledge Graph structured data for organization information on the front page
 */
class KnowledgeGraph
{
    /**
     * Generates the knowledge graph structured data on the front page of the site
     *
     * @return string|null JSON-LD script or null if not on front page
     */
    public static function get()
    {
		// Get component parameters
		$params = Helper::getParams();

		if (!$kg = $params->extract('kg'))
		{
			return;
		}

		$whereToPublish = $kg->get('publish', 'home');

		// Generate schema on the front page
		if ($whereToPublish == 'home' && !Helper::isFrontPage())
		{
			return;
		}

		
		// Do not generate schema
		if ($whereToPublish == '0')
		{
			return;
		}	

		// Generate schema on custom pages
		if ($whereToPublish == 'custom')
		{
			$conditions = ConditionsHelper::getInstance();
			$pages = $kg->get('publish_pages');

			if (!$conditions->passOne('Joomla\Menu', $pages, 'includes'))
			{
				return;
			}
		}
		

		$siteRepType = $kg->get('type', 'Organization');

		// Prepare social profile URLs
		$sameAs = $kg->get('sameAs');
		if (is_object($sameAs))
		{
			$sameAs = (array) $sameAs;

			if (isset($sameAs['other_profiles']) && !empty($sameAs['other_profiles']))
			{
				$sameAs['other_profiles'] = Helper::makeArrayFromNewLine($sameAs['other_profiles']);
				$sameAs['other_profiles'] = array_filter($sameAs['other_profiles']);
				$sameAs = array_merge($sameAs, $sameAs['other_profiles']);

				unset($sameAs['other_profiles']);
			}

			$sameAs = array_values($sameAs);
			$sameAs = array_unique($sameAs);
			$sameAs = array_filter($sameAs);
		}

		$kg->set('sameAs', $sameAs);
		$kg->set('url', Helper::getSiteURL());

		if (empty($kg->get('name')))
		{
			$kg->set('name', Helper::getSiteName());
		}

		if (empty($kg->get('description')))
		{
			$kg->set('description', Factory::getConfig()->get('MetaDesc'));
		}

		switch ($siteRepType)
		{
			case 'Organization':
				$kg->set('type', 'Organization');
				break;

			case 'Person';
				$kg->remove('type');
				break;
		}
    
        // Convert properties to respective schema format
        $orgSchemaProps = SchemaHelper::getInstance($siteRepType, $kg)->get();

        // Convert schema to JSON-LD format
        $jsonClass = new Json($orgSchemaProps);
        $jsonScript = $jsonClass->generate();

        return $jsonScript;
    }
}