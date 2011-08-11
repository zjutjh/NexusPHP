<?php

/*
	[UCenter] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: pm.php 836 2008-12-05 02:25:48Z monkey $
*/

!defined('IN_UC') && exit('Access Denied');

define('PMLIMIT1DAY_ERROR', -1);
define('PMFLOODCTRL_ERROR', -2);
define('PMMSGTONOTFRIEND', -3);
define('PMSENDREGDAYS', -4);

class pmcontrol extends base {

	function __construct() {
		$this->pmcontrol();
	}

	function pmcontrol() {
		parent::__construct();
		$this->load('user');
		$this->load('pm');
	}

	function oncheck_newpm() {
		$this->init_input();
		$this->user['uid'] = intval($this->input('uid'));
		$more = $this->input('more');
		$result = $_ENV['pm']->check_newpm($this->user['uid'], $more);
		if($more == 3) {
			require_once UC_ROOT.'lib/uccode.class.php';
			$this->uccode = new uccode();
			$result['lastmsg'] = $this->uccode->complie($result['lastmsg']);
		}
		return $result;
	}

	function onsendpm() {
		$this->init_input();
		$fromuid = $this->input('fromuid');
		$msgto = $this->input('msgto');
		$subject = $this->input('subject');
		$message = $this->input('message');
		$replypmid = $this->input('replypmid');
		$isusername = $this->input('isusername');
		if($fromuid) {
			$user = $_ENV['user']->get_user_by_uid($fromuid);
			$user = daddslashes($user, 1);
			if(!$user) {
				return 0;
			}
			$this->user['uid'] = $user['uid'];
			$this->user['username'] = $user['username'];
		} else {
			$this->user['uid'] = 0;
			$this->user['username'] = '';
		}
		if($replypmid) {
			$isusername = 1;
			$pms = $_ENV['pm']->get_pm_by_pmid($this->user['uid'], $replypmid);
			if($pms[0]['msgfromid'] == $this->user['uid']) {
				$user = $_ENV['user']->get_user_by_uid($pms[0]['msgtoid']);
				$msgto = $user['username'];
			} else {
				$msgto = $pms[0]['msgfrom'];
			}
		}

		$msgto = array_unique(explode(',', $msgto));
		$isusername && $msgto = $_ENV['user']->name2id($msgto);
		$blackls = $_ENV['pm']->get_blackls($this->user['uid'], $msgto);

		if($fromuid) {
			if($this->settings['pmsendregdays']) {
				if($user['regdate'] > $this->time - $this->settings['pmsendregdays'] * 86400) {
					return PMSENDREGDAYS;
				}
			}
			$this->load('friend');
			if(count($msgto) > 1 && !($is_friend = $_ENV['friend']->is_friend($fromuid, $msgto, 3))) {
				return PMMSGTONOTFRIEND;
			}
			$pmlimit1day = $this->settings['pmlimit1day'] && $_ENV['pm']->count_pm_by_fromuid($this->user['uid'], 86400) > $this->settings['pmlimit1day'];
			if($pmlimit1day || ($this->settings['pmfloodctrl'] && $_ENV['pm']->count_pm_by_fromuid($this->user['uid'], $this->settings['pmfloodctrl']))) {
				if(!$_ENV['friend']->is_friend($fromuid, $msgto, 3)) {
					if(!$_ENV['pm']->is_reply_pm($fromuid, $msgto)) {
						if($pmlimit1day) {
							return PMLIMIT1DAY_ERROR;
						} else {
							return PMFLOODCTRL_ERROR;
						}
					}
				}
			}
		}
		$lastpmid = 0;
		foreach($msgto as $uid) {
			if(!$fromuid || !in_array('{ALL}', $blackls[$uid])) {
				$blackls[$uid] = $_ENV['user']->name2id($blackls[$uid]);
				if(!$fromuid || isset($blackls[$uid]) && !in_array($this->user['uid'], $blackls[$uid])) {
					$lastpmid = $_ENV['pm']->sendpm($subject, $message, $this->user, $uid, $replypmid);
				}
			}
		}
		return $lastpmid;
	}

	function ondelete() {
		$this->init_input();
		$this->user['uid'] = intval($this->input('uid'));
		$id = $_ENV['pm']->deletepm($this->user['uid'], $this->input('pmids'));
		return $id;
	}

	function ondeleteuser() {
		$this->init_input();
		$this->user['uid'] = intval($this->input('uid'));
		$id = $_ENV['pm']->deleteuidpm($this->user['uid'], $this->input('touids'));
		return $id;
	}
	
	function onreadstatus() {
		$this->init_input();
		$this->user['uid'] = intval($this->input('uid'));
		$_ENV['pm']->set_pm_status($this->user['uid'], $this->input('uids'), $this->input('pmids'), $this->input('status'));
	}

