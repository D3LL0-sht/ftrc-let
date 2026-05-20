<?php
// ============================================================
// FTRC LET Review System — Configuration
// ============================================================

// Database (MySQL - GoogieHost)
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'fbiihujv_ftrc_let');
define('DB_USER',    'fbiihujv_ftrc_let');
define('DB_PASS', 'Ftrc2024!');
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_NAME', 'FTRC LET Review System');
define('APP_URL',  'http://ftrcreview.cu.ma');

// AI — Gemini free tier
define('AI_PROVIDER', 'gemini');
define('AI_API_KEY',  'AIzaSyC80QigadSVM2fJNzB17qDkDaO7OCcyars');
define('AI_API_URL',  'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=');