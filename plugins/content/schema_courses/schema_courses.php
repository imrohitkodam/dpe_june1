<?php
/**
 * @package     Structured Data - Schema
 * @subpackage  plugin-Content-Schema_Courses
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
/**
 * content - Schema_Courses Plugin
 *
 * @package		Joomla.Plugin
 */
class PlgContentSchema_Courses extends CMSPlugin {

/**
	 *  onJTEventSchema event to add JSON markup to the document
	 *
	 *  @return void
	 */
	public function onTjLmsCourseSchema($itemData)
	{
		$itemData->description = isset($itemData->short_desc) && !empty($itemData->short_desc) ? $itemData->short_desc: $itemData->description;

		$uri                   = Uri::getInstance();
		$url                   = $uri->toString();
		$baseUrl               = Uri::base();

		$providerName          = Factory::getConfig()->get('sitename');
		$content = [
            '@type' 			=> "Course",
            'name' 				=> $itemData->title,
            'description' 		=> $itemData->description,
			'startDate' 		=> $itemData->start_date,
			"provider" 			=> [
									"@type"  => "Organization",
									"name"   => $providerName,
									"sameAs" => $baseUrl
								   ],
			'mainEntityOfPage' 	=> [
									'@type' => 'WebPage',
									'@id'   => $url
            					   ],
        ];

		if(!empty($itemData->image))
		{
			$content = array_merge($content, ['image' => $itemData->image]);
		}

		// CourseModule
		if(!empty($itemData->toc))
		{
			$modules = [];
			foreach($itemData->toc as $toc)
			{
				$moduleJson = [
					"@type" => "CourseInstance",
					"name" => $toc->name
				];

				if(!empty($toc->description))
				{
					$moduleJson = array_merge($moduleJson, ["description" => $toc->description]);
				}

				if(!empty($toc->image))
				{
					$moduleJson = array_merge($moduleJson, ['image' => $itemData->image]);
				}

				$modules = array_merge($modules, [$moduleJson]);

			}

		}

		$content = array_merge($content, ["hasCourseInstance" => $modules]);

		if($content)
		{
			$content = ['@context' => 'https://schema.org'] + $content;

			$jsonString = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

			Factory::getDocument()->addScriptDeclaration($jsonString,'application/ld+json');

		}

	}
}