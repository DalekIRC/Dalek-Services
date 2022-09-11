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
\\	Title:		SWHOIS
//				
\\	Desc:		SWHOIS command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class specialwhois {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "specialwhois";
	public $description = "Provides SWHOIS compatibility";
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
		
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		/* Params: CommandAdd( this module name, command keyword, function, parameter count)
		 * the function is a string reference to this class, the cmd_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
        */

		if (!CommandAdd($this->name, 'SWHOIS', 'specialwhois::cmd_swhois', 0))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_swhois($u)
    {
        $parv = explode(" ",$u['params']);

    
        $username = (!($user = new User($parv[0]))->IsUser) ? $parv[0] : $user->nick;
        $switch = $parv[1];
        $tag = $parv[2];
        $priority = $parv[3];

        $whois = ($s = explode(" :",$u['params'])) ? $s[1] : "";
        
        self::SWHOIS("$username $switch $tag $priority $whois");
    }

    /*	SWHOIS command (incoming)
        $parv[1] = UID,
        $parv[2] = +/-,
        $parv[3] = tag,
        $parv[4] = priority,
        $parv[5] = swhois
    */
    public static function SWHOIS($string){
       
        $conn = sqlnew();
        $parv = explode(" ",$string);
        
        $user = (!($u = new User($parv[0]))->IsUser) ? $parv[0] : $u->nick;
        $switch = $parv[1];
        $tag = $parv[2];
        $priority = $parv[3];
        $whois = str_replace("$user $switch $tag $priority ","",$string);
        
        
        if ($switch == "+")
        {
            $whois = ($whois[0] == ":") ? mb_substr($whois,1) : $whois;
            if (!$conn) { return false; }
            else
            {
                $prep = $conn->prepare("INSERT INTO ".sqlprefix()."swhois (tag, uid, priority, swhois) VALUES (?, ?, ?, ?)");
                $prep->bind_param("ssss",$tag,$user,$priority,$whois);
                $prep->execute();
                $prep->close();
            }
            
        }
        elseif ($switch == "-")
        {
            if (!$conn){ return false; }
            else
            {
                if ($whois == "*")
                {
                    $prep = $conn->prepare("DELETE FROM ".sqlprefix()."swhois WHERE uid = ? AND tag = ?");
                    $prep->bind_param("ss",$user,$tag);
                }
                else
                {
                    $prep = $conn->prepare("DELETE FROM ".sqlprefix()."swhois WHERE uid = ? AND tag = ? AND swhois = ?");
                    $prep->bind_param("sss",$user,$tag,$whois);
                }
                $prep->execute();
                $prep->close();
            }
        }
    }


    public static function send_swhois($uid,$tag,$swhois)
    {
        self::del_swhois($uid,$tag);
        $cmd = "$uid + $tag -500 :$swhois";
        self::SWHOIS($cmd);
        S2S("SWHOIS $cmd");
    }
    public static function del_swhois($uid,$tag)
    {
        $cmd = "$uid - $tag -500 *";
        self::SWHOIS($cmd);
        S2S("SWHOIS $cmd");
    }

    static function list_swhois_for_user(User $user, $tag = NULL)
	{
		$swhois = [];
		$conn = sqlnew();
		if (!$tag || $tag == "*")
		{
			$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."swhois WHERE uid = ?");
			$prep->bind_param("s",$user->nick);
		}
		else
		{
			$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."swhois WHERE uid = ? AND tag = ?");
			$prep->bind_param("ss",$user->nick,$tag);
		}
		$prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
			return $swhois;
		
		while($row = $result->fetch_assoc())
			$swhois[$row['tag']] = $row['swhois'];

		return $swhois;
	}

    static function is_swhois($nick,$tag)
    {
        $conn = sqlnew();
        $prep = $conn->prepare("SELECT * FROM ".sqlprefix()."swhois WHERE uid = ? AND tag = ?");
        $prep->bind_param("ss",$nick,$tag);
        $prep->execute();
		$result = $prep->get_result();
		if (!$result || !$result->num_rows)
            return false;
        return true;
    }
}
