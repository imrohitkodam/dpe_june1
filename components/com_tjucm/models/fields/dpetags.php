<?php

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Custom Field class for the Joomla Framework.
 *
 * @package		Joomla.Administrator
 * @subpackage	        com_my
 * @since		1.6
 */
class JFormFieldDpetags extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'DpeTags';

	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	public function getOptions()
	{
		// Initialize variables.
		$options = array();

		$db	= Factory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('Distinct tags.id As value, tags.title As text');
		$query->from('#__tags AS tags');
		$query->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagMap') . ' ON ' . $db->quoteName('tagMap.tag_id') . ' = ' . $db->quoteName('tags.id'));
		$query->order('tags.title');
		$query->where('type_alias="com_multiagency.multiagency"');

		$user = Factory::getUser();
		if (!$user->authorise('core.manageall', 'com_cluster') && !$user->authorise('core.admin'))
		{
			// Restrict by Trustee / Org Admin tags
			JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
			$dpeModel = DPE::model('school', array('ignore_request' => true));
			
			$params = ComponentHelper::getParams('com_multiagency');
			$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
			$isTrustee = in_array($multiagencyTrusteeRoleId, $user->groups);
			$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
			$isOrgAdmin = in_array($orgAdminRoleId, $user->groups);

			$roleId = 0;
			if ($isTrustee) {
				$roleId = $multiagencyTrusteeRoleId;
			} elseif ($isOrgAdmin) {
				$roleId = $orgAdminRoleId;
			}
			
			if ($roleId) {
				$tags = $dpeModel->getAgencyTags($roleId);
				if (!empty($tags)) {
					$tagIds = array();
					foreach ($tags as $tagObj) {
						if (isset($tagObj['value'])) {
							$tagIds[] = (int) $tagObj['value'];
						}
					}
					if (!empty($tagIds)) {
						$query->where('tags.id IN (' . implode(',', $tagIds) . ')');
					} else {
						$query->where('tags.id = 0');
					}
				} else {
					$query->where('tags.id = 0');
				}
			}
		}

		// Check for a database error.
			try 
			{
			    // Get the options.
				$db->setQuery($query);
				$options = $db->loadObjectList();
			}
			catch (Exception $e)
			{
			    echo $e->getMessage();
			}

		return $options;
	}
}