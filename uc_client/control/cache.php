<?php

/*
	[UCenter] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: cache.php 753 2008-11-14 06:48:25Z cnteacher $
*/

!defined('IN_UC') && exit('Access Denied');

class cachecontrol extends base {

	function __construct() {
		$this->cachecontrol();
	}

	function cachecontrol() {
		parent::__construct();
	}

	function onupdate($arr) {
		$this->load("cache");
		$_ENV['cache']->updatedata();
	}

}

?>