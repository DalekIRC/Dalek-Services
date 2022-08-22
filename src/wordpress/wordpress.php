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


hook::func("preconnect", function($u)
{
	global $wpconfig;
	$conn = sqlnew();
	
	$result = $conn->query("SELECT option_value FROM ".$wpconfig['dbprefix']."options WHERE option_name = '".$wpconfig['dbprefix']."user_roles'");
	if (!$result)
		die();
	
	$row = $result->fetch_assoc();
	$roles = unserialize($row['option_value']);
	$roles_origin = $roles;
	
	if (!in_array("irc_admin",$roles))
	{
		$admin_array = array();
		$admin_array['name'] = "Services Administrator";
		$admin_array['capabilities'] =
		[
			'can_jupe' => true,
			'can_kill' => true,
			'can_gline' => true,
			'can_ungline' => true,
			'can_gzline' => true,
			'can_ungzline' => true,
			'can_sqline' => true,
			'can_unsqline' => true,
			'can_eline' => true,
			'can_uneline' => true,
			'can_ban' => true,
			'can_unban' => true,
			'can_kick' => true,
			'can_invite' => true,
			'can_topic' => true,
			'can_suspend_account' => true,
			'can_unsuspend_account' => true,
			'can_suspend_channel' => true,
			'can_unsuspend_channel' => true,
			'can_delete_account' => true,
			'can_load_modules' => true,
			'can_unload_modules' => true,
			'can_shutdown' => true,
			'can_forbid_nick' => true,
			'can_forbid_channel' => true,
			'can_unforbid_nick' => true,
			'can_unforbid_channel' => true,
			'can_mode' => true,
			'can_umode' => true,
			'can_chankill' => true,
			'can_ignore' => true,
			'can_oper' => true,
			'extended_info' => true,
			'can_swhois' => true,
			'can_forcelogin' => true,
		];
		$roles["irc_admin"] = $admin_array;
		$admin_array = NULL;
	}
	if (!in_array("irc_oper",$roles))
	{
		$admin_array = array();
		$admin_array['name'] = "Services Operator";
		$admin_array['capabilities'] =
		[
			'can_kill' => true,
			'can_gline' => true,
			'can_ungline' => false,
			'can_gzline' => true,
			'can_ungzline' => false,
			'can_sqline' => false,
			'can_unsqline' => false,
			'can_eline' => false,
			'can_uneline' => false,
			'can_ban' => true,
			'can_unban' => true,
			'can_kick' => true,
			'can_invite' => true,
			'can_topic' => true,
			'can_suspend_account' => true,
			'can_unsuspend_account' => false,
			'can_suspend_channel' => true,
			'can_unsuspend_channel' => true,
			'can_delete_account' => false,
			'can_load_modules' => false,
			'can_unload_modules' => false,
			'can_shutdown' => false,
			'can_forbid_nick' => false,
			'can_forbid_channel' => false,
			'can_unforbid_nick' => false,
			'can_unforbid_channel' => false,
			'can_mode' => true,
			'can_umode' => false,
			'can_chankill' => false,
			'can_ignore' => false,
			'can_oper' => false,
			'extended_info' => true,
			'can_forcelogin' => true,
		];
		$roles["irc_oper"] = $admin_array;
		$admin_array = NULL;
	}
	if (!in_array("irc_helper",$roles))
	{
		$admin_array = array();
		$admin_array['name'] = "Services Helper";
		$admin_array['capabilities'] =
		[
			'can_kill' => false,
			'can_gline' => false,
			'can_ungline' => false,
			'can_gzline' => false,
			'can_ungzline' => false,
			'can_sqline' => false,
			'can_unsqline' => false,
			'can_eline' => false,
			'can_uneline' => false,
			'can_ban' => true,
			'can_unban' => true,
			'can_kick' => true,
			'can_invite' => true,
			'can_topic' => true,
			'can_suspend_account' => false,
			'can_unsuspend_account' => false,
			'can_suspend_channel' => false,
			'can_unsuspend_channel' => false,
			'can_delete_account' => false,
			'can_load_modules' => false,
			'can_unload_modules' => false,
			'can_shutdown' => false,
			'can_forbid_nick' => false,
			'can_forbid_channel' => false,
			'can_unforbid_nick' => false,
			'can_unforbid_channel' => false,
			'can_mode' => true,
			'can_umode' => false,
			'can_chankill' => false,
			'can_ignore' => false,
			'can_oper' => false,
			'extended_info' => true,
		];
		$roles["irc_helper"] = $admin_array;
		$admin_array = NULL;
	}
	if ($roles !== $roles_origin)
	{
		$up = serialize($roles);
		$conn->query("UPDATE ".$wpconfig['dbprefix']."options SET option_value = '$up' WHERE option_name = '".$wpconfig['dbprefix']."user_roles'");
	}
});
/* WordPress plugin "Disable User Account" compatibility" */
include "_is_disabled.php";
include "fail2ban.php";


