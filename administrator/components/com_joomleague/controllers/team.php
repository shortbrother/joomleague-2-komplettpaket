<?php
/**
* @copyright	Copyright (C) 2006-2013 JoomLeague.net. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.filesystem.file');

/**
 * Joomteam Component Controller
 *
 * @package	JoomLeague
 * @since	0.1
 */
class JoomleagueControllerTeam extends JoomleagueController
{
	protected $view_list = 'teams';
	
	function __construct()
	{
		parent::__construct();

		// Register Extra tasks
		$this->registerTask('add','display');
		$this->registerTask('edit','display');
		$this->registerTask('apply','save');
	}

	function display()
	{
	  switch($this->getTask())
		{
			case 'add'	:
			{
				JRequest::setVar('hidemainmenu',0);
				JRequest::setVar('layout','form');
				JRequest::setVar('view','team');
				JRequest::setVar('edit',false);

				// Checkout the project
				$model = $this->getModel('team');
				$model->checkout();
			} break;

			case 'edit'	:
			{
				JRequest::setVar('hidemainmenu',0);
				JRequest::setVar('layout','form');
				JRequest::setVar('view','team');
				JRequest::setVar('edit',true);

				// Checkout the project
				$model = $this->getModel('team');
				$model->checkout();
			} break;

			/*
			case 'copy'	:
			{
				$cid = JRequest::getVar('cid',array(0),'post','array');
				$copyID = (int)$cid[0];
				JRequest::setVar('hidemainmenu',1);
				JRequest::setVar('layout','form');
				JRequest::setVar('view','project');
				JRequest::setVar('edit',true);
				JRequest::setVar('copy',true);
			} break;
			*/
		}
		parent::display();
	}

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('COM_JOOMLEAGUE_GLOBAL_INVALID_TOKEN');
		$post = JRequest::get('post');
		$cid = JRequest::getVar('cid',array(0),'post','array');
		$post['id'] = (int) $cid[0];
        //$mainframe->enqueueMessage(JText::_('post -> '.'<pre>'.print_r($post,true).'</pre>' ),'');
		//decription must be fetched without striping away html code
		$post['notes'] = JRequest:: getVar('notes','none','post','STRING',JREQUEST_ALLOWHTML);
		$model = $this->getModel('team');

    if (isset($post['merge_clubs']))
		{
			if (count($post['merge_clubs']) > 0)
			{
				$temp=implode(",",$post['merge_clubs']);
			}
			else
			{
				$temp='';
			}
			$post['merge_clubs']=$temp;
		}
		else
		{
			$post['merge_clubs']='';
		}
    
		if ($model->store($post))
		{
			$msg = JText::_('COM_JOOMLEAGUE_ADMIN_TEAM_CTRL_SAVED');
		}
		else
		{
			$msg = JText::_('COM_JOOMLEAGUE_ADMIN_TEAM_CTRL_ERROR_SAVE').$model->getError();
		}

		// Check the table in so it can be edited.... we are done with it anyway
		$model->checkin();
		if ($this->getTask() == 'save')
		{
			$link = 'index.php?option=com_joomleague&view=teams&task=team.display';
		}
		else
		{
			$link = 'index.php?option=com_joomleague&task=team.edit&cid[]='.$post['id'];
		}
        
        //-------extra fields-----------//
		if(isset($post['extraf']) && count($post['extraf']))
        {
		
        for($p=0;$p<count($post['extraf']);$p++)
        {
            $tbl = JTable::getInstance("jlextuserextrafieldvalue", "Table");
        // extra feld ist schon vorhanden
        if ( $post['extra_value_id'][$p] )
        {
            $result = $post['extra_value_id'][$p];
            $tbl->load($result);
            $tbl->field_id = $post['extra_id'][$p];
            $tbl->jl_id = $post['id'];
            $tbl->fieldvalue = $post['extraf'][$p];
        }
        else
        {
            $tbl->field_id = $post['extra_id'][$p];
            $tbl->jl_id = $post['id'];
            $tbl->fieldvalue = $post['extraf'][$p];
        }
        if (!$tbl->store())
			{
			} 
        }
        
        }
        
		$this->setRedirect($link, $msg);
	}

	function copysave()
	{
		$model = $this->getModel('team');
		if ($model->copyTeams()) //copy team data
		{
			$msg = JText::_('COM_JOOMLEAGUE_ADMIN_TEAM_CTRL_COPY_TEAM');
		}
		else
		{
			$msg = JText::_('COM_JOOMLEAGUE_ADMIN_TEAM_CTRL_ERROR_COPY_TEAM').$model->getError();
		}
		//echo $msg;
		$this->setRedirect('index.php?option=com_joomleague&view=teams&task=team.display',$msg);
	}

	function remove()
	{
		$cid = JRequest::getVar('cid',array(),'post','array');
		JArrayHelper::toInteger($cid);

		if (count($cid) < 1)
		{
			JError::raiseError(500,JText::_('COM_JOOMLEAGUE_GLOBAL_SELECT_TO_DELETE'));
		}

		$model = $this->getModel('team');

		if(!$model->delete($cid))
		{
			echo "<script> alert('".$model->getError(true)."'); window.history.go(-1); </script>\n";
		}

		$this->setRedirect('index.php?option=com_joomleague&view=teams&task=team.display');
	}


	function cancel()
	{
		// Checkin the project
		$model = $this->getModel('team');
		$model->checkin();

		$this->setRedirect('index.php?option=com_joomleague&view=teams&task=team.display');
	}

	function import()
	{
		JRequest::setVar('view','import');
		JRequest::setVar('table','team');
		parent::display();
	}

	function export()
	{
		JRequest::checkToken() or die('COM_JOOMLEAGUE_GLOBAL_INVALID_TOKEN');
		$post=JRequest::get('post');
		$cid=JRequest::getVar('cid',array(),'post','array');
		JArrayHelper::toInteger($cid);
		if (count($cid) < 1){JError::raiseError(500,JText::_('COM_JOOMLEAGUE_GLOBAL_SELECT_TO_EXPORT'));}
		$model = $this->getModel("team");
		$model->export($cid, "team", "Team");
	}

	/**
	 * Proxy for getModel
	 *
	 * @param	string	$name	The model name. Optional.
	 * @param	string	$prefix	The class prefix. Optional.
	 *
	 * @return	object	The model.
	 * @since	1.6
	 */
	function getModel($name = 'Team', $prefix = 'JoomleagueModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}	
}
?>