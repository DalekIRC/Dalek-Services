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
\\	Title: Ajoin
//	
\\	Desc: Auto-join on identify.
//	Allows you to add/remove to a list of channels you wish to
\\	be autojoined to when you identify with NickServ.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class ns_pronouns {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_pronouns";
	public $description = "NickServ 'Pronouns' options";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		
		hook::del("UID", 'ns_pronouns::auth');
		hook::del("auth", 'ns_pronouns::auth');
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		global $nickserv;
		if (!AddServCmd(
			'ns_pronouns', /* Module name */
			'NickServ', /* Client name */
			'PRONOUNS', /* Command */
			'ns_pronouns::cmd_func', /* Command function */
			'Show your pronouns in your WHOIS and /'.$nickserv['nick'].' INFO', /* Help string */
			'PRONOUNS', /* Syntax */
			"You can use this command to change your pronoun's visibility in chat.
			Note: You can toggle this setting on the website in addition to this command." /* Extended help */
		)) return false;

		hook::func("auth", 'ns_pronouns::auth');
		return true;
	}

	public static function cmd_func($u)
	{
		$parv = explode(" ",$u['msg']);
		$key = strtolower($parv[1]);
		if ($key !== "on" && $key !== "off")
			return;
		
		$i = ($key == "on") ? 1 : 0;
		
		$nick = $u['nick'];
		$user = $nick->wp;
		if (!$nick->account)
			return;
		$c = unserialize($user->user_meta->pronouns);
		if (self::pn_whois($nick,$i))
			self::set_pronoun($nick->uid,$c,$i);

		self::del_user_cache($user->user_id);
	}

	public static function pn_whois($nick,$i)
	{
		global $wpconfig,$nickserv;

		$ns = Client::find($nickserv['nick']);

		specialwhois::del_swhois($nick->uid,"pronouns");
		$conn = sqlnew();
		if ($i == 1)
		{
			if (!isset($nick->wp->user_meta->pronouns))
			{
				$ns->notice($nick->uid,"You don't have any pronouns set. You can set them by navigating<lf>to your profile on the  website to define your pronouns: ".$wpconfig['siteurl']."/user/$nick->account<lf>and clicking 'Edit Profile'");
				return false;
			}
			$opts = unserialize($nick->wp->user_meta->show_irc);
			if (in_array("Show pronouns in my WHOIS",$opts))
				return true;
			
			array_splice($opts, 0, 0, "Show pronouns in my WHOIS");
			$opts = serialize($opts);

			$prep = $conn->prepare("UPDATE ".$wpconfig['dbprefix']."usermeta set meta_value = ? WHERE user_id = ? AND meta_key = 'show_irc'");
			$prep->bind_param("si",$opts,$nick->wp->user_id);
			$prep->execute();
			$ns->notice($nick->uid,"You have set to have your pronouns visible in your WHOIS");
			return true;		
		}
		else
		{
			if (!isset($nick->wp->user_meta->pronouns))
			{
				$ns->notice($nick->uid,"You don't have any pronouns set. Navigate to your profile on the<lf>website to define your pronouns: ".$wpconfig['siteurl']."/user/$nick->account<lf>and clicking 'Edit Profile'");
				return false;
			}
			$opts = unserialize($nick->wp->user_meta->show_irc);
			if (!in_array("Show pronouns in my WHOIS",$opts))
				return true;
			
			unset($opts[0]);

			$opts = serialize($opts);
			$prep = $conn->prepare("UPDATE ".$wpconfig['dbprefix']."usermeta SET meta_value = ? WHERE user_id = ? AND meta_key = 'show_irc'");
			$prep->bind_param("si",$opts,$nick->wp->user_id);
			$prep->execute();

			$ns->notice($nick->uid,"Your pronouns are no longer visible in your /WHOIS");
			return true;
		}
	}

	public static function auth($u)
	{
		if (!isset($u['account']))
			return;

		$user = new User($u['nick']);
		$umeta = $user->wp->user_meta;

		if (!isset($umeta->pronouns))
			return;
		
		if (!isset($umeta->show_irc))
			return;

		$show_options = unserialize($umeta->show_irc);
		$array = unserialize($umeta->pronouns);

		if (in_array("Show pronouns in my WHOIS",$show_options))
		{

			specialwhois::del_swhois($user->uid,"pronouns");
			self::set_pronoun($user->uid,$array,1);
		}
	}
	public static function set_pronoun($uid,$p = NULL,$i = 0)
	{
		if (!$i)
			return;
		$pronouns = "using ";
		foreach($p as $pnoun)
			$pronouns .= str_replace(" - ","/",$pnoun).", ";

		$pronouns = rtrim($pronouns,", ")." pronouns";
		specialwhois::send_swhois($uid,"pronouns",$pronouns);
		metadata::send_usermeta(NULL,$uid,"pronouns",$pronouns);
	}

	public static function del_user_cache($wpid)
	{
		global $wpconfig;
		$conn = sqlnew();
		$conn->query("DELETE FROM ".$wpconfig['dbprefix']."options WHERE option_name = 'um_cache_userdata_".$wpid."'");
	}
	public static function is_whois_pronoun(WPUser $wp)
	{
		if (!isset($user->wp->user_meta->show_irc))
			return false;

		$show_options = unserialize($user->wp->user_meta->show_irc);
		if (isset($show_options[0]) && $show_options[0] !== NULL)
			return true;
		return false;
	}
}
