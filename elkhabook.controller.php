<?php

/**
 * Elkha Book
 *
 * Copyright (c) 엘카
 *
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class ElkhabookController extends Elkhabook
{
	public function beforeAddFriend($obj)
	{
		$config = $this->getConfig();
		if(($config->follow_add_limit ?: 0) && \Context::get('logged_info')->is_admin != 'Y')
		{
			$args = new \stdClass();
			$args->member_srl = $obj->member_srl;
			$args->list_count = 1;
			$data = executeQuery('elkhabook.getFriends', $args)->data;

			if(is_object($data) && isset($data->regdate) && ( $limit = time() - (INT)strtotime($data->regdate) - floor($config->follow_add_limit * 3600) ) < 0)
			{
				\Context::loadLang('modules/elkhabook/lang');
				if(abs($limit) < 3600)
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_add_minutes'), floor(abs($limit) / 60));
				}
				else
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_add_hours'), floor(abs($limit) / 3600));
				}
				throw new Rhymix\Framework\Exception($message);
			}
		}
		return $this;
	}
	public function afterAddFriend($args)
	{
		$config = $this->getConfig();
		if($point = $config->follow_point ?: 0)
		{
			$oPointController = getController('point');
			$output = $oPointController->setPoint($args->target_srl, $point, 'add');
		}
		$oModel = getModel('elkhabook');
		$oModel->getElkhabookFriendButton($args->target_srl);
		$oCommunicationController = getController('communication');
		$oCommunicationController->add('elkhabook_tpl_button', $oModel->get('tpl_button'));
		return $this;
	}
	public function beforeDeleteFriend($args)
	{
		$config = $this->getConfig();
		$data = executeQueryArray('elkhabook.getFriends', $args)->data;
		$friend_srl_list = [];
		$target_srls = [];
		foreach($data as $val)
		{
			if(\Context::get('logged_info')->is_admin == 'Y')
			{
				$friend_srl_list[] = $val->friend_srl;
				$target_srls[] = $val->target_srl;
			}
			else if(( $limit = time() - (INT)strtotime($val->regdate) - floor($config->follow_delete_limit * 3600) ) < 0)
			{
				\Context::loadLang('modules/elkhabook/lang');
				if(abs($limit) < 3600)
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_delete_minutes'), floor(abs($limit) / 60));
				}
				else
				{
					$message = sprintf(\Context::getLang('elkhabook_can_not_delete_hours'), floor(abs($limit) / 3600));
				}
				throw new Rhymix\Framework\Exception($message);
			}
			else if(!is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($val->target_srl)) || !$member_info->member_srl)
			{
				$friend_srl_list[] = $val->friend_srl;
			}
			else
			{
				$friend_srl_list[] = $val->friend_srl;
				$target_srls[] = $val->target_srl;
			}
		}
		if(!count($friend_srl_list))
		{
			\Context::loadLang('modules/elkhabook/lang');
			throw new Rhymix\Framework\Exception('elkhabook_can_not_delete');
		}
		$args->friend_srl_list = $friend_srl_list;
		$args->target_srls = $target_srls;
		return $this;
	}
	public function afterDeleteFriend($args)
	{
		$config = $this->getConfig();
		if(isset($args->target_srls) && is_array($args->target_srls) && ($point = $config->follow_point ?: 0))
		{
			$oPointController = getController('point');
			foreach($args->target_srls as $target_srl)
			{
				$output = $oPointController->setPoint($target_srl, $point, 'minus');
			}
		}
		if(is_array($args->target_srls) && count($args->target_srls) == 1)
		{
			$target_srl = reset($args->target_srls);
			$oModel = getModel('elkhabook');
			$oModel->getElkhabookFriendButton($target_srl);
			$oCommunicationController = getController('communication');
			$oCommunicationController->add('elkhabook_tpl_button', $oModel->get('tpl_button'));
		}
		return $this;
	}
}
