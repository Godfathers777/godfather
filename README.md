# Pappy Deployment Notes

This project is a PHP-hosted company onboarding flow. It uses local JSON files
for request status, so no Firebase or external database is required.

## Required Files

Upload or deploy these files together:

- `index.html`
- `config.js`
- `webhook.php`
- `config.php`
- `submit.php`
- `state.php`
- `storage.php`
- `telegram.php`
- `router.php` for PHP built-in server deployments

## Environment Variables

Set these on your host before enabling Telegram:

```text
BOT_TOKEN=your_telegram_bot_token
CHAT_ID=your_telegram_chat_or_channel_id
```

For local smoke tests without sending Telegram messages:

```text
TELEGRAM_ENABLED=0
```

## Local/Container Start

Docker uses:

```sh
docker build -t pappy-test .
docker run --rm -p 8080:8080 pappy-test
```

Nixpacks uses:

```sh
php -S 0.0.0.0:$PORT -t /app router.php
```

The router serves `index.html` at `/`, serves existing static files directly, and
passes unknown paths to `webhook.php`.

## Runtime Flow

1. The browser submits a safe onboarding request to `submit.php`.
2. `submit.php` saves the request in `sessions/*.json`.
3. `submit.php` sends a Telegram admin message with status buttons.
4. Telegram calls `webhook.php` when an admin clicks a button.
5. `webhook.php` updates the matching JSON session.
6. The browser polls `state.php` and updates the status panel.

## Verify

Run PHP syntax checks:

```sh
php -l config.php
php -l index.php
php -l router.php
php -l webhook.php
php -l submit.php
php -l state.php
php -l storage.php
php -l telegram.php
```

Smoke-test the container:

```sh
docker build -t pappy-test .
docker run --rm -p 8080:8080 pappy-test
```

Then open:

```text
http://localhost:8080/
http://localhost:8080/webhook.php
```

## Writable Folder

The app creates a `sessions/` folder automatically. On shared hosting, make sure
PHP can write to the project folder or create `sessions/` manually with write
permission for the PHP user.

## Webhook URL

Set the Telegram webhook to:

```text
https://greyston.com/webhook.php
```
