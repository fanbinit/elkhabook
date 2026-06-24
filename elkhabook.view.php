<?php

class ElkhabookView extends Elkhabook
{
	public function init()
	{
		foreach($this->_acts as $act => $val)
		{
			if($this->act == $val['act'])
			{
				$config = $this->getConfig();
				if(($config->view ?? '') == 'Y')
				{
					if(!is_object($logged_info = Context::get('logged_info')) || !$logged_info->member_srl)
					{
						return $this->stop('msg_not_logged');
					}
				}
				$this->setTemplatePath($this->module_path . 'skins/' . $config->skin);
				$this->setTemplateFile($act);
				$this->module_info->layout_srl = $config->layout_srl;
				$this->module_info->mlayout_srl = $config->mlayout_srl;
				return $this;
			}
		}
		return $this->stop('msg_invalid_request');
	}

	public function triggerBeforeDispMemberModule($oModule)
	{
		if(Context::getRequestMethod() != 'GET')
		{
			return $oModule;
		}
		$url = $this->getUrl(Context::get('act'), (INT)Context::get('member_srl'));
		if($url != '')
		{
			header("Location: $url");
			exit;
		}
	}
	public function dispElkhabookIndex()
	{
		$config = $this->getConfig();
		$target_srl = (INT)\Context::get('member_srl') ?: (INT)\Context::get('document_srl') ?: (INT)\Context::get('target_srl') ?: 0;
		if(!$target_srl && strlen($user_id = \Context::get('target_id') ?: ''))
		{
			if($config->user_id_open == 'Y')
			{
				if(is_object($member_info = \MemberModel::getMemberInfoByUserID($user_id)) && $member_info->user_id == $user_id)
				{
					$target_srl = $member_info->member_srl;
				}
			}
			else if($config->user_id_open == 'nick_name')
			{
				$target_srl = (INT)\MemberModel::getMemberSrlByNickName($user_id);
			}
		}
		if(!$target_srl)
		{
			if(is_object($logged_info = \Context::get('logged_info')) && $logged_info->member_srl)
			{
				if($config->user_id_open == 'Y')
				{
					header('Location: ' . getNotEncodedUrl('mid', $config->source_mid, 'target_id', $logged_info->user_id));
				}
				else if($config->user_id_open == 'nick_name')
				{
					header('Location: ' . getNotEncodedUrl('mid', $config->source_mid, 'target_id', $logged_info->nick_name));
				}
				else
				{
					header('Location: ' . getNotEncodedUrl('mid', $config->source_mid, 'member_srl', $logged_info->member_srl, 'target_id', ''));
				}
				exit;
			}
			return $this->stop('msg_not_logged');
		}

		foreach(\Context::getInstance()->opengraph_metadata as $key => $val)
		{
			if($val[0] == 'og:url') unset(\Context::getInstance()->opengraph_metadata[$key]);
		}

		// $oModel->setMemberInfo 에서 옮겨옴.
		if(is_object($logged_info = \Context::get('logged_info')) && ($logged_info->member_srl == $target_srl))
		{
			$member_info = $logged_info;
		}
		else
		{
			$member_info = \MemberModel::getMemberInfoByMemberSrl($target_srl);
		}
		\Context::loadLang('modules/member/lang');
		if(is_object($member_info) && $member_info->member_srl)
		{
			$args = new \stdClass();
			$args->member_srl = $member_info->member_srl;
			$args->service = 'chzzk';
			$chzzkInfo = executeQuery('sociallogin.getMemberSns', $args)->data;
			$member_info->chzzk = $chzzkInfo;
			
			\Context::set('_member_info', $member_info);
			\Context::setBrowserTitle( $config->browser_title . " - $member_info->nick_name");
		}
		else
		{
			$member_info = new stdClass();
			$member_info->member_srl = 0;
			$member_info->nick_name = '?';
			\Context::setBrowserTitle( \Context::getLang('msg_not_exists_member') );
			\Context::set('_member_info', $member_info);
		}
	}
}
