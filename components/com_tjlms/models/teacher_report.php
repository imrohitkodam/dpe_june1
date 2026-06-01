<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.application.component.model');

/**
 * Tjlms model.
 * 
 * @since  1.6
 */
class TjlmsModelTeacher_Report extends BaseDatabaseModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   2.2
	 */
	public function __construct($config = array())
	{
		parent::__construct();
	}

	/**
	 * Get top 10 scorers for the teacher
	 * 
	 * @param   INT  $course_id  course_id ID
	 *
	 * @return  Array.
	 */
	public function getTopScorer($course_id )
	{
		$topscorer = array();
		$topscorer[0] = new stdClass;
		$topscorer[0]->path = '';
		$topscorer[0]->uname = 'Lina';
		$topscorer[0]->score = 44;
		$topscorer[0]->status = 0;
		$topscorer[0]->report_path = 'path';

		$topscorer[1] = new stdClass;
		$topscorer[1]->uname = 'Tina';
		$topscorer[1]->score = 56;
		$topscorer[1]->status = 1;
		$topscorer[1]->report_path = 'path';

		$topscorer[2] = new stdClass;
		$topscorer[2]->uname = 'Mina';
		$topscorer[2]->score = 23;
		$topscorer[2]->status = 1;
		$topscorer[2]->report_path = 'path';

		$topscorer[3] = new stdClass;
		$topscorer[3]->uname = 'Jack';
		$topscorer[3]->score = 67;
		$topscorer[3]->status = 1;
		$topscorer[3]->report_path = 'path';

		$topscorer[4] = new stdClass;
		$topscorer[4]->uname = 'Kim';
		$topscorer[4]->score = 45;
		$topscorer[4]->status = 0;
		$topscorer[4]->report_path = 'path';

		return $topscorer;
	}

	/**
	 * Get popular student for dashboard based on active courses
	 * 
	 * @param   INT  $course_id  course_id ID
	 *
	 * @return  Array.
	 */
	public function getStudentwhoLiked($course_id )
	{
		$popularStudent = array();
		$popularStudent[0] = new stdClass;
		$popularStudent[0]->path = '';
		$popularStudent[0]->name = 'Aniket';
		$popularStudent[0]->enrolledIn = 4;

		$popularStudent[1] = new stdClass;
		$popularStudent[1]->path = '';
		$popularStudent[1]->name = 'Vaishali';
		$popularStudent[1]->enrolledIn = 2;

		$popularStudent[2] = new stdClass;
		$popularStudent[2]->path = '';
		$popularStudent[2]->name = 'Durr';
		$popularStudent[2]->enrolledIn = 3;

		$popularStudent[3] = new stdClass;
		$popularStudent[3]->path = '';
		$popularStudent[3]->name = 'Sneha';
		$popularStudent[3]->enrolledIn = 1;

		return $popularStudent;
	}
}
