<?php
require_once '../../config/constants.php';
require_once '../../config/bootstrap.php';
require_once '../../src/controllers/AuthController.php';

$authController = new AuthController();
$authController->login();