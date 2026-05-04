<?php
require_once __DIR__ . '/../config/helpers.php';

session_start();
session_destroy();
jsonResponse(['message' => 'Logged out successfully']);
