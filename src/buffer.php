<?php
/*
 *	(C) 2021 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: Buffer
 *  WHY? Because obvious reasons lol?
 * 
 *	Version: 1
*/

class Buffer
{
	/* our actual buffer */
	static $buf = array();

	/* receive an item to put in the buffer
	 * gotta make sure it's all safe and shit because a lot of it
	 * will go into the SQL db.
	 */
	static function add_to_buffer(String $str)
	{
		/* this is irc bb u need 2 str1p ( ͡° ͜ʖ ͡°)*/
		$str = ircstrip($str);

		/* guess the encoding and convert to UTF-8 if it isn't already */
		$str = mb_convert_encoding($str, "UTF-8");

		/* okay so, if it's a PING, I think we should prioritize it dontcha know */
		if ($parv = explode(" ",$str) && isset($parv) && isset($parv[0]) && $parv[0] == "PING")
			array_unshift(self::$buf, $str);
	   
		/* else, add to de arrae normally */
		else self::$buf[] = $str;
	}

	/* this just gets the top item from the array and posts it back. This is the main call to the top of the buffer. */
	static function get_buffer()
	{
		if (!empty(self::$buf))
		{
			if (isset(self::$buf[0]))
			{
				$p = self::$buf[0];
				array_shift(self::$buf);
				return $p;
			}
		}

		return NULL; 
	}

	/* lets get spooky! */
	static function do_buf(String $str = NULL)
	{
		if ($str)
			self::add_to_buffer($str);
		
		return self::get_buffer();
	}

}