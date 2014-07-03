
<?php

//using ldap bind anonymously

// connect to ldap server
$ldapconn = ldap_connect("ldap.nbcs.rutgers.edu")
    or die("Could not connect to LDAP server.");

if ($ldapconn) {

    // binding anonymously
    $ldapbind = ldap_bind($ldapconn);

    if ($ldapbind) {
        echo "LDAP bind anonymous successful...";
    } else {
        echo "LDAP bind anonymous failed...";
    }

}

?>