	function onignore() {
		$this->init_input();
		$this->user['uid'] = intval($this->input('uid'));
		return $_ENV['pm']->set_ignore($this->user['uid']);
	}

 	function onls() {
 		$this->init_input();
 		$pagesize = $this->input('pagesize');
 		$folder = $this->input('folder');
 		$filter = $this->input('filter');
 		$page = $this->input('page');
 		$folder = in_array($folder, array('newbox', 'inbox', 'outbox', 'searchbox')) ? $folder : 'inbox';
 		if($folder != 'searchbox') {
 			$filter = $filter ? (in_array($filter, array('newpm', 'privatepm', 'systempm', 'announcepm')) ? $filter : '') : '';
 		}
 		$msglen = $this->input('msglen');
 		$this->user['uid'] = intval($this->input('uid'));
		if($folder != 'searchbox') {
 			$pmnum = $_ENV['pm']->get_num($this->user['uid'], $folder, $filter);
 			$start = $this->page_get_start($page, $pagesize, $pmnum);
 		} else {
 			$pmnum = $pagesize;
 			$start = ($page - 1) * $pagesize;
 		}
 		if($pagesize > 0) {
	 		$pms = $_ENV['pm']->get_pm_list($this->user['uid'], $pmnum, $folder, $filter, $start, $pagesize);
	 		if(is_array($pms) && !empty($pms)) {
				foreach($pms as $key => $pm) {
					if($msglen) {
						$pms[$key]['message'] = htmlspecialchars($_ENV['pm']->removecode($pms[$key]['message'], $msglen));
					} else {
						unset($pms[$key]['message']);
					}
					unset($pms[$key]['folder']);
				}
			}
			$result['data'] = $pms;
		}
		$result['count'] = $pmnum;
 		return $result;
 	}

 	function onviewnode() {
  		$this->init_input();
  		$this->user['uid'] = intval($this->input('uid'));
 		$pmid = $_ENV['pm']->pmintval($this->input('pmid'));
 		$type = $this->input('type');
 		$pm = $_ENV['pm']->get_pmnode_by_pmid($this->user['uid'], $pmid, $type);
 		if($pm) {
			require_once UC_ROOT.'lib/uccode.class.php';
			$this->uccode = new uccode();
			$pm['message'] = $this->uccode->complie($pm['message']);
			return $pm;
		}
 	}

 	function onview() {
 		$this->init_input();
 		$this->user['uid'] = intval($this->input('uid'));
		$touid = $this->input('touid');
		$pmid = $_ENV['pm']->pmintval($this->input('pmid'));
		$daterange = $this->input('daterange');
 		if(empty($pmid)) {
	 		$daterange = empty($daterange) ? 1 : $daterange;
	 		$today = $this->time - ($this->time + $this->settings['timeoffset']) % 86400;
	 		if($daterange == 1) {
	 			$starttime = $today;
	 		} elseif($daterange == 2) {
	 			$starttime = $today - 86400;
	 		} elseif($daterange == 3) {
	 			$starttime = $today - 172800;
	 		} elseif($daterange == 4) {
	 			$starttime = $today - 604800;
	 		} elseif($daterange == 5) {
	 			$starttime = 0;
	 		}
	 		$endtime = $this->time;
	 		$pms = $_ENV['pm']->get_pm_by_touid($this->user['uid'], $touid, $starttime, $endtime);
	 	} else {
	 		$pms = $_ENV['pm']->get_pm_by_pmid($this->user['uid'], $pmid);
	 	}

 	 	require_once UC_ROOT.'lib/uccode.class.php';
		$this->uccode = new uccode();
		$status = FALSE;
		foreach($pms as $key => $pm) {
			$pms[$key]['message'] = $this->uccode->complie($pms[$key]['message']);
			!$status && $status = $pm['msgtoid'] && $pm['new'];
		}
		$status && $_ENV['pm']->set_pm_status($this->user['uid'], $touid, $pmid);
		return $pms;
 	}

  	function onblackls_get() {
  		$this->init_input();
 		$this->user['uid'] = intval($this->input('uid'));
 		return $_ENV['pm']->get_blackls($this->user['uid']);
 	}

 	function onblackls_set() {
 		$this->init_input();
 		$this->user['uid'] = intval($this->input('uid'));
 		$blackls = $this->input('blackls');
 		return $_ENV['pm']->set_blackls($this->user['uid'], $blackls);
 	}

	function onblackls_add() {
		$this->init_input();
 		$this->user['uid'] = intval($this->input('uid'));
 		$username = $this->input('username');
 		return $_ENV['pm']->update_blackls($this->user['uid'], $username, 1);
 	}

 	function onblackls_delete($arr) {
		$this->init_input();
 		$this->user['uid'] = intval($this->input('uid'));
 		$username = $this->input('username');
 		return $_ENV['pm']->update_blackls($this->user['uid'], $username, 2);
 	}

}

?>