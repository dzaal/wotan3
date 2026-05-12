# Wotan3 PHP Framework

A lightweight PHP CMS/framework with session management, MySQL ORM helpers, access control, template rendering, and a modular plugin system.

Originally developed by [Digizaal](https://www.digizaal.nl). Open-sourced as a free installable framework.

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache or Nginx with mod_rewrite / try_files

---

## Quick Install

### 1. Clone

```bash
git clone https://github.com/dzaal/wotan3.git /var/www/yoursite
```

### 2. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE yourdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p yourdb < install/schema.sql
```

### 3. Configure

```bash
cp share/settings/config.example.php share/settings/config.php
nano share/settings/config.php
```

Fill in your MySQL credentials, domain name, and webmaster hostname.

### 4. Required directories (writable by web server)

```bash
mkdir -p share/tmp share/log
chmod 770 share/tmp share/log
```

### 5. Web server

**Nginx** — add to your server block:

```nginx
location / {
    try_files $uri $uri/ /wotan/index.php;
}
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

**Apache** — `.htaccess` in webroot:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ /wotan/index.php [L]
```

### 6. Log in

Browse to `http://yoursite/admin/`
Default credentials: **admin / changeme**
**Change the password immediately** (Users → Edit → set new password).

---

## Directory Structure

```
wotan/               Framework kernel
  head3.php          Session bootstrap, error handling, systemlog
  wotan3.php         Module loader (wotan()), pwcd(), pwsp()
  index.php          Entry point — loads wotan3.php
  phpmod/            Bundled modules (see below)

share/
  settings/
    config.php        Your local config (not in git — use config.example.php)
    config.example.php  Template for config.php
    guests.php        Frontend request handler
    admin.php         Admin request handler
    guests/           Per-page frontend settings (news.php, login.php, …)
    admin/            Per-page admin settings (users.php, webpages.php, …)
  website/            Layout partials (head.xhtml, top.xhtml, foot.xhtml, …)
  templates/          Page templates (.xhtml)
  styles/             CSS
  log/                PHP error log (gitignored)
  tmp/                PHP session files (gitignored)

admin/                Admin UI pages (each with index.php + english.xhtml)
install/
  schema.sql          Database schema + seed data
```

---

## Core Concepts

### `wotan($module, $object, $input)`

Loads a module from `wotan/phpmod/`, instantiates its class, and runs methods on it.

```php
// Connect to MySQL
wotan('mysql3/connect');

// Load a recordset into $result['items']
$result['items'] = pw_dbtoarray("SELECT * FROM webpages WHERE online='1'");

// Render a template file
echo wotan('pw_htmlfile4/pw_htmlfile', '$obj[page]', ['htmlfile=/share/templates/mypage.xhtml', 'runall()']);
```

### `pwcd($paths)`

Path matching — returns `true` when the current URL matches. Supports wildcards.

```php
if (pwcd('/news/*'))         { /* matches /news/anything/ */ }
if (pwcd('/shop/ /basket/')) { /* matches either path */     }
if (pwcd('/admin/* !/admin/login/')) { /* admin except login */ }
```

### Templates (`.xhtml`)

Templates are plain HTML/XML files with `{$variable}` interpolation and Wotan template directives:

```html
<h1>{$echo['seo_title']}</h1>
<p>{$result['items'][1]['name_english']}</p>

<!-- php pw_loop -->
<div>{$result['items'][n]['name_english']}</div>
<!-- php pw_loop end -->
```

### Access Control

Defined per user in the `users` table:

| Column   | Meaning |
|----------|---------|
| `admin`  | URL patterns the user has admin (edit) access to |
| `access` | URL patterns the user can visit |
| `deny`   | URL patterns that are always denied |
| `settings` | Settings file to load (`guests.php` or `admin.php`) |
| `home`   | Redirect URL after login |

---

## Bundled Modules (`wotan/phpmod/`)

| Module | Description |
|--------|-------------|
| `mysql3` | MySQLi connection |
| `mysqltovars3` | Recordset → `$result` arrays |
| `varstomysql3` | POST → INSERT/UPDATE with whitelist |
| `chkusr3` | Login, session auth, brute-force protection |
| `basicfunc` | `pw_dbtoarray`, `pw_arraytodb`, `dz_translate`, helpers |
| `pw_htmlfile4` | Template engine (parse + render `.xhtml`) |
| `var_conv2` | GET/POST sanitisation |
| `email` | Send emails |
| `mailinglist` | Mailing list management |
| `fileread/filewrite` | File I/O helpers |
| `gd_lib` | GD image resize/crop/webp |
| `pager` | Pagination |
| `webpages` | CMS page routing |
| `language` | Browser language detection |
| `vardump` | Developer debug output |
| `xmlrss` | RSS feed builder |
| `banners` | Banner rotation |
| `categorys` | Category tree |
| `dtree` | Directory tree |
| `grouping` | Result grouping |
| `pager` | Pagination |
| `settings` | Admin settings viewer/editor |
| `textfuncs` | String helpers |
| `dirtoarray` | Directory listing → array |
| `randomimage` | Random image picker |

---

## Security Notes

- **Never commit `share/settings/config.php`** — it contains your database credentials.
- Brute-force login protection is built into `chkusr3.php`: 10 failed attempts per IP within 10 minutes triggers a lockout.
- Passwords are stored as bcrypt hashes (`password_hash` / `password_verify`). Plaintext passwords are migrated to bcrypt on first login.
- All MySQL queries use `mysqli_real_escape_string`. Passwords are never put in SQL `WHERE` clauses.

---

## License

MIT License — free to use, modify, and distribute.

---

## Credits

Developed by [Digizaal](https://www.digizaal.nl) — web development, Amsterdam.
