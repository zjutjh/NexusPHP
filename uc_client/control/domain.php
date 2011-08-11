<?php

/*
	[UCenter] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: domain.php 753 2008-11-14 06:48:25Z cnteacher $
*/

!defined('IN_UC') && exit('Access Denied');

class domaincontrol extends base {

	function __construct() {
		$this->domaincontrol();
	}

	function domaincontrol() {
		parent::__construct();
		$this->init_input();
		$this->load('domain');
	}

	function onls() {
		return $_ENV['domain']->get_list(1, 9999, 9999);
	}
}

?>