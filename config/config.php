<?php
// ============================================================
// FTRC LET Review System — Configuration
// ============================================================

// Database
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'exam');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres'); // change this

// App
define('APP_NAME', 'FTRC LET Review System');
define('APP_URL', 'http://localhost/FTRC');

// AI — choose one and comment out the other

// Option A: Gemini (free tier, no credit card)
define('AI_PROVIDER', 'gemini');
define('AI_API_KEY',  'your_gemini_api_key'); // get from aistudio.google.com
define('AI_API_URL',  'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=');

// Option B: Anthropic (free credits)
// define('AI_PROVIDER', 'anthropic');
// define('AI_API_KEY',  'your_anthropic_api_key'); // get from console.anthropic.com
// define('AI_API_URL',  'https://api.anthropic.com/v1/messages');