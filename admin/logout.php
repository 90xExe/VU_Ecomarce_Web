<?php
require_once __DIR__ . "/../includes/functions.php";
unset($_SESSION["admin_username"]);
flash("success", "Admin logged out.");
redirect("login.php");
