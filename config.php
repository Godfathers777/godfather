<?php
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '');
define('CHAT_ID', getenv('CHAT_ID') ?: '');
define('COMPANY_DOMAIN', 'greyston.com');
define('SESSION_DIR', __DIR__ . '/sessions');
define('TELEGRAM_ENABLED', getenv('TELEGRAM_ENABLED') !== '0');
