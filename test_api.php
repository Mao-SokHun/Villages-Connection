<?php
require_once 'bootstrap.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/api/admin-counts.php';
$_SERVER['REQUEST_URI'] = '/api/admin-counts.php';

// Mock session
session_start();
$_SESSION['user_id'] = 1;

require 'public/router.php';
