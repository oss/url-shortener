<?

	ini_set('display_startup_errors', 1);
	ini_set('display_errors', 1);
	error_reporting(-1);

	require_once('auth-functions.php');

	// VALIDATE POST DATA	
	// make sure that netid and passwd parameters exist
	if (! array_key_exists('netid',$_POST)) {
		
		echo "Missing netid attribute. <br />";
		die();
	}
	if (! array_key_exists('passwd',$_POST)) {
		
		echo "Missing passwd attribute. <br />";
		die();
	}

	// ensure that netid and passwd are the only parameters in the post data
	if ( count($_POST) != 2 ) {
		
		echo "Invalid parameters in POST data <br />";
		die();
	}
	
	// attempt to login
	$result = ldap_login($_POST['netid'],
				LDAP_SEARCH_BASE,
					LDAP_SERVER,
						LDAP_PORT,
							$_POST['passwd']);
	echo json_encode($result);	
	
?>
