<?php
	session_start();
	if ($_SESSION['authenticated'] !== TRUE) {
		header('Location: login.php');
		echo 'You must log in first';
		die();
	}
?>
