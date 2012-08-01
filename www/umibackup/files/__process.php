<?php






function sendLetter($to, $subject, $message, $treatment) {
	
	$headers  = "MIME-Version: 1.0\r\n"
			   ."Content-type: text/html; charset=utf-8\r\n"
			   ."From:=?utf-8?b?".base64_encode('TATARMP3.RU')."?= <noreply@tatarmp3.ru>\r\n";

	$message = "<html>
					<head>
						<title>".$subject."</title>
					</head>
					<body style='font-size: 14px; color: #333; '>
						<div style='width: 500px; margin: 50px auto 50px auto; padding: 10px; border: 10px solid #fafafa;'>
							<a style='float: right; margin: 10px; ' href='http://tatarmp3.ru/'>
								<img src='http://tatarmp3.ru/_images/logo.png' alt='tatarmp3.ru' />
							</a>
							".$message."
						</div>
					</body>
				</html>";
	$to = "=?utf-8?b?".base64_encode($treatment)."?= <".$to.">";
	$subject = "=?utf-8?b?".base64_encode($subject)."?=";
	
	return mail($to, $subject, $message, $headers);
	
}

$send = sendLetter("basurovav@yandex.ru", "тема", "сообщение", "имя");
if ($send) { echo  "отправлено"; }

?>