define("LOOKUP_BY_ID", "0-001");
define("LOOKUP_BY_ACCOUNT_NAME", "0-002");
define("LOOKUP_BY_EMAIL", "0-003");
class WPUser {

	function __construct($account = "", $searchType = NULL)
	{
		/* if we're doing a specific lookup */
		if ($searchType)
		{
			if ($searchType == LOOKUP_BY_ID)
				$nick = $this->lookup_by_id($account);

			elseif ($searchType == LOOKUP_BY_ACCOUNT_NAME)
				$nick = $this->lookup($account);

			elseif ($searchType == LOOKUP_BY_EMAIL)
				$nick = $this->lookup_by_email($account);
		}
		/* else wing it */
		else
		{
			$nick = $this->lookup($account);
			if (!$nick)
				$nick = $this->lookup_by_id($account);
		}

		if (!$nick)
			$this->IsUser = false;
		else
			$this->IsUser = true;

		if ($this->IsUser)
		{
			$this->user_id = intval($nick['ID']);
			$this->user_login = $nick['user_login'];
			$this->user_nicename = $nick['user_nicename'];
			$this->user_pass = $nick['user_pass'];
			$this->user_email = $nick['user_email'];
			$this->user_url = $nick['user_url'] ?? NULL;
			$this->user_registered = $nick['user_registered'];
			$this->user_status = $nick['user_status'];
			$this->display_name = $nick['display_name'];
			$this->confirmed = (!strlen($nick['user_activation_key'])) ? true : false;
			$this->user_meta = new WPUserMeta($this);
			$uns = isset($this->user_meta->wp_capabilities) ? unserialize($this->user_meta->wp_capabilities) : [];
			$this->role_array = array();
			foreach ($uns as $key => $value)
				if ($value)
					$this->role_array[] = $key;

			$this->IsAdmin = (in_array("administrator",$this->role_array)
			 || in_array("irc_admin",$this->role_array)
			 || in_array("irc_oper",$this->role_array)
			 || in_array("irc_helper",$this->role_array)) ? true : false;
		}
	}

	private function lookup($account = "0")
	{
		global $wpconfig;
		$account = strtolower($account);
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."users WHERE user_login = ?");
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
	function lookup_by_id($account = "0")
	{
		global $wpconfig;
		$account = strtolower($account);
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."users WHERE ID = ?");
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
	/* Not used internally, used for providing an alternate lookup */
	function lookup_by_email($email = NULL)
	{
		global $wpconfig;
		if (!$email)
			return false;
		$account = strtolower($email);
		$conn = sqlnew();
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."users WHERE LOWER(user_email) = ?");
		$prep->bind_param("s",$email);
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
			return $result->num_rows;

		else
			return "0";
	}
}




function wp_get_privs($role)
{
	global $wpconfig;
	$conn = sqlnew();
	if (!$conn) { return false; }
	
	$table = $wpconfig['dbprefix']."options";
	$option = $wpconfig['dbprefix']."user_roles";
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
		if ($val)
			$priv[] = $key;
	}
	
	return $priv;
}


