<?php

// LDAP Configuration Settings for eLoDAP Integration

$ldap_host = 'ldap.example.com'; // LDAP server address
$ldap_port = 389; // LDAP server port
$ldap_user_dn = 'cn=read-only-admin,dc=example,dc=com'; // LDAP user DN
$ldap_user_password = 'password'; // LDAP user password

$ldap_base_dn = 'dc=example,dc=com'; // Base DN for LDAP searches
$ldap_timeout = 10; // LDAP connection timeout in seconds

// Connect to LDAP server
$ldap_conn = ldap_connect($ldap_host, $ldap_port);

if (!$ldap_conn) {
    die('Could not connect to LDAP server.');
}

// Set LDAP options
ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

// Bind to LDAP server
$bind = ldap_bind($ldap_conn, $ldap_user_dn, $ldap_user_password);

if (!$bind) {
    die('Could not bind to LDAP server with provided credentials.');
}

// Successful connection
echo 'LDAP connection established.';

// Close the connection
ldap_unbind($ldap_conn);

?>