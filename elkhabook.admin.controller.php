<?php

/**
 * Elkha Book
 *
 * Copyright (c) 엘카
 *
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class ElkhabookAdminController extends Elkhabook
{
	public function insertModule(string $mid, object $vars) : baseObject
	{
		\Context::loadLang('modules/member/lang');
		$oModuleController = getController('module');

		$args = new \stdClass();
		$args->mid = $mid;
		$args->module = 'elkhabook';
		$args->skin = $vars->skin ?? 'default';
		$args->browser_title = $vars->browser_title ?: \Context::getLang('cmd_view_member_info');
		$args->layout_srl = $vars->layout_srl ?? -1;
		$args->mlayout_srl = $vars->mlayout_srl ?? -1;
		$args->use_mobile = 'Y';
		$args->mskin = '/USE_RESPONSIVE/';
		if(is_object($module_info = \ModuleModel::getModuleInfoByMid($mid)) && strlen($module_info->module ?? ''))
		{
			if($module_info->module == 'elkhabook')
			{
				$args->module_srl = $module_info->module_srl;
				$output = $oModuleController->updateModule($args);
			}
			else
			{
				return $this->createObject(-1, 'invalid value: mid');
			}
		}
		else
		{
			$output = $oModuleController->insertModule($args);
		}
		return $output;
	}
	public function procElkhabookAdminInsertConfig()
	{
		$vars = Context::getRequestVars();
		$config = new stdClass();
		$config->skin = $vars->skin;
		$config->doc_list = [];
		$config->elkhatalk_rooms = [];
		$config->user_id_open = $vars->user_id_open ?: 'N';
		$config->layout_srl = $vars->layout_srl ?? -1;
		$config->mlayout_srl = $vars->mlayout_srl ?? -1;
		$config->view = $vars->view ?? 'N';
		$config->list_count = $vars->list_count ?? 10;
		if(isset($vars->source_mid) && strlen($vars->source_mid))
		{
			$output = $this->insertModule($vars->source_mid, $vars);
			if(!$output->toBool())
			{
				return $output;
			}
			$config->source_mid = $vars->source_mid;
		}
		else
		{
			return $this->stop('invalid value: mid');
		}
		if(isset($vars->skin_colorset) && strlen($vars->skin_colorset))
		{
			$config->skin_colorset = $vars->skin_colorset;
		}
		if(isset($vars->follow_point) && strlen($vars->follow_point))
		{
			$config->follow_point = (INT)$vars->follow_point;
		}
		if(isset($vars->follow_add_limit) && strlen($vars->follow_add_limit))
		{
			$config->follow_add_limit = (INT)$vars->follow_add_limit;
		}
		if(isset($vars->follow_delete_limit) && strlen($vars->follow_delete_limit))
		{
			$config->follow_delete_limit = (INT)$vars->follow_delete_limit;
		}
		if(isset($vars->browser_title) && strlen($vars->browser_title))
		{
			$config->browser_title = $vars->browser_title;
		}

		foreach($vars->doc_list_regex as $key => $regex)
		{
			if(!strlen(trim($regex)))
			{
				continue;
			}
			if(!strlen(trim($vars->doc_list_label[$key])))
			{
				continue;
			}
			$config->doc_list[$regex] = [
				'label' => $vars->doc_list_label[$key],
				'more' => (INT)$vars->doc_list_more[$key]
			];
		}

		foreach($vars->elkhatalk_rooms_name as $key => $room)
		{
			if(!strlen(trim($room)))
			{
				continue;
			}
			if(!strlen(trim($vars->elkhatalk_rooms_label[$key])))
			{
				continue;
			}
			$config->elkhatalk_rooms[$room] = $vars->elkhatalk_rooms_label[$key];
		}

		// 변경된 설정을 저장
		$output = $this->setConfig($config);
		if (!$output->toBool())
		{
			return $output;
		}

		// 설정 화면으로 리다이렉트
		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));
	}
}
