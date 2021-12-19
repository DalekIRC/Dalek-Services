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


include "ns_identify.php";
include "ns_register.php";
include "ns_sasl.php";
include "ns_set_pass.php";
include "ns_set_email.php";
include "ns_info.php";


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
		$this->user_id = intval($nick['ID']) ?? 0;
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