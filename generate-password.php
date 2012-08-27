<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
<head>
   	<title>Password Generator</title>
</head>
   	<body>
   	<?php 

   	// you'll need a file with a salt to enable this script to work (see http://www.php.net/manual/de/function.crypt.php).
   	// PLEASE PLACE salt.txt OUTSIDE OF YOUR SERVER DIRECTORY!
   	$salt = file_get_contents('../../salt.txt');

   	if(isset($_POST['pass'])){
   		echo '<p>Encrypted password: <b>'.substr(crypt($_POST['pass'], $salt), strlen($salt)). '</b></p>';
   	}
   	?>
   	<form class="form-inline" method="post">
    	<input type="text" class="input-small" name="pass" placeholder="Password">
    	<button type="submit" class="btn">Encrypt Password</button>
   	</form>
</body>
</html>