function wp_get_caps($nicename)
{
	global $wpconfig;
	$conn = sqlnew();
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

function ChanAccess2Int($path)
{
	switch ($path)
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
function ChanAccessAsInt(Channel $chan, User $nick)
{
	if (!IsLoggedIn($nick))
		return false;
	if (!($ch = ChanAccess($chan,$nick->account)))
		return false;
	
	return ChanAccess2Int($ch);
}

function WPNewUser(array $user) : bool
{
	global $wpconfig;
	$wp_hasher = new PasswordHash(8, true);
	$username = $user['name'];
	$email = $user['email'];
	$pass = $wp_hasher->HashPassword($user['password']);
	$nicename = strtolower(str_replace(["-", "_"],"",$username));
	$date = new DateTime();
	$date = $date->format('Y-m-d H:i:s');
	$status = 0;

	$code = activation_code();

	$conn = sqlnew();
	$prep = $conn->prepare("INSERT INTO ".$wpconfig['dbprefix']."users (user_login, user_pass, user_nicename, user_email, user_registered, user_activation_key, user_status, display_name)
							VALUES (?,?,?,?,?,?,?,?)");
	$prep->bind_param("ssssssis", $username, $pass, $nicename, $email, $date, $code, $status, $username);
	$prep->execute();
	$conn->close();
	return true;
}



function activation_code()
{
	$i = microtime(false);
	$code = md5(md5(md5($i[strlen($i) -1] * $i[strlen($i) - 2])));
	return $code;
}

function IsRegUser($user){
	
	global $wpconfig;
	$conn = sqlnew();
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."users WHERE user_nicename = lower(?)");
		$prep->bind_param("s",$user);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return false;
		return true;
	}
}


/* Validates whether someone has permission to do a thing
 * @argv1 path = Permission to check (Cannot be NULL)
 * @argv2 user = User to check (Cannot be NULL)
 * @argv3 victim = Victim to check
 * @argv4 chan = Channel associated
 * @argv5 extra = any extra information
 * use NULL if not applicable
 */ 
function ValidatePermissionsForPath(String $path, User $user, User $victim = NULL, Channel $chan = NULL, $extra = NULL) : bool
{
	if (!strlen($path) || !$user)
		return false;
	
	if (!$user->IsWordPressUser) /* No account */
		return false;

	if (!$user)
		return false;

	if ($victim && $victim == $user) /* User should not be the victim */
		$victim = NULL;

	if (isset($user->wp) && in_array("administrator", $user->wp->role_array))
		return true;

	if ($victim && $victim->IsUser)
	{
		if (isset($user->wp) && in_array("irc_admin",$user->wp->role_array))
			return true;
		
		if (isset($user->wp) && in_array("irc_oper",$user->wp->role_array))
			return true;
			
		if (isset($user->wp) && in_array("irc_helper",$user->wp->role_array))
		{
			if (isset($victim->wp) && (in_array("irc_admin",$victim->wp->role_array)
				|| in_array("irc_oper",$victim->wp->role_array)
				|| in_array("irc_helper",$victim->wp->role_array)))
					return false;

			else
				return true;
		}
		
		/* If nobody has privs, let checks happen later */
		
	}
	$path_int = ChanAccess2Int($path);
	
	if ($chan)
	{
		if ($victim)
		{
			if (IsOper($victim))
					return false;
			$v_access = ChanAccessAsInt($chan,$victim);
			$u_access = ChanAccessAsInt($chan,$user);

			if ($u_access)
				return false;

			if ($v_access >= $u_access)
				return false;

			if ($path == "can_kick" || $path == "can_ban" || $path == "can_unban" || $path == "can_topic")
			{
				if ($u_access > $v_access && $u_access >= ChanAccess2Int("operator"))
					return true;
				return false;
			}
			return true;
		}

		else
		{
			if ($path_int <= ChanAccessAsInt($chan, $user))
			{
				return true;
			}
		}
	}

	elseif (isset($user->account) && strlen($user->account) && isset($user->wp))
	{
		for ($i = 0; isset($user->wp->role_array[$i]); $i++)
		{
			$privs = wp_get_privs($user->wp->role_array[$i]);
			if (in_array($path,$privs))
				return true;
		}
	}
	return false;
}
