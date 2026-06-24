<?php
class ElkhabookModel extends Elkhabook
{
	public function getElkhabookFriendButton(int $member_srl = 0)
	{
		$member_srl = $member_srl ?: (INT)Context::get('target_srl');
		$me_member_srl = is_object($logged_info = \Context::get('logged_info')) && $logged_info->member_srl ? $logged_info->member_srl : 0;
		$count_info = $this->getElkhabookCountInfo($member_srl);

		if($count_info['내팔로잉'] && $count_info['내팔로워'])
			$status = \Context::getLang('elkhabook_is_freind');
		else if($count_info['내팔로잉'])
			$status = \Context::getLang('elkhabook_followed'); // 내가 상대를 친추했음: 팔로우하고 있습니다.
		else if($count_info['내팔로워'])
			$status = \Context::getLang('elkhabook_freind'); // 상대가 나를 친추했음: 친구가 됩니다.
		else
			$status = \Context::getLang('elkhabook_follow'); // 팔로우 합니다.

		$tpl = sprintf(' <a href="javascript:;" onclick="jQuery.exec_json(%s,%s, function(p){});" class="tooltip tooltip-follow%s"> <span class="label">%s</span> <span class="num follow_count">%d</span> </a> ',
			"'communication.procCommunicationAddFriend'",
			"{target_srl:$member_srl}",
			$me_member_srl == $member_srl || $count_info['내팔로잉'] ? ' on' : '',
			$status,
			$count_info['팔로워'] + $count_info['친구']
		);
		$this->add('tpl_button', $tpl);
	}
	public function getElkhabookConfig()
	{
		$config = $this->getConfig();
		$target_srl = (INT)\Context::get('target_srl');
		$logged_info = \Context::get('logged_info');

		$args = new \stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->target_srl = $target_srl;
		$args->list_count = 1;
		$data = executeQuery('elkhabook.getFriends', $args)->data;

		$elkhabook_config = new \stdClass();
		$elkhabook_config->friend_srl = is_object($data) && isset($data->friend_srl) && $data->friend_srl ? $data->friend_srl : 0;
		$elkhabook_config->confirm_follow = sprintf(\Context::getLang('elkhabook_confirm_follow'), $config->follow_point);
		$elkhabook_config->confirm_unfollow = sprintf(\Context::getLang('elkhabook_confirm_unfollow'), $config->follow_point);
		$this->add('config', $elkhabook_config);
	}
	public function getElkhabookCountInfo(INT $member_srl) : array
	{
		if($member_srl <= 0)
		{
			return ['팔로워' => 0, '팔로잉'=> 0, '친구' => 0, '내팔로워' => 0, '내팔로잉' => 0];
		}
		static $list = [];
		if(!isset($list[$member_srl]))
		{
			$member_srl2 = is_object($logged_info = \Context::get('logged_info')) && $logged_info->member_srl ? $logged_info->member_srl : 0;
			$oDB = DB::getInstance();
			$result = $oDB->query("
			SELECT
				COUNT(CASE WHEN f.target_srl = m.member_srl THEN 1 END) - FLOOR(COUNT(CASE WHEN f2.member_srl IS NOT NULL THEN 1 END) / 2) AS '팔로워',
				COUNT(CASE WHEN f.member_srl = m.member_srl THEN 1 END) - FLOOR(COUNT(CASE WHEN f2.member_srl IS NOT NULL THEN 1 END) / 2) AS '팔로잉',
				FLOOR(COUNT(CASE WHEN f2.member_srl IS NOT NULL THEN 1 END) / 2) AS '친구',
				IF(? > 0, COUNT(CASE WHEN (f.member_srl = m.member_srl AND f.target_srl = m.member_srl2) THEN 1 END), 0) AS '내팔로워',
				IF(? > 0, COUNT(CASE WHEN (f.target_srl = m.member_srl AND f.member_srl = m.member_srl2) THEN 1 END), 0) AS '내팔로잉'
			FROM (SELECT ? `member_srl`, ? `member_srl2`) AS `m`
			LEFT JOIN `member_friend` AS `f`
				ON f.member_srl = m.member_srl
				OR f.target_srl = m.member_srl
			LEFT JOIN `member_friend` AS `f2`
				ON f2.member_srl = f.target_srl
				AND f2.target_srl = f.member_srl;", $member_srl2, $member_srl2, $member_srl, $member_srl2);
			$list[$member_srl] = (array)$oDB->fetch($result);
		}
		return $list[$member_srl];
	}
	public function getElkhabookFriendList(INT $member_srl = 0)
	{
		$friend_list = ['팔로워' => [], '팔로잉'=> [], '친구' => []];
		$type = isset($friend_list[\Context::get('friend_type')]) ? (STRING)\Context::get('friend_type') : '';
		$member_srl = $member_srl ?: (INT)Context::get('target_member_srl') ?: 0;
		$page = abs((INT)Context::get('friend_page')) ?: 1;
		$friend_more = ['팔로워' => FALSE, '팔로잉'=> FALSE, '친구' => FALSE];
		$list_count = 20;
		$queries = [];
		$params = [];
		$member_not_exists = [];
		if($member_srl && is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl)) && $member_info->member_srl == $member_srl)
		{
			if($type === '')
			{
				$count_info = $this->getElkhabookCountInfo($member_srl);

				if($count_info['팔로잉'])
				{
					$queries[] = "(SELECT f.target_srl AS `member_srl`, m.nick_name AS `nick_name`, '팔로잉' AS `type`
					FROM `member_friend` AS `f`
					LEFT JOIN `member_friend` AS `f2`
						ON f2.target_srl = f.member_srl
						AND f2.member_srl = f.target_srl
					LEFT JOIN `member` AS `m`
						ON m.member_srl = f.target_srl
					WHERE f.member_srl = ?
						AND f2.target_srl IS NULL
					ORDER BY f.friend_srl DESC
					LIMIT ?, ?)";
					$params = array_merge($params, [$member_srl, ($page - 1) * $list_count, $list_count]);
					$friend_more['팔로잉'] = $count_info['팔로잉'] > $page * $list_count;
				}
				if($count_info['팔로워'])
				{
					$queries[] = "(SELECT f.member_srl AS `member_srl`, m.nick_name AS `nick_name`, '팔로워' AS `type`
					FROM `member_friend` AS `f`
					LEFT JOIN `member_friend` AS `f2`
						ON f2.target_srl = f.member_srl
						AND f2.member_srl = f.target_srl
					LEFT JOIN `member` AS `m`
						ON m.member_srl = f.member_srl
					WHERE f.target_srl = ?
						AND f2.target_srl IS NULL
					ORDER BY f.friend_srl DESC
					LIMIT ?, ?)";
					$params = array_merge($params, [$member_srl, ($page - 1) * $list_count, $list_count]);
					$friend_more['팔로워'] = $count_info['팔로워'] > $page * $list_count;
				}
				if($count_info['친구'])
				{
					$queries[] = "(SELECT f.member_srl AS `member_srl`, m.nick_name AS `nick_name`, '친구' AS `type`
					FROM `member_friend` AS `f`
					INNER JOIN `member_friend` AS `f2`
						ON f2.target_srl = f.member_srl
						AND f2.member_srl = f.target_srl
					LEFT JOIN `member` AS `m`
						ON m.member_srl = f.member_srl
					WHERE f.target_srl = ?
					ORDER BY f.friend_srl DESC
					LIMIT ?, ?)";
					$params = array_merge($params, [$member_srl, ($page - 1) * $list_count, $list_count]);
					$friend_more['친구'] = $count_info['친구'] > $page * $list_count;
				}

				if(count($queries))
				{
					$oDB = DB::getInstance();
					$result = $oDB->query(implode(' UNION ALL ', $queries), $params);
					$data = $oDB->fetch($result);
					if(is_object($data) && isset($data->member_srl))
					{
						$data = [$data];
					}

					foreach($data as $val)
					{
						if(!is_string($val->nick_name) || $val->nick_name == '')
						{
							$member_not_exists[] = $val->member_srl;
							$val->nick_name = '?';
						}
						$friend_list[$val->type][] = $val;
					}
				}
			}
			else
			{
				$args = new \stdClass();
				$args->list_count = $list_count;
				$args->page = $page;
				if($type == '팔로잉')
				{
					$args->member_srl = $member_srl;
					$output = executeQueryArray('elkhabook.getFriendListFollowing', $args, ['f.target_srl member_srl', 'm.nick_name']);
				}
				else if($type == '팔로워')
				{
					$args->target_srl = $member_srl;
					$output = executeQueryArray('elkhabook.getFriendListFollower', $args, ['f.member_srl', 'm.nick_name']);
				}
				else if($type == '친구')
				{
					$args->member_srl = $member_srl;
					$output = executeQueryArray('elkhabook.getFriendListFriend', $args, ['f.member_srl', 'm.nick_name']);
				}
				foreach($output->data as $val)
				{
					$val->type = $type;
					if(!is_string($val->nick_name) || $val->nick_name == '')
					{
						$member_not_exists[] = $val->member_srl;
						$val->nick_name = '?';
					}
				}
				$friend_list = [$type => $output->data];
				$friend_more[$type] = $output->page_navigation->cur_page < $output->page_navigation->total_page;
			}
		}

		$this->add('friend_more', $friend_more);
		$this->add('friend_list', $friend_list);
		$this->add('friend_page', $page);
		$config = $this->getConfig();
		$oTemplateHandler = \TemplateHandler::getInstance();
		$tpl = $oTemplateHandler->compile($this->module_path . "skins/$config->skin", __FUNCTION__);
		$this->add(__FUNCTION__, $tpl);
	}

	public function voted_count($member_srl = 0) : int
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			if(is_object($logged_info) && $logged_info->member_srl)
			{
				$member_srl = $logged_info->member_srl;
			}
		}
		if(!$member_srl)
		{
			return 0;
		}
		// voted_count 추가
		if(static::$voted_count===FALSE)
		{
			$args = new stdClass();
			$args->member_srl = $member_srl;
			$voted_count = executeQuery('elkhabook.getVotedCount', $args)->data->voted_count;

			static::$voted_count = $voted_count > 0 ? $voted_count : 0;
		}
		return static::$voted_count;
	}

	public function level($member_srl = 0) : array
	{
		if(!$member_srl)
		{
			return [0,0];
		}
		$oPointModel = getModel('point');
		$point = $oPointModel->getPoint($member_srl);
		$oModuleModel = getModel('module');
		$point_config = $oModuleModel->getModuleConfig('point');
		$level = $oPointModel->getLevel($point, $point_config->level_step);
		return [(INT)$point, (INT)$level, $point_config->point_name];
	}

	public function setMemberInfo() : bool
	{
		// 사용 안 함. dispElkhabookIndex 에서 사용.
		$_member_info = \Context::get('_member_info');
		return is_object($_member_info) && $_member_info->member_srl > 0;
	}
	public function getCategoryCmt(INT $member_srl) : array
	{
		static $doc_list = [];
		if(!isset($doc_list[$member_srl]))
		{
			$doc_list[$member_srl] = [
				'__default' => [
					'docs' => []
				],
				'__module_srls' => []
			];

			$config = $this->getConfig();
			if(!count($config->doc_list))
			{
				return $doc_list[$member_srl];
			}
			$args = new \stdClass();
			$args->member_srl = $member_srl;
			$data = executeQueryArray('elkhabook.getCategoryCmt', $args)->data;
			foreach($data as $val)
			{
				$continue = false;
				if(is_string($val->mid) && strlen($val->mid))
				{
					foreach($config->doc_list as $regex => $v)
					{
						if(strlen($regex))
						{
							if(!isset($doc_list[$member_srl][$regex]))
							{
								$doc_list[$member_srl][$regex] = $v;
								$doc_list[$member_srl][$regex]['docs'] = [];
							}
							if(preg_match($regex, $val->mid))
							{
								$doc_list[$member_srl]['__module_srls'][ $val->module_srl ] = $val->count;
								$count = $val->count * 1000;
								while( isset($doc_list[$member_srl][$regex]['docs'][$count]) )
								{
									$count++;
								}
								$doc_list[$member_srl][$regex]['docs'][$count] = $val;


								$continue = true;
								break;
							}
						}
					}
				}
				if($continue)
				{
					continue;
				}

				$count = $val->count * 1000;
				while( isset($doc_list[$member_srl]['__default']['docs'][$count]) )
				{
					$count++;
				}
				$doc_list[$member_srl]['__default']['docs'][$count] = $val;
			}
		}
		return $doc_list[$member_srl];
	}
	public function getCategory(INT $member_srl) : array
	{
		static $doc_list = [];
		if(!isset($doc_list[$member_srl]))
		{
			$doc_list[$member_srl] = [
				'__default' => [
					'docs' => []
				],
				'__module_srls' => []
			];

			$config = $this->getConfig();
			if(!count($config->doc_list))
			{
				return $doc_list[$member_srl];
			}
			$args = new \stdClass();
			$args->member_srl = $member_srl;
			$data = executeQueryArray('elkhabook.getCategory', $args)->data;
			foreach($data as $val)
			{
				$continue = false;
				if(is_string($val->mid) && strlen($val->mid))
				{
					foreach($config->doc_list as $regex => $v)
					{
						if(strlen($regex))
						{
							if(!isset($doc_list[$member_srl][$regex]))
							{
								$doc_list[$member_srl][$regex] = $v;
								$doc_list[$member_srl][$regex]['docs'] = [];
							}
							if(preg_match($regex, $val->mid))
							{
								$doc_list[$member_srl]['__module_srls'][ $val->module_srl ] = $val->count;
								$count = $val->count * 1000;
								while( isset($doc_list[$member_srl][$regex]['docs'][$count]) )
								{
									$count++;
								}
								$doc_list[$member_srl][$regex]['docs'][$count] = $val;


								$continue = true;
								break;
							}
						}
					}
				}
				if($continue)
				{
					continue;
				}

				$count = $val->count * 1000;
				while( isset($doc_list[$member_srl]['__default']['docs'][$count]) )
				{
					$count++;
				}
				$doc_list[$member_srl]['__default']['docs'][$count] = $val;
			}
		}
		return $doc_list[$member_srl];
	}
	public function getElkhabookList()
	{
		$act = isset($this->act) && strlen($this->act) ? $this->act : \Context::get('act');
		if($act == __FUNCTION__)
		{
			if(!($member_srl = (INT)\Context::get('member_srl')) || !is_object($member_info = \MemberModel::getMemberInfoByMemberSrl($member_srl)) || $member_info->member_srl != $member_srl)
			{
				$member_info = new stdClass();
				$member_info->member_srl = 0;
				$member_info->nick_name = '?';
			}
			\Context::set('_member_info', $member_info);
		}

		$config = $this->getConfig();
		$oTemplateHandler = \TemplateHandler::getInstance();
		$tpl = $oTemplateHandler->compile($this->module_path . "skins/$config->skin", __FUNCTION__);
		$this->add(__FUNCTION__, $tpl);
	}
	public function getDocList(INT $member_srl, INT $page, INT $module_srl = 0) : \baseObject
	{
		$config = $this->getConfig();
		$obj = new stdClass();
		$obj->member_srl = $member_srl;
		$obj->list_count = $config->list_count;
		$obj->sort_index = 'list_order';
		$obj->order_type = 'ASC';
		$obj->page = $page ?: 1;

		$module_srls = array_keys($this->getCategory($member_srl)['__module_srls']);
		if($module_srl)
		{
			if(in_array($module_srl, $module_srls))
			{
				$obj->module_srl = $module_srl;
			}
			else
			{
				$output = $this->createObject();
				$output->data = [];
				$output->page_navigation = new \PageHandler(0, 0, 0);
				return $output;
			}
		}
		else if(count($module_srls))
		{
			$obj->module_srl = implode(',', $module_srls);
		}

		//$columns = array('document_srl','title','content','regdate','module_srl');
		$oDocumentModel = getModel('document');
		$output = $oDocumentModel->getDocumentList($obj, FALSE, FALSE/*, $columns*/);
		return $output;
	}

	public function getCmtList(INT $member_srl, INT $page = 1, INT $module_srl = 0) : \baseObject
	{
		//$oModel = getModel('comment');
		//$comments = $oModel->getCommentListByMemberSrl($member_srl, ['comment_srl'], 1, FALSE, $list_count);
		$config = $this->getconfig();
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->list_count = $config->list_count;
		$args->page = $page ?: 1;

		$module_srls = array_keys($this->getCategoryCmt($member_srl)['__module_srls']);
		if($module_srl)
		{
			if(in_array($module_srl, $module_srls))
			{
				$args->s_module_srl = [$module_srl];
			}
			else
			{
				$output = $this->createObject();
				$output->data = [];
				$output->page_navigation = new \PageHandler(0, 0, 0);
				return $output;
			}
		}
		else if(count($module_srls))
		{
			$args->s_module_srl = $module_srls;
		}

		$args->s_member_srl = $args->member_srl;
		unset($args->member_srl);
		$output = executeQueryArray('elkhabook.getCommentList', $args);

		$oDocumentModel = getModel('document');
		require_once(_XE_PATH_.'modules/comment/comment.item.php');
		foreach($output->data as $key => $comment)
		{
			$comment->member_srl = $member_srl;
			$oComment = new commentItem(0);
			$oComment->setAttribute($comment);
			$oComment->oDocument = $oDocumentModel->getDocument($oComment->get('document_srl'), FALSE, FALSE);
			$output->data[$key] = $oComment;
		}
		return $output;
	}
	public function getBrowserTitle(int $module_srl)
	{
		static $title_list = [];
		if(!isset($title_list[$module_srl]))
		{
			$module_info = \ModuleModel::getModuleInfoByModuleSrl($module_srl);
			if(is_object($module_info) && $module_info->module_srl == $module_srl)
			{
				$title_list[$module_srl] = $module_info->browser_title;
			}
			else
			{
				$title_list[$module_srl] = '?';
			}
		}
		return $title_list[$module_srl];
	}
	public function getMid(int $module_srl)
	{
		static $mid_list = [];
		if(!isset($mid_list[$module_srl]))
		{
			$module_info = \ModuleModel::getModuleInfoByModuleSrl($module_srl);
			if(is_object($module_info) && $module_info->module_srl == $module_srl)
			{
				$mid_list[$module_srl] = $module_info->mid;
			}
			else
			{
				$mid_list[$module_srl] = '';
			}
		}
		return $mid_list[$module_srl];
	}
	// 닉변 기록
	public function getNickList(int $member_srl) : array
	{
		if(!$member_srl)
		{
			return [];
		}
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		return executeQueryArray('member.getMemberModifyNickName', $args)->data;
	}
	public function getChatList(INT $member_srl, INT $page, int $room = 0)
	{
		$config = $this->getconfig();

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->list_count = $config->list_count;
		$args->page = $page ?: 1;
		if($room > 0)
		{
			$args->room = $room;
		}
		else
		{
			$args->room = [];
			$rooms = array_keys($config->elkhatalk_rooms);
			foreach($rooms as $room)
			{
				$args->room[] = (INT)preg_replace('/[^0-9]+/u', '', $room);
			}
		}

		$output = executeQueryArray('elkhabook.getElkhatalk', $args);

		require_once(__DIR__.'/elkhatalk.item.php');
		foreach($output->data as $key => $comment)
		{
			$comment->member_srl = $member_srl;
			if(isset($comment->msg))
			{
				$comment->content = &$comment->msg;
				$comment->valid = $comment->option=='D'? 'N' : 'Y';
			}
			$oComment = new \elkhabook\elkhatalk();
			$oComment->setAttribute($comment);
			$output->data[$key] = $oComment;
		}
		return $output;
	}
	public function getChatListByPk(INT $pk = 0, INT $page_count = 7)
	{
		$config = $this->getConfig();
		$pk_less_prev = 0; // 이전 페이지 존재하는지 체크하기.
		Context::set('prev_exists', FALSE);

		$columns = array();
		$args = new stdClass();
		if($pk <= 0)
		{
			$epage = (INT)Context::get('epage');
			// 0 페이지는 초기 화면.
			if($epage <= 0)
			{
				$args->list_count = $config->list_count; // 15
			}
			else
			{
				// list_count, sort 대신에 pk 지정함.
				$args->pk_more = ($epage -1) * $config->list_count + 1;
				$args->pk_less = $pk_less_prev = $epage * $config->list_count + 1; // + 1이전 페이지 존재하는지 체크하기
			}
		}
		else
		{
			$args->pk = $pk;
			$args->pk_more = $pk - $page_count;
			$args->pk_less = $pk_less_prev = $pk + $page_count + 1; // + 1이전 페이지 존재하는지 체크하기
		}
		$output = executeQueryArray('elkhabook.getElkhatalkByPk', $args);

		if(count($output->data))
		{
			$reset = reset($output->data);
			if($pk_less_prev && $reset->pk==$pk_less_prev)
			{
				$first = array_shift($output->data);
				Context::set('prev_exists', $pk_less_prev);
			}
		}

		// 현재 페이지 구하기
		if($pk > 0)
		{
			Context::set('epage', ceil($pk / $config->list_count) );
		}
		else if(count($output->data))
		{
			$reset = reset($output->data);
			Context::set('epage', ceil($reset->pk / $config->list_count) );
		}


		require_once(__DIR__.'/elkhatalk.item.php');
		foreach($output->data as $key => $comment)
		{
			if(isset($comment->msg))
			{
				$comment->content = &$comment->msg;
				$comment->valid = $comment->option=='D'? 'N' : 'Y';
			}
			$oComment = new \elkhabook\elkhatalk();
			$oComment->setAttribute($comment);
			$output->data[$key] = $oComment;
			if($pk && $pk==$oComment->get('pk'))
			{
				$output->oComment = $oComment;
			}
		}
		return $output;
	}
	public function isAccessible($obj) : bool
	{
		if(isset($obj->elkhatalk))
		{
			return TRUE;
		}
		if( $obj->get('document_srl') )
		{
			$oDocument = \DocumentModel::getDocument($obj->get('document_srl'), FALSE, FALSE);
			return $oDocument->isAccessible(TRUE);
		}
		return FALSE;
	}
	public static function getBadges($chzzkid) {
		$cacheKey = 'elkhabook:badges:' . $chzzkid;
		$cached = \Rhymix\Framework\Cache::get($cacheKey);
		if ($cached !== null) {
			return $cached;
		}

		$url = 'http://127.0.0.1:9333/api/badges/' . $chzzkid;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		$response = curl_exec($ch);
		
		if(!$response)
		{
			return '';
		}

		$json = json_decode($response);
		if(!$json || !$json->success || !isset($json->data))
		{
			return '';
		}

		$data = $json->data;
		$html = [];

		if(isset($data->subscription) && is_object($data->subscription) && isset($data->subscription->badge) && isset($data->subscription->badge->imageUrl))
		{
			$tier = $data->subscription->tier ?? 0;
			$tier_name = $tier == 2 ? '호감고닉' : ($tier == 1 ? '고닉' : '구독');
			$month = $data->subscription->accumulativeMonth ?? 0;
			$alt = sprintf('%s %d개월 구독중', $tier_name, $month);
			$html[] = sprintf('<img src="%s" alt="%s" title="%s" style="height:18px;vertical-align:middle;margin-right:3px">', $data->subscription->badge->imageUrl, $alt, $alt);
		}

		if(isset($data->viewerBadges) && is_array($data->viewerBadges))
		{
			$badge_map = [];
			foreach($data->viewerBadges as $val)
			{
				if(isset($val->badge) && isset($val->badge->imageUrl))
				{
					$badge_id = $val->badge->badgeId ?? '';
					$alt = isset($badge_map[$badge_id]) ? $badge_map[$badge_id] : $badge_id;
					$html[] = sprintf('<img src="%s" alt="%s" title="%s" style="height:18px;vertical-align:middle;margin-right:3px">', $val->badge->imageUrl, $alt, $alt);
				}
			}
		}

		$result = implode('', $html);
		\Rhymix\Framework\Cache::set($cacheKey, $result, 3600);

		return $result;
	}
}
