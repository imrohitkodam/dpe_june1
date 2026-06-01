<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproAdapterNavpills
{
	protected $id;
	protected $titles   = array();
	protected $contents = array();
	
	public function __construct($id)
	{
		$this->id = preg_replace('/[^A-Z0-9_\. -]/i', '', $id);
	}
	
	public function addTitle($label, $id)
	{
		$this->titles[] = (object) array('label' => $label, 'id' => $id);
	}
	
	public function addContent($content)
	{
		$this->contents[] = $content;
	}
	
	public function render()
	{
		$nav_html		= '<ul class="nav nav-pills" id="' . $this->id . '-nav">';
		$content_html	= '<div class="tab-content" id="' . $this->id . '-content">';

		$active = reset($this->titles);

		foreach ($this->titles as $i => $title)
		{
			$nav_html .= '<li' . ($title->id == $active->id ? ' class="active"' : '') . '>';
			$nav_html .= '<a id="' . $title->id . '-tab" href="#' . $title->id . '" data-toggle="tab">' . JText::_($title->label) . '</a>';
			$nav_html .= '</li>';
			
			$content_html .= '<div class="tab-pane' . ($title->id == $active->id ? ' active' : '') . '" id="' . $title->id . '">';
			$content_html .= $this->contents[$i];
			$content_html .= '</div>';
		}
		
		$nav_html		.= '</ul>';
		$content_html	.= '</div>';

		echo $nav_html . $content_html;
	}
}