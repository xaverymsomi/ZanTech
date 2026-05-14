# ZanTech — version matrix

This file is the **detailed** companion to the summary in [README.md](README.md).  
**CDN URLs** are recorded in [cdn-lock.json](cdn-lock.json). When you change `views/header.php`, update `cdn-lock.json` in the same commit.

---

## Application

| Item | Value | Where |
|------|--------|--------|
| ZanTech (kernel / shell) | **1.0.3** | `constants/sys_pref.php` (`ZT_APP_VERSION`) |
| PHP (required) | **^8.2** | `composer.json` → `require.php` |
| Database (migrations) | **Microsoft SQL Server** (T-SQL) | `Database/migrations/*.sql` |

---

## Frontend — CDN (`views/header.php`)

All of the following are loaded globally for the main HTML shell (unless a view omits the standard header).

| Package | Version | Role |
|---------|---------|------|
| [Bootstrap](https://getbootstrap.com/) | **5.3.2** | CSS grid, components, JS bundle |
| [Font Awesome](https://fontawesome.com/) | **6.4.2** | Icons (`fa-solid`, etc.) |
| [jQuery](https://jquery.com/) | **3.7.1** | Legacy DOM / plugins |
| [AngularJS](https://angularjs.org/) | **1.8.2** | `angular`, `angular-animate`, `angular-sanitize` |
| [Angular UI Bootstrap](https://angular-ui.github.io/bootstrap/) | **2.5.6** | Modals, tabs, etc. (`ui-bootstrap-tpls`) |
| [Moment.js](https://momentjs.com/) | **2.29.4** | Dates |
| [ng-file-upload](https://github.com/danialfarid/ng-file-upload) | **12.2.13** | Uploads |
| [angular-filter](https://github.com/a8m/angular-filter) | **0.5.17** | Extra filters |
| [ui-select](https://github.com/angular-ui/ui-select) | **0.20.0** | Select widget + CSS |
| [angularjs-toaster](https://github.com/jirikavi/AngularJS-Toaster) | **3.0.0** | Toasts (JS + CSS from jsDelivr) |
| [angular-toaster](https://www.npmjs.com/package/angular-toaster) (CSS only) | **3.0.0** | Legacy toaster stylesheet (cdnjs) — prefer aligning on one package long-term |

### Fonts (no fixed npm-style version)

| Resource | Notes |
|----------|--------|
| [Google Fonts — Outfit](https://fonts.google.com/specimen/Outfit) | Weights 300–700; URL in `views/header.php` |

### First-party assets (not on CDN)

| Path | Notes |
|------|--------|
| `public/assets/css/zantech-ui.css` | App design system |
| `public/assets/js/zantech.bundle.js` | Bundled Angular controllers / app logic (`?v=` cache-bust in header) |

---

## PHP — Composer (`composer.json`)

| Area | Notes |
|------|--------|
| **PHPUnit** (dev) | `^11.5` |
| **twbs/bootstrap** | `3.3.*` — legacy Composer dependency; **not** the UI Bootstrap version (see CDN **5.3.2** above). |
| **Other packages** | Many use `*` or ranges; run `composer show` after install for exact installed versions. |

To lock PHP dependencies for reproducible installs, use a committed **`composer.lock`** (generate with `composer update` / `composer install` in your environment).

---

## CLI

| Entry | Notes |
|-------|--------|
| `zt` / `zt.php` | PHP CLI; same commands. |

---

## Changelog vs this file

- Bump **`constants/sys_pref.php`** `ZT_APP_VERSION` when you ship a notable framework release.
- Bump **`cdn-lock.json`** (and this file’s tables) whenever **`views/header.php`** CDN URLs or versions change.
