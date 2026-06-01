<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;


class TjlmsViewLessonsmodal extends HtmlView
{
    public $filterForm; 

    protected $items;
    protected $pagination;
    protected $state;

    public function display($tpl = null)
    {
        $app   = Factory::getApplication();
        $input = $app->input; 

        $this->state      = $this->get('State');
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');

                // Get cid and mid from request
                $this->cid = $input->getInt('cid');
                $this->mid = $input->getInt('mid');
        $this->filterForm = $this->get('FilterForm');

        
        if (count($errors = $this->get('Errors')))
        {
            throw new Exception(implode("\n", $errors), 500);
        }

        parent::display($tpl);
    }
}

