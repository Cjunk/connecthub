<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';
// Don't include AuthController here - let the autoloader handle it

$authController = new AuthController();
$authController->showLogin();