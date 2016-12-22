<?php
	DEFINE ('DB_USER', 'username');
	DEFINE ('DB_PASSWORD', 'password');
	DEFINE ('DB_HOST', 'host');
	DEFINE ('DB_NAME', 'sandbox');
	
	$dbc = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)

	OR die('Could not connect to database' .
		mysqli_connect_error());
	
	function post($name) {
		return(isset($_POST[$name]) ? $_POST[$name] :false);
	}
?>
