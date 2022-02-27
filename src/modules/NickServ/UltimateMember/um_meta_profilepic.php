<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Profile Picture 2 IRC METADATA
//	
\\	Desc:   Implementation of METADATA `avatar` via
//			WordPress using a plugin called
\\			'Ultimate Member', the most popular
//			profile front-end plugin avaialable.
\\
//			The metadata is applied upon authentication.
\\			Also providing a NickServ command to apply
//		  your meta easily.
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class um_meta_profilepic {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "um_meta_profilepic";
	public $description = "Sets a users avatar from WordPress* with METADATA avatar";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Silence is golden */
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("auth", 'um_meta_profilepic::setpic');
		hook::del("UID", 'um_meta_profilepic::setpic');
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init() : bool
	{
		hook::func("UID", 'um_meta_profilepic::setpic');
		hook::func("auth", 'um_meta_profilepic::setpic');

		nickserv::func("help", 'um_meta_profilepic::ns_hcmd');
		nickserv::func("helplist", 'um_meta_profilepic::ns_cmd');

		nickserv::func("privmsg", 'um_meta_profilepic::picupdate');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = UID string
	 */
	public static function setpic($u)
	{
		if (!isset($u['account']))
			um_meta_profilepic::check_for_gravatar($u);

		$pic = self::get_pic($u['account']);

		if (!$pic)
        {
			if (!($pic = um_meta_profilepic::check_for_irccloud($u)))
                $pic = um_meta_profilepic::check_for_gravatar($u);
        }
		ns_metadata::send_usermeta(NULL, $u['nick'], 'avatar', $pic);
	
		return true;
	}
    static function check_for_irccloud($u)
    {
	    $tok = substr($u['ident'], 0, 3);
        if ($tok !== "sid" && $tok !== "uid")
			return false;
		
		$tok2 = mb_substr($u['ident'], 3);
		if (!is_numeric($tok2))
			return false;

		$irccloudcdn = "https://static.irccloud-cdn.com/avatar-redirect/";
		$img = NULL;
		$img = file_get_contents("$irccloudcdn$tok2");
		if (!$img)
			return false;

		return "$irccloudcdn$tok2";
    }
	static function check_for_gravatar($u)
	{
		global $wpconfig;
		$user = new User($u['nick']);
		$wp_user = new WPUser($user->account);
		$email = $wp_user->user_email;
		$hash = md5($email);


		$url = "https://www.gravatar.com/avatar/$hash?d=".urlencode($wpconfig['default_avatar']);
		return $url;
	}
	static function get_pic($uid)
	{
		global $wpconfig,$os;
		
		$user = new WPUser($uid);
		$conn = sqlnew();
		$table = $wpconfig['dbprefix']."usermeta";

		$prep = $conn->prepare("SELECT * FROM $table WHERE meta_key = 'profile_photo' AND user_id = ?");
		$prep->bind_param("i",$user->user_id);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return false;

		$row = $result->fetch_assoc();
		$type = $row['meta_value'];

		$url = $wpconfig['siteurl']."/wp-content/uploads/ultimatemember/".$user->user_id."/".$type;
		return $url;
	}

	/* nickserv help command */
	static function ns_cmd($u)
	{
		global $ns;
	
		$nick = $u['nick'];
	
		$ns->notice($nick,"PICUPDATE           Updates your IRC avatar according to your profile picture");
	}
	/* help with extra */
	static function ns_hcmd($u)
	{
		global $ns;
		$nick = $u['nick'];
		if ($u['key'] !== "picupdate")
			return;

		$ns->notice($nick,"Syntax: /msg $ns->nick PICUPDATE<lf>".
							"For when you have updated your profile picture and you<lf>".
							"wish to update it in chat.");

	}
	/* So, you want to update your picture */
	static function picupdate($u)
	{
		global $ns;

		$parv = explode(" ",$u['msg']);
		if (strtolower($parv[0]) !== "picupdate")
			return;

		$nick = new User($u['nick']);
		$u['nick'] = $nick->nick;
		$u['account'] = (isset($nick->account)) ? $nick->account : false;

		if (um_meta_profilepic::setpic($u))
			$ns->notice($u['nick'],"Your avatar has been updated");
		
		else
			$ns->notice($u['nick'],"There was a problem fetching your avatar.");
	}
}