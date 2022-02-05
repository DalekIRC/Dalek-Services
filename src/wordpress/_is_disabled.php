<?php


/*				
//	(C) 2021 DalekIRC Services
\\				
//		dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: _is_disabled
//	
\\	Desc: Provides function for WordPress plugin "Disable User Login"
//	
\\	
//	Syntax: none
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/


function _is_disabled(WPUser $user) : int
{
	if (!isset($user->user_meta->_is_disabled))
		return 0;
	if ($user->user_meta->_is_disabled == "1")
		return 1;
	if ($user->user_meta->_is_disabled !== NULL)
		return 0;
	return $user->user_meta->_is_disabled;
}

hook::func("ping", function($u)
{
	global $cf,$wpconfig;
	$conn = sqlnew();
	$result  = $conn->query("SELECT * FROM dalek_user WHERE account IS NOT NULL");
	while ($row = $result->fetch_assoc())
	{
		$nick = new User($row['UID']);
		$user = new WPUser($row['account']);
		if (_is_disabled($user))
		{
			S2S("KILL $nick->nick :Account disabled on website");
			$nick->exit();
		}
		
	}
});




















