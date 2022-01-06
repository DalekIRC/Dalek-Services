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
