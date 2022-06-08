<?php

/* invitation table */

hook::func("preconnect", function()
{
	$conn = sqlnew();
	$conn->query("CREATE TABLE IF NOT EXISTS dalek_invite (
				id int NOT NULL AUTO_INCREMENT,
				code varchar(255) NOT NULL,
				timestamp varchar(255) NOT NULL,
				realtime int NOT NULL,
				PRIMARY KEY(id)
	)");
	$conn->close();
});
