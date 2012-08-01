<?php
	require CURRENT_WORKING_DIR . "/libs/config.php";
	
	$has_login_cookie = !empty($_COOKIE['u-login']) ;
	$has_pwd_cookie = !empty($_COOKIE['u-password']) || !empty($_COOKIE['u-password-md5']) ;
	
     if( $has_login_cookie  && $has_pwd_cookie) 
     {
          echo "ok";
          exit;
     }

	$session_name = ini_get("session.name");
	
	if(empty($_REQUEST[$session_name]))
	{
          setcookie("umicms_session", "", time() - 3600, "/");
          echo 'closed';
          exit;
	}

	$session_id = $_REQUEST[$session_name];

	
	session_id($session_id); session_start();
	
	if(empty($_SESSION['starttime']))
	{
          echo 'ok';
          $_SESSION['starttime'] = time();
          session_commit();
          exit;
	}

	$session_start_time = $_SESSION['starttime'];
	
	
          
     $Difference_time_sec = time() - $session_start_time;

     // session expires
     if (  $Difference_time_sec > (SESSION_LIFETIME - 0.2) * 60) 
     {
          session_destroy();
          setcookie("umicms_session", "", time() - 3600, "/");
          setcookie($session_name, "", time() - 3600, "/");
          exit( "closed" );
     }
     
     // осталось меньше 1.5 минут
     if ( $Difference_time_sec > (SESSION_LIFETIME - 1.2) * 60) 
     {
          exit( 'warning' );
     }

     exit( "ok" );
     


     
	
?>