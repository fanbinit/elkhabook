<?php

/**
 * Elkha Book
 *
 * Copyright (c) 엘카
 *
 * Generated with https://www.poesis.org/tools/modulegen/
 */
class Elkhabook extends ModuleObject
{
	static $voted_count = FALSE;
	static $config;

	protected $_acts = [
		'dispMemberInfo' => ['act' => 'dispElkhabookIndex', 'mid' => 'source_mid']
	];

	public function getAct(string $act) : string
	{
		return isset($this->_acts[$act]) ? $this->_acts[$act]['act'] : $act;
	}
	public function getUrl(string $act, int $member_srl, $func = 'getNotEncodedUrl') : string
	{
		if(!$member_srl && is_object($logged_info = Context::get('logged_info')) && $logged_info->member_srl)
		{
			$member_srl = $logged_info->member_srl;
		}
		static $urls = [];
		if(!isset($urls[$act]))
		{
			$urls[$act] = [];
		}
		if(!isset($urls[$act][$member_srl]))
		{
			if($member_srl && is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl)) && ($member_info->member_srl ?? 0) == $member_srl)
			{
				$config = $this->getConfig();
				if(isset($this->_acts[$act]) && file_exists(__DIR__ . "/skins/$config->skin/$act.html"))
				{
					if(preg_match('/^(true|y|yes)$/i', \ModuleModel::getModuleActionXml('elkhabook')->action->dispElkhabookIndex->global_route))
					{
						if($config->user_id_open == 'Y')
						{
							$urls[$act][$member_srl] = \RX_BASEURL . '@' . urlencode($member_info->user_id);
						}
						else if($config->user_id_open == 'nick_name')
						{
							$urls[$act][$member_srl] = \RX_BASEURL . '@' . urlencode($member_info->nick_name);
						}
						else
						{
							$urls[$act][$member_srl] = ['', 'mid', $config->{$this->_acts[$act]['mid']}, 'member_srl', $member_srl];
						}
					}
					else
					{
						if($config->user_id_open == 'Y')
						{
							$urls[$act][$member_srl] = ['', 'target_id', $member_info->user_id, 'mid', $config->{$this->_acts[$act]['mid']}];
						}
						else if($config->user_id_open == 'nick_name')
						{
							$urls[$act][$member_srl] = ['', 'target_id', $member_info->nick_name, 'mid', $config->{$this->_acts[$act]['mid']}];
						}
						else
						{
							$urls[$act][$member_srl] = ['', 'mid', $config->{$this->_acts[$act]['mid']}, 'member_srl', $member_srl];
						}
					}
				}
			}
			else
			{
				$urls[$act][$member_srl] = [];
			}
		}
		if(is_array($urls[$act][$member_srl]))
		{
			// 구버전 코드 호환.
			if(is_bool($func))
			{
				$func = $func ? 'getUrl' : 'getNotEncodedUrl';
			}
			return count($urls[$act][$member_srl])? call_user_func_array($func, $urls[$act][$member_srl]) : '';
		}
		else
		{
			return $urls[$act][$member_srl];
		}
	}

	/**
	 * 모듈 설정 캐시를 위한 변수.
	 */
	protected static $_config_cache = null;

	/**
	 * 캐시 핸들러 캐시를 위한 변수.
	 */
	protected static $_cache_handler_cache = null;

	public function getConfig()
	{
		if (self::$_config_cache === null)
		{
			$oModuleModel = getModel('module');
			self::$_config_cache = $oModuleModel->getModuleConfig($this->module) ?: new stdClass;
		}
		self::$_config_cache->source_mid = self::$_config_cache->source_mid ?? 'mylog';
		self::$_config_cache->skin = self::$_config_cache->skin ?? 'default';
		self::$_config_cache->list_count = self::$_config_cache->list_count ?? 10;
		self::$_config_cache->elkhatalk_list_count = self::$_config_cache->elkhatalk_list_count ?? 15;
		self::$_config_cache->skin_colorset = self::$_config_cache->skin_colorset ?? '#2c3e50';
		self::$_config_cache->doc_list = self::$_config_cache->doc_list ?? [];
		self::$_config_cache->follow_point = self::$_config_cache->follow_point ?? 15;
		self::$_config_cache->elkhatalk_rooms = self::$_config_cache->elkhatalk_rooms ?? [];
		self::$_config_cache->follow_add_limit = self::$_config_cache->follow_add_limit ?? 1;
		self::$_config_cache->follow_delete_limit = self::$_config_cache->follow_delete_limit ?? 24;
		self::$_config_cache->user_id_open = self::$_config_cache->user_id_open ?? 'N';
		self::$_config_cache->browser_title = self::$_config_cache->browser_title ?? \Context::getLang('cmd_view_member_info');
		self::$_config_cache->layout_srl = self::$_config_cache->layout_srl ?? -1;
		self::$_config_cache->mlayout_srl = self::$_config_cache->mlayout_srl ?? -1;
		self::$_config_cache->view = self::$_config_cache->view ?? 'N';
		return self::$_config_cache;
	}

	/**
	 * 모듈 설정을 저장하는 함수.
	 *
	 * 설정을 변경할 필요가 있을 때 ModuleController를 직접 호출하지 말고 이 함수를 사용한다.
	 * getConfig()으로 가져온 설정을 적절히 변경하여 setConfig()으로 다시 저장하는 것이 정석.
	 *
	 * @param object $config
	 * @return object
	 */
	public function setConfig($config)
	{
		$oModuleController = getController('module');
		$result = $oModuleController->insertModuleConfig($this->module, $config);
		if ($result->toBool())
		{
			self::$_config_cache = $config;
		}
		return $result;
	}

	/**
	 * 오브젝트 캐시에서 값을 가져오는 함수.
	 *
	 * 그룹 키를 지정하지 않으면 자동으로 현재 모듈 이름이 그룹 키로 사용되므로
	 * 필요시 그룹 키를 비움으로써 신속하게 캐시를 갱신할 수 있다.
	 *
	 * @param string $key
	 * @param int $ttl
	 * @param string $group_key (optional)
	 * @return mixed
	 */
	public function getCache($key, $ttl = 86400, $group_key = null)
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			$group_key = $group_key ?: $this->module;
			return self::$_cache_handler_cache->get(self::$_cache_handler_cache->getGroupKey($group_key, $key), $ttl);
		}
		else
		{
			return false;
		}
	}

	/**
	 * 오브젝트 캐시에 값을 저장하는 함수.
	 *
	 * 그룹 키를 지정하지 않으면 자동으로 현재 모듈 이름이 그룹 키로 사용되므로
	 * 필요시 그룹 키를 비움으로써 신속하게 캐시를 갱신할 수 있다.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @param string $group_key (optional)
	 * @return bool
	 */
	public function setCache($key, $value, $ttl = 86400, $group_key = null)
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			$group_key = $group_key ?: $this->module;
			return self::$_cache_handler_cache->put(self::$_cache_handler_cache->getGroupKey($group_key, $key), $value, $ttl);
		}
		else
		{
			return false;
		}
	}

	/**
	 * 오브젝트 캐시에서 개별 키를 삭제하는 함수.
	 *
	 * @param string $key
	 * @param string $group_key (optional)
	 * @return bool
	 */
	public function deleteCache($key, $group_key = null)
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			$group_key = $group_key ?: $this->module;
			self::$_cache_handler_cache->delete(self::$_cache_handler_cache->getGroupKey($group_key, $key));
		}
		else
		{
			return false;
		}
	}

	/**
	 * 오브젝트 캐시를 비우는 함수.
	 *
	 * 지정된 그룹 키에 소속된 데이터만 삭제한다.
	 * 현재 모듈에서 저장한 데이터만 삭제하는 것이 기본값이다.
	 *
	 * @param string $group_key (optional)
	 * @return bool
	 */
	public function clearCache($group_key = null)
	{
		if (self::$_cache_handler_cache === null)
		{
			self::$_cache_handler_cache = CacheHandler::getInstance('object');
		}

		if (self::$_cache_handler_cache->isSupport())
		{
			$group_key = $group_key ?: $this->module;
			return self::$_cache_handler_cache->invalidateGroupKey($group_key) ? true : false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * XE Object를 생성하여 반환한다.
	 *
	 * XE 1.8 이하, XE 1.9 이상, PHP 7.1 이하, PHP 7.2 이상 모두 호환된다.
	 * 기본적인 사용법은 return new Object(-1, 'error'); 라고 쓸 자리에
	 * return $this->createObject(-1, 'error'); 라고 쓰면 된다.
	 *
	 * 반환할 언어 내용 중 %s, %d 등 변수를 치환하는 부분이 있다면
	 * 치환할 내용을 추가 파라미터로 넘겨주면 sprintf()의 역할까지 해준다.
	 *
	 * @param string $message
	 * @param $arg1, $arg2 ...
	 * @return object
	 */
	public function createObject($status = 0, $message = 'success' /* $arg1, $arg2 ... */)
	{
		$args = func_get_args();
		if (count($args) > 2)
		{
			global $lang;
			$message = vsprintf($lang->$message, array_slice($args, 2));
		}
		return class_exists('BaseObject') ? new BaseObject($status, $message) : new Object($status, $message);
	}

	/**
	 * 모듈 설치 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return object
	 */
	public function moduleInstall()
	{
		$config = $this->getConfig();
		$oAdminController = getAdminController('elkhabook');
		$output = $oAdminController->insertModule($config->source_mid, new \stdClass() );
		return $this->createObject(0, 'success_updated');
	}

	/**
	 * 모듈 업데이트 확인 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return bool
	 */
	public function checkUpdate()
	{
		return false;
	}

	/**
	 * 모듈 업데이트 콜백 함수.
	 *
	 * 트리거 등록 외에 따로 할 일이 없다면 수정할 필요 없다.
	 *
	 * @return object
	 */
	public function moduleUpdate()
	{
		return $this->moduleInstall();
	}

	/**
	 * 캐시파일 재생성 콜백 함수.
	 *
	 * @return void
	 */
	public function recompileCache()
	{
		$this->clearCache();
	}
}
