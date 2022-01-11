<?php
global $wpconfig;
include "wordpress.conf";
include "wp-phpass.php";

if (!isset($wpconfig['siteurl']) || empty($wpconfig['siteurl']))
{
	if (!isset($wpconfig['dbprefix']) || empty($wpconfig['dbprefix']))
		return;

	$conn = sqlnew();
	$result = $conn->query("SELECT option_value FROM ".$wpconfig['dbprefix']."options WHERE option_name = 'siteurl'");
	if (!$result)
		die("Couldn't query the siteurl");

	$row = $result->fetch_assoc();
	$wpconfig['siteurl'] = $row['option_value'];
	$conn->close();
}


include "NickServ/ns_identify.php";
include "NickServ/ns_register.php";
include "NickServ/ns_sasl.php";
include "NickServ/ns_set_pass.php";
include "NickServ/ns_set_email.php";
include "NickServ/ns_info.php";
include "NickServ/ns_autoprivate.php";
include "NickServ/ns_certfp.php";

include "ChanServ/cs_register.php";
include "ChanServ/cs_autoop.php";
//include "ChanServ/cs_fantasy.php";
include "ChanServ/cs_voice.php";
include "ChanServ/cs_devoice.php";
include "ChanServ/cs_invite.php";
include "ChanServ/cs_op.php";
include "ChanServ/cs_deop.php";
include "ChanServ/cs_kick.php";
/* WordPress plugin "Disable User Account" compatibility" */
include "_is_disabled.php";

class WPUser {

	function __construct($account = "")
	{
		$nick = $this->lookup($account);
		if (!$nick)
			$this->IsUser = false;
		else
			$this->IsUser = true;
		if (!$this->IsUser)
			return;
		$this->user_id = intval($nick['ID']);
		$this->user_login = $nick['user_login'];
		$this->user_nicename = $nick['user_nicename'];
		$this->user_pass = $nick['user_pass'];
		$this->user_email = $nick['user_email'];
		$this->user_url = $nick['user_url'] ?? NULL;
		$this->user_registered = $nick['user_registered'];
		$this->user_status = $nick['user_status'];
		$this->display_name = $nick['display_name'];
		$this->user_meta = new WPUserMeta($this);
		$uns = unserialize($this->user_meta->wp_capabilities);
		$this->role_array = array();
		foreach ($uns as $key => $value)
			if ($value)
				$this->role_array[] = $key;

		$this->IsAdmin = (in_array("administrator",$this->role_array)) ? true : false;
		
		
	}

	private function lookup($account = "0")
	{
		global $wpconfig;
		$account = strtolower($account);
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."users WHERE user_nicename = ?");
		$prep->bind_param("s",$account);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result)
		{
			$prep->close();
			return false;
		}
		
		$result = $result->fetch_assoc();
		$prep->close();
		return $result;
	}
	function SetEmail($email)
	{
		global $wpconfig;
		if (!$this->IsUser)
			return false;
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE ".$wpconfig['dbprefix']."users SET user_email = ? WHERE ID = ?");
		$prep->bind_param("ss",$email,$this->user_id);
		$prep->execute();
		$prep->close();
		$this->user_email = $email;
	}
	function SetPassword($password)
	{
		global $wpconfig;
		if (!$this->IsUser)
			return false;
		$wp_hasher = new PasswordHash(8, true);
		if (($password = $wp_hasher->HashPassword($password)) == "*")
			return false;

		$conn = sqlnew();
		if (!$conn)
			return false;

		$prep = $conn->prepare("UPDATE ".$wpconfig['dbprefix']."users SET user_pass = ? WHERE user_nicename = ?");
		$prep->bind_param("ss",$password,$this->user_nicename);
		$prep->execute();
		$prep->close();
		return true;
	}
	function ConfirmPassword($input)
	{
		if (!$this->IsUser)
			return false;
		$p = $input;
	  	$wp_hasher = new PasswordHash(8, true);
		if ($wp_hasher->CheckPassword($p,$this->user_pass))
			return true;
		return false;
	}
}

class WPUserMeta {

	function __construct(WPUser $account)
	{
		$this->lookup($account->user_id);
		$this->num_posts = $this->GetNumPosts($account->user_id);
	}

	function lookup(int $id)
	{
		global $wpconfig;
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."usermeta WHERE user_id = ?");
		$prep->bind_param("i",$id);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result)
		{
			$prep->close();
			return false;
		}
		while($row = $result->fetch_assoc())
			$this->{$row['meta_key']} = $row['meta_value'];

		$prep->close();
		
	}
	function GetNumPosts(int $id)
	{
		global $wpconfig;
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."posts WHERE post_author = ?");
		$prep->bind_param("i",$id);
		$prep->execute();
		$result = $prep->get_result();
		if ($result)
		{
			$prep->close();
			return $result->num_rows;
		}
		else
			return "0";
	}
}




function wp_get_privs($role)
{
	global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	
	$table = $cf['wp_prefix']."_options";
	$option = $cf['wp_prefix']."_user_roles";
	$prep = $conn->prepare("SELECT * FROM $table WHERE option_name = ?");
	$prep->bind_param("s",$option);
	$prep->execute();
	$result = $prep->get_result();
	
	if (!$result){ return false; }
	if ($result->num_rows == 0)
	{
		return false;
	}
	
	$privs = array();
	$priv = array();
	$row = $result->fetch_assoc();
	$privs = unserialize($row['option_value']);
	
	if (!is_array($privs))
		return false;
	
	
	
	foreach ($privs[$role]['capabilities'] as $key => $val)
	{
		$priv[] = $key;
	}
	
	return $priv;
}


function wp_get_caps($nicename)
{
	global $sqlip,$sqluser,$sqlpass,$sqldb,$wpconfig;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	
	$user = new WPUser($nicename);
	$table = $wpconfig['dbprefix']."usermeta";
	$option = $wpconfig['dbprefix']."capabilities";
	
	$prep = $conn->prepare("SELECT * FROM $table WHERE meta_key = ? AND user_id = ?");
	$prep->bind_param("si",$option,$user->id);
	$prep->execute();
	$result = $prep->get_result();
	
	if (!$result){ return false; }
	if ($result->num_rows == 0)
	{
		return false;
	}
	
	$row = $result->fetch_assoc();
	
	$perms = array();
	$perms = unserialize($row['meta_value']);
	
	$perm = array();
	foreach ($perms as $p => $val)
		$perm[] = $p ;
		
	return $perm;
}

function IsAdmin($caps)
{
	if (!$caps)
		return;
	if (is_array($caps)) {
			if (in_array("services-admin",$caps) || 
			in_array("administrator",$caps) ||
			in_array("services-operator",$caps))
				return true;
	}
}

function ChanAccess(Channel $channel,$nick)
{
	foreach ($channel->access as $key => $val)
	{
		if (strtolower($key) == strtolower($nick))
			return $val;			
	}
	return false;
}

function ChanAccessAsInt(Channel $chan, User $nick)
{
	if (!($ch = ChanAccess($chan,$nick->nick)))
		return false;
	
	switch ($ch)
	{
		case "owner":
		return 5;
	
		case "admin":
		return 4;
		
		case "operator":
		return 3;

		case "halfop":
		return 2;
		
		case "voice":
		return 1;
	}
}
