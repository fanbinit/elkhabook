<?php

namespace elkhabook;
require_once(_XE_PATH_.'modules/comment/comment.item.php');
class elkhatalk extends \commentItem
{
	var $elkhatalk = TRUE;
	/*public function getPermanentUrl() : string
	{
	}*/
	public function isGranted() : bool
	{
		$logged_info = \Context::get('logged_info');
		if(!is_object($logged_info) || !$logged_info->member_srl)
		{
			return FALSE;
		}
		return $this->get('member_srl')==$logged_info->member_srl || $logged_info->is_admin=='Y';
	}
	public function setAttribute($attribute)
	{
		$this->adds($attribute);
	}
	public function roomName() : string
	{
		$oModel = getModel('elkhabook');
		$config = $oModel->getConfig();

		if(isset($config->elkhatalk_rooms[ 'public' . $this->get('room') ]))
		{
			return $config->elkhatalk_rooms[ 'public' . $this->get('room') ];
		}
		return '?';
	}
	public function getContent2()
	{
		$logged_info = \Context::get('logged_info');
		if($this->get('option')!='D' || (is_object($logged_info) && $logged_info->is_admin=='Y'))
		{
			return htmlspecialchars($this->get('content'));
		}
		return \Context::getLang('elkhabook_delete_chat');
	}
	public function getProfileImage()
	{
		$member_srl = (INT)$this->get('member_srl');
		if($member_srl)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl);
			if(is_object($member_info) && $member_info->member_srl)
			{
				return $member_info->profile_image->src;
			}
		}
		return FALSE;
	}
	public function getNickName()
	{
		$member_srl = (INT)$this->get('member_srl');
		if($member_srl)
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl);
			if(is_object($member_info) && $member_info->member_srl)
			{
				return strip_tags($member_info->nick_name);
			}
		}
		return '[?]';
	}
	function getRegdate($format = 'Y.m.d H:i:s', $conversion = true)
	{
		return date($format, strtotime($this->get('regdate')));
	}
}
