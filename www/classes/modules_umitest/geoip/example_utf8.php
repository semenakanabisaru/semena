<?

if ( !@$_REQUEST['ips'] ) {
    $_REQUEST['ips'] = $_SERVER['REMOTE_ADDR'];
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/plain; charset=utf-8">
<title>CNGeoip4: example</title>
</head>
<body>

<p>Please, put one IP address per line in this textarea:</p>
<form method=get action=example_utf8.php>
<textarea cols=25 rows=5 name=ips><?=@$_REQUEST['ips']?></textarea>
<br>
<input type=submit value="Test this IP addresses">
</form>

<?

include 'cngeoip.php';

$iparr = explode("\n", @$_REQUEST['ips']);

print "<table width=100% border=1 cellpadding=5 cellspacing=5>\n";

foreach ( $iparr as $ip ) {
    $ip = trim($ip);
    if ( !ereg("^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$", $ip) )
	continue;
    print "<tr><th>cngeoip4array(\"$ip\")</th><th>cngeoip_lookup_ip(\"$ip\")</th></tr>\n";
    print "<tr>";
    print "<td valign=top><pre>".print_r(cngeoip4array($ip), true)."</pre></td>";
    print "<td valign=top><pre>".print_r(cngeoip_lookup_ip($ip), true)."</pre></td>";
    print "</tr>\n";
}

print "</table>\n";

?>

</body>
</html>
