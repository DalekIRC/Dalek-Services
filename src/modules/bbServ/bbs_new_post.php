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
\\	Title: bbServ
//	
\\	Desc: Post to the chat whenever something new goes on the forum
//	
\\	Expects an "forumschan" setting in wordpress.conf
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class bbs_new_post {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "bbs_new_post";
	public $description = "Post to channel whenever there is a new";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS dalek_bbserv_postmeta (
				id int AUTO_INCREMENT NOT NULL,
				post_meta varchar(255) NOT NULL,
				post_value varchar(255) NOT NULL,
				PRIMARY KEY (id)
			)");
		$conn->close();
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("ping", 'bbs_new_post::check_for_new_post');
	}


	function __init()
	{

		hook::func("ping", 'bbs_new_post::check_for_new_post');
		if (IsConnected())
			bbs_new_post::check_for_new_post(NULL);

		return true;
	}

	function check_for_new_post($u)
	{
		global $wpconfig;

		$last = self::get_last();
		$start = ($last) ? $last : 1;
		$conn = sqlnew();
		
		$forums_url = $wpconfig['siteurl']."/forums/";
		$result = $conn->query("SELECT * FROM ".$wpconfig['dbprefix']."posts WHERE ID > ".$start." AND guid LIKE '".$forums_url."%'");
		if (!$result || $result->num_rows < 1)
			return;
			
		
		else {
			$bbs = Client::find("bbServ");
		
			while($row = $result->fetch_assoc())
			{
				if ($row['guid'] == $forums_url)
					continue;
		
				$content = substr($row['post_content'],0,80);
				$author = new WPUser($row['post_author']);
				$bbs->msg($wpconfig['forumschan'],"Forum: ".$author->user_login." \"$content\"... [".$row['guid']."]");
				bbs_new_post::set_last($row['ID']);
			}
		}
	}
	static function set_last($id)
	{
		$conn = sqlnew();
		if (bbs_new_post::get_last() == false)
			$conn->query("INSERT INTO dalek_bbserv_postmeta (post_meta, post_value) VALUES ('last_id', $id)");
		else
			$conn->query("UPDATE dalek_bbserv_postmeta SET post_value=$id WHERE post_meta = 'last_id'");
	}
	static function get_last()
	{
		$conn = sqlnew();
		$result = $conn->query("SELECT * FROM dalek_bbserv_postmeta WHERE post_meta = 'last_id'");
		if (!$result || $result->num_rows < 1)
			return false;

		$row = $result->fetch_assoc();
		return $row['post_value'];

	}
}