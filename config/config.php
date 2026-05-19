<?php
// ============================================================
// FTRC LET Review System — Configuration
// Reads from environment variables (Vercel) or falls back
// to local values (Laragon)
// ============================================================

// Database
define('DB_HOST', $_ENV['DB_HOST'] ?? 'ep-fancy-thunder-aowl4dse.c-2.ap-southeast-1.aws.neon.tech');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'neondb');
define('DB_USER', $_ENV['DB_USER'] ?? 'neondb_owner');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'npg_dpu9b3nxaEGm');
define('DB_SSL',  $_ENV['DB_SSL']  ?? 'require');

// App
define('APP_NAME', 'FTRC LET Review System');
define('APP_URL',  $_ENV['APP_URL'] ?? 'http://localhost/FTRC');

// AI — Gemini free tier
define('AI_PROVIDER', 'gemini');
define('AI_API_KEY',  $_ENV['AI_API_KEY'] ?? 'AIzaSyC80QigadSVM2fJNzB17qDkDaO7OCcyars');
define('AI_API_URL',  'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=');