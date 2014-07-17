<?php

	// turn output buffering on and start the session
	ob_start();
	session_start();

	require_once('auth-functions.php');

	// make sure that netid and passwd parameters exist
	if (! array_key_exists('netid',$_POST)) {
		
		echo "Missing netid attribute. <br />";
		header('Location: ../login.php');
	}
	if (! array_key_exists('passwd',$_POST)) {
		
		echo "Missing passwd attribute. <br />";
		header('Location: ../login.php');
	}

	// ensure that netid and passwd are the only parameters in the post data
	if ( count($_POST) != 2 ) {
		
		echo "Invalid parameters in POST data <br />";
		header('Location: ../login.php');
	}

	// ensure that password is valid	
	if ( strlen($_POST['passwd']) < 10 || strlen($_POST['passwd']) > 63) {
		echo "Invalid password <br />";
		header('Location: ../login.php');
	}
	
	// attempt to login
	$result = ldap_login($_POST['netid'],
				LDAP_SEARCH_BASE,
					LDAP_SERVER,
						LDAP_PORT,
							$_POST['passwd']);
	if ($result['success'] == TRUE) {

		session_regenerate_id();
		$_SESSION['authenticated'] = TRUE;
		$_SESSION['userinfo'] = $result['userinfo'];
		header('Location: ../index.php');
	}
	else {
		header('Location: ../login.php');
	}
?>
