<?php
require_once __DIR__ . "/includes/functions.php";
unset($_SESSION["user_email"]);
flash("success", "Logged out.");
redirect("login.php");
?>
