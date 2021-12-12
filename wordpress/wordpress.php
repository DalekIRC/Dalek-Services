<?php
global $wpconfig;
include "wordpress.conf";


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


