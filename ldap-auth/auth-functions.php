<?php
	require_once("auth-config.php");

	function
	report_error($lineNo, $why, $ds=NULL) { 

		if ($ds) {
			$ldaperrno  = ldap_errno($ds);
			$ldaperrstr = ldap_error($ds);
		}
		else {
			$ldaperrno  = 0;
			$ldaperrstr = ldap_error($ds);
		}

		$return = array();
		
		$return['success'] = FALSE;
		$return['detail']  = $ldaperrstr;
		$return['why']     = $why;
		$return['line']    = $lineNo;
		$return['errno']   = $ldaperrno;

		// if the bind failed, this is an auth failure
		$return['error'] = $why == 'bind failure'?'authfailure':'internalerror';

		ldap_unbind($ds);	
		return $return;

	} // end report_error

	function
	ldap_login($netid, $search_base, $server, $port, $passwd) {

		// connect to the ldap server and bind anonymously
		if (!($ds=ldap_connect($server,$port)))
			return report_error(__LINE__, "connection failure");
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		if (!($bind=ldap_bind($ds,null,null)))
			return report_error(__LINE__, "anonymous bind failure", $ds);

		// search the ldap directory for dn that corresponds to the given netid
		if (!($result=ldap_search($ds,$search_base,"uid=$netid",Array("dn"))))
			return report_error(__LINE__, "search failure", $ds);	
		if (!($entries=ldap_get_entries($ds,$result)))
			return report_error(__LINE__, "get entry failure", $ds);
		
		// if no users found, then this is an authfailure
		if ($entries['count'] == 0)
			return report_error(__LINE__, "authfailure", $ds);

		$dn = $entries['0']['dn'];
		
		// use the dn to bind securely to the ldap server
		if (!($bind=ldap_bind($ds,$dn,$passwd)))
			return report_error(__LINE__, "bind failure", $ds);

		// get basic user info from ldap directory
		$attributes = Array("dn", "cn");
		if (!($result=ldap_search($ds,$search_base,"uid=$netid",$attributes)))
			return report_error(__LINE__, "search failure", $ds);	
		$entries = ldap_get_entries($ds, $result);
		$user_info = Array();
	
		// close the ldap connection
		ldap_unbind($ds);

		// craft user_info array
		$user_info['cn'] = $entries[0]['cn'][0];
		$user_info['dn'] = $entries[0]['dn'];
		$user_info['netid'] = $netid;
	
		// craft return array from success status and user_info then return it
		$return['success'] = TRUE;
		$return['userinfo'] = $user_info;
		return $return; 
	
	} // end ldap_login
?>
