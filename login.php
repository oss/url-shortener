<?php
session_start();
require_once( dirname(__FILE__).'/includes/load-yourls.php' );

yourls_html_head();

echo <<<HTML
<form method="post" action="ldap-auth/auth.php">
	Netid   : <input type="text" name="netid" /><br />
	Password: <input type="password" name="passwd" /><br />
	<input type="submit" value="value" /><br />
</form>
HTML;

yourls_html_footer();

?>
