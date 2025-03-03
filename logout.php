<?php
// logout.php - Logout functionality
require_once 'auth.php';

logout();
redirect('login.php');