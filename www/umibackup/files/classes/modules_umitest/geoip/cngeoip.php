<?php

function cngeoip4array($ip, $dbfile='') {
    $info = cngeoip4text($ip, $dbfile);
    
    if ( is_array($info) )
	return $info;

    $result = explode('!', $info);
    for($i=1,$s=sizeof($result); $i<$s; $i++) {
	$result[$i] = explode('|', $result[$i]);
    }
    return $result;
}

function cngeoip4array1251($ip, $dbfile='') {
    $info = cngeoip4unUTF(cngeoip4text($ip, $dbfile));

    $result = explode('!', $info);
    for($i=1,$s=sizeof($result); $i<$s; $i++) {
	$result[$i] = explode('|', $result[$i]);
    }
    return $result;
}


function cngeoip_lookup_ip($ip, $dbfile='') {
    $result = cngeoip4array($ip, $dbfile);
    if ( sizeof($result) < 2 )
	return array('','','','','','','','','');
    $old = array(
	'',
	'',
	$result[sizeof($result)-1][6],
	$result[1][4],
	$result[1][5],
	$result[sizeof($result)-1][3],
	$result[sizeof($result)-1][2],
	'',
	'');
    reset ($result);
    foreach ($result as $r) {
	if ( !is_array($r) )
	    continue;
	if ( @$r[0] == 't' ) {
	    $old[0] = $r[3];
	    $old[1] = $r[2];
	}
    }
    reset ($result);
    foreach ($result as $r) {
	if ( !is_array($r) )
	    continue;
	if ( @$r[0] == 'r' ) {
	    $old[7] = $r[3];
	    $old[8] = $r[2];
	}
    }
    return $old;
}

function cngeoip_lookup_ip_cp1251($ip, $dbfile='') {
    $result = cngeoip4array1251($ip, $dbfile);
    if ( sizeof($result) < 2 )
	return array('','','','','','','','','');
    $old = array(
	'',
	'',
	$result[sizeof($result)-1][6],
	$result[1][4],
	$result[1][5],
	$result[sizeof($result)-1][3],
	$result[sizeof($result)-1][2],
	'',
	'');
    foreach ($result as $r) {
	if ( !is_array($r) )
	    continue;
	if ( @$r[0] == 't' ) {
	    $old[0] = $r[3];
	    $old[1] = $r[2];
	}
    }
    reset ($result);
    foreach ($result as $r) {
	if ( !is_array($r) )
	    continue;
	if ( @$r[0] == 'r' ) {
	    $old[7] = $r[3];
	    $old[8] = $r[2];
	}
    }
    return $old;
}

function cngeoip4text($ip, $dbfile='') {
    if ( ereg("^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$", $ip, $res) ) {
	if ( $res[1] > 255 || $res[2] > 255 || $res[3] > 255 || $res[4] > 255) return array('error'=>'invalid ip');
    } else
	return array('error'=>'invalid ip');
    $ipint = ($res[1]<<24) + ($res[2]<<16) + ($res[3]<<8) + $res[4];
    if ( $dbfile == '' )
	$FP = fopen(dirname(__FILE__)."/cngeoip.dat", "r");
    else
	$FP = fopen($dbfile, "r");
    if ( !$FP )
	return array('error'=>'datafile not found');

    $data = fread($FP, 4);
    $unp = unpack('Ndata', $data);
    if ( (($unp['data'] >> 8) & 0xffffff) != 0xcd301b )
	return array('error'=>'invalid datafile signature', 'signature'=>(($unp['data'] >> 8) & 0xffffff));
    if ( (($unp['data']) & 0xff) != 0x01 )
	return array('error'=>'incompatible datafile version', 'version'=>(($unp['data']) & 0xff));
    $LEVEL_SIZE = array();
    for(;;) {
	$data = fread($FP, 1);
	$unp = unpack('Cdata', $data);
	$hi = ($unp['data']>>4) & 0x0f;
	$lo = $unp['data'] & 0x0f;
	$LEVEL_SIZE[] = $hi;
	if ( $hi == 0 )
	    break;
	$LEVEL_SIZE[] = $lo;
	if ( $lo == 0 )
	    break;
    }

    $first_xor_code = 1;
    for($i=0,$left=32;$i<sizeof($LEVEL_SIZE);$i++) {
	if ( $LEVEL_SIZE[$i] == 0 )
	    break;
	$first_xor_code <<= $LEVEL_SIZE[$i];
	$first_xor_code += $left;
	$first_xor_code = (($first_xor_code >> 11) & 0x001fffff) + ( $first_xor_code << 11);
	$left -= $LEVEL_SIZE[$i];
    }
    
    $shift = 32;
    $offset = ftell($FP);
    
    for($l=0; $l<sizeof($LEVEL_SIZE); $l++) {
	$shift -= $LEVEL_SIZE[$l];
	$index = (($ipint>>$shift)) & ((1<<$LEVEL_SIZE[$l])-1);
	$tell = $offset+$index*4;
	fseek($FP, $tell, 0);
	$data = fread($FP, 4);
	$unp = unpack('Ndata',$data);
	$offset = $unp['data'] ^ $first_xor_code ^ $tell;
	$offset &= 0xffffffff;
	if ( $offset & 0x80000000 ) {
	    $tell = $offset & 0x7fffffff;
	    fseek($FP, $tell, 0);
	    $data = fread($FP, 2);
	    $unp = unpack('nlen', $data);
	    $length = ( $unp['len'] ^ $first_xor_code ^ $tell ) &0xffff;
	    $tell += 2;
	    $xor = $tell ^ $first_xor_code;
	    for($i=0,$info=""; $i<$length; $i++) {
		$char = fread($FP, 1);
		$info .= chr((ord($char) ^ $xor) & 0xff );
		$xor = ( ($xor >> 3) &0x1fffffff) ^ ( ($xor & 0x1fffffff) << 3);
	    }
	    break;
	}
    }
    fclose($FP);

    return $info;
}

function cngeoip4unUTF($str) {
    $newstr="";
    $l=strlen($str);
    $i=0;
    while ($i<$l) {
         $code=ord($str[$i]);
         if ($code<0x80) $newstr.=$str[$i];
         else {
              $i++;
              $w=$code*256+ord($str[$i]);

              if ($w>=0xd090) $b=192+$w-0xd090; else $b=95;
              if ($w>=0xd180 && $w<=0xd18f) $b=240+$w-0xd180;
              $newstr.=chr($b);
              }
         $i++;
         }
    return($newstr);
}



?>
