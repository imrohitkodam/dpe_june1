<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

class RsticketsproTableKbcontentfeedbacks extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	public $id				= null;
	
	public $article_id 		= 0;
	public $user_id 		= 0;
	public $ip 				= '';
	public $date_submitted	= '';

	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__rsticketspro_kb_content_feedbacks', 'id', $db);
	}
}