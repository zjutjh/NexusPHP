<?php
/**
 * Description of activate
 *
 * @author Kebot<i@yaofur.com>
 * @link http://kebot.me/
 */
class Account {
    public static function activate($username,$password,$email)
    {
        dbconn();
        $secret = mksecret();
        $wantpasshash = md5($secret . $password . $secret);

        $query = "INSERT INTO users (username, passhash, secret, editsecret, email, country, gender, status, class, invites, ".($type == 'invite' ? "invited_by," : "")." added, last_access, lang, stylesheet".", uploaded) VALUES 
            ('" . $username . "','". $wantpasshash ."','" .$secret . "','" . ' ' . "','" . "$email" . "'," . '8' . ",'" . 'N/A' . "', 'confirmed', ".'1'.",". 0 .", ".($type == 'invite' ? "'$inviter'," : "") ." '". date("Y-m-d H:i:s") ."' , " . " '". date("Y-m-d H:i:s") ."' , ".'25' . ",".'3'.",".'0'.")";
        print $query;
        $ret = sql_query($query) or sqlerr(__FILE__, __LINE__);
    }

    public static function updatePass($username,$password,$secret)
    {
        $passhash = md5($secret . $wantpassword . $secret);
        $sql="UPDATE `users` SET  `passhash` =  '$passhash'  WHERE  `username` = '$username'";
        print $sql;
        sql_query($sql) or sqlerr(__FILE__, __LINE__);
    }


}
?>
