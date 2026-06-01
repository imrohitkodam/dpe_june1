<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproAdapterPlain
{
	protected $id		= null;
	protected $titles 	= array();
	protected $contents = array();
	
	public function __construct($id)
	{
		$this->id = preg_replace('#[^A-Z0-9_\. -]#i', '', $id);
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
		foreach ($this->titles as $i => $title)
		{
			?>
			<div class="card" id="ticket-<?php echo $this->escape($title->id); ?>">
				<div class="card-header">
					<h3><?php echo $this->escape($title->label); ?></h3>
				</div>
				<div class="card-body">
					<?php echo $this->contents[$i]; ?>
				</div>
			</div>
			<?php
		}
	}
	
	protected function escape($string)
	{
		return htmlentities($string, ENT_COMPAT, 'utf-8');	
	}

	public function remove($index)
	{
		if (isset($this->titles[$index]))
		{
			unset($this->titles[$index]);
		}

		if (isset($this->contents[$index]))
		{
			unset($this->contents[$index]);
		}
	}
}