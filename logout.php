<?php
/**
 * Logout script
 */

include 'auth.php';
logout_user();
header('Location: login.php');
exit;
?>