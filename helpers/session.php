<?php
session_start();

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}