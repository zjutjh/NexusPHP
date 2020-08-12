<?php
require "include/bittorrent.php";
if ($_SERVER["REQUEST_METHOD"] != "POST")
	stderr("Error", "Permission denied!");
dbconn();
loggedinorreturn();                                                    

if (get_user_class() < UC_ADMINISTRATOR)
	stderr("Sorry", "Permission denied.");

$sender_id = ($_POST['sender'] == 'system' ? 0 : (int)$CURUSER['id']);
$dt = sqlesc(date("Y-m-d H:i:s"));
$msg = trim($_POST['msg']);
if (!$msg)
	stderr("Error","Don't leave any fields blank.");
$updateset = $_POST['clases'];
if (is_array($updateset)) {
	foreach ($updateset as $class) {
		if (!is_valid_id($class))  //这种判断可以被直接绕过  当有一个对比参数是整数的时候，会把另外一个参数强制转换为整数。 如果参数开头不是整数就会返回0 把是否等于零放入is_valid_id函数内判断
			stderr("Error","Invalid Class");
	}
}else{
	if (!is_valid_id($updateset) && $updateset != 0)
		stderr("Error","Invalid Class");
}
$subject = trim($_POST['subject']);
$query = sql_query("SELECT id FROM users WHERE class IN (".implode(",", $updateset).")");
while($dat=mysql_fetch_assoc($query))
{
	sql_query("INSERT INTO messages (sender, receiver, added,  subject, msg) VALUES ($sender_id, $dat[id], $dt, " . sqlesc($subject) .", " . sqlesc($msg) .")") or sqlerr(__FILE__,__LINE__);
}

header("Refresh: 0; url=staffmess.php?sent=1");
?>
