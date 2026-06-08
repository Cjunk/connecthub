<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/bootstrap.php';
// Don't include AuthController here - let the autoloader handle it

$authController = new AuthController();
$authController->showRegister();
