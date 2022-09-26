<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		REGISTRATION
//				
\\	Desc:		Provides Services functionality for the IRCv3 'account-registration'
\\				https://ircv3.net/specs/extensions/account-registration
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class registration {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "registration";
	public $description = "Provides account registration (IRCv3)";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Validation codes table */
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS ".sqlprefix()."verification (
						id int AUTO_INCREMENT NOT NULL,
						account varchar(50) NOT NULL,
						timestamp bigint NOT NULL,
						visitor_code varchar(80) NOT NULL,
						verify_code varchar(80) NOT NULL,
						password varchar(255) NOT NULL,
						PRIMARY KEY (id)
					)");
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		if (!CommandAdd($this->name, 'REGISTRATION', 'registration::cmd_registration', 0))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_registration($u)
	{
		/* User object of caller */
		$nick = $u['nick'];

		/* grab our NickServ Client object lol */
		$ns = Client::find("NickServ");

		$parv = explode(" ",$u['params']);

		$dest = $parv[0];
		$uid = $parv[1];
		$ip = $parv[2];

		$c1 = $parv[3];
		$c2 = $parv[4];

		$data1 = $parv[5];
		$data2 = $parv[6];

		$tok = explode(" :",$u['params']);

		$data3 = str_replace($tok[0]." :","",$u['params']);

		/* if it's not for us, leave it alone */
		if (strcmp(Conf::$settings['info']['services-name'], $dest) && strcmp(Conf::$settings['info']['SID'], $dest))
			return;

		/* User is attempting to register */
		if ($c1 == "R")
		{
			/* incorrect response received, we expect only 'R' from a user. leave it alone I guess */
			if ($c2 !== "R")
				return;

			$account = $data1;
			$email = ($data2 == "*") ? NULL : $data2;
			$password = $data3;
			
			/* user already exists */
			$toCheck = new WPUser($account);
			if ($toCheck->IsUser)
			{
				S2S("REGISTRATION $nick->nick $uid $ip R F ACCOUNT_EXISTS $account :Sorry, that account already exists");
				return;
			}

			/* email is not a proper email format */
			if ($email && !filter_var(filter_var($email, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL))
			{
				S2S("REGISTRATION $nick->nick $uid $ip R F INVALID_EMAIL $email :The email address you provided is invalid.");
				return;
			}
			$toCheck = NULL;

			/* email already has an account, we don't allow multiples */
			$toCheck = new WPUser($email, LKUP_BY_EMAIL);
			if ($toCheck->IsUser)
			{
				S2S("REGISTRATION $nick->nick $uid $ip R F UNACCEPTABLE_EMAIL $email :The email address you provided has been taken.");
				return;
			}
			/* Password doesn't meet expectations */
			if (strlen($password) < 8)
			{
				S2S("REGISTRATION $nick->nick $uid $ip R F WEAK_PASSWORD $account :The password you provided was too weak.");
				return;
			}

			WPNewUser(["name" => $account, "email" => $email, "password" => $password]);


			$ns->svslogin($uid,servertime());

			S2S("REGISTRATION $nick->nick $uid $ip R S $account * :You have successfully registered your account");
		}
		return;
	}
	function generate_verification_link($account,$password)
	{
		global $wpconfig;
		$conn = sqlnew();
		$time = servertime();
		$code = rand(112233,998877);
		$p = md5(md5($account.$password.$time));
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."verification (account, timestamp, visitor_code, verify_code) VALUES (?,?,?,?)");
		$prep->bind_param("siss",$account,$time,$p,$code);
		$prep->execute();

		return $wpconfig['siteurl']."ircverify/?id=$p";
	}
}
