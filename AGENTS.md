# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Commands

**Run the full stack locally** (Valkey + Web + Cron containers):
```bash
./run-local.sh
```
Requires `env.sh` to be sourced with `DB_USER`, `DB_PASSWORD`, `DB_HOST`, `RESEND_API_KEY`. App runs at `http://localhost`.

**Static analysis:**
```bash
./phpstan.sh   # runs vendor/bin/phpstan analyse -c phpstan.neon
```

**Trigger a cron manually** (inside the cron container):
```bash
docker exec multiverse-idle-cron php /app/crons/run_all.php
docker exec multiverse-idle-cron php /app/crons/arena.php   # individual cron
```

# Architecture

### Language

- func/i18n.php is the core file for all print text
- lang/en.php is the core file for English
- lang/es.php is the core file for Spanish

### Request Flow

All HTTP requests enter through `public/index.php`. Routing is file-based:
- `/api/*` → `api/{name}.php` (runs before any HTML output; handles JSON responses)
- All other paths → optionally load `code/{name}.php` (controller logic, sets variables), then `pages/{name}.php` (HTML view)

Each page view begins with `require_once('../templates/game-header.php')`, which bootstraps the `Character` object from DB (or guest session), handles CSRF validation, and includes the nav/chat UI.

### Bootstrap Order (`config.php`)

1. Constants defined (`DEBUG`, `ENVIRONMENT`, `URL`, `NUMBER_OF_MINUTES_PER_RUN`)
2. Redis connection created (`$redis`)
3. SPL autoloader registered for `classes/*.php` (lowercase filenames)
4. Composer autoload
5. PDO connection → `$DAL` initialized
6. Delight/Auth initialized → session keys mapped to `$_SESSION['auth_*']`
7. CSRF token initialized/validated on POST
8. `func/*.php` utility files loaded
9. `data/*.php` constants loaded (`SKILL_GEMS`, `GEARNAMES_*`, `RESOURCES`)

### Key Globals

| Variable | Type | Purpose |
|---|---|---|
| `$DAL` | DAL | Database access layer (PDO wrapper) |
| `$redis` | Redis | Available everywhere; used for chat, daily floor tracking, dedup locks |
| `$Character` | Character | Loaded per-request in game-header.php; holds all player state |
| `$auth` | Delight\Auth | Authentication library |

### Database Layer (`classes/dal.php`)

Thin PDO wrapper. Always use bound parameters:
```php
$DAL->r("SELECT * FROM table WHERE id=:id", [':id' => $id]);   // read → array|false
$DAL->w("UPDATE table SET x=:x WHERE id=:id", [...]);           // write → bool
$DAL->rows_affected();   // after write
$DAL->last_insert_id();  // after INSERT
```

### Character Data

`Character->Data` is a flat array mixing DB columns and decoded JSON fields:
- `party_json` → `members.frontline` / `members.backline` each with `class`, `level`, `strength`, `dexterity`, `health`, `wisdom`, `gear[]`, `skills[]`, `xp`
- `worker_json` → `resource`, `workers`, `speed_upgrades`, `intelligence_upgrades`, `skills{}`, `skill_xp{}`
- Scalar fields: `arena_floor`, `gold`, `iron`, `herbs`, `gems`, `credits`, `highest_rift_level`, `subscription_expires`, etc.

Guests: `Character->IsGuest()` returns true; data lives only in `$_SESSION['guest_character']`. No DB writes occur.

### Cron System

`crons/run_all.php` is the master script. Each cron calls `ActiveUsers()` (users active within 72 hours) and loops per user. Crons run as CLI (`IS_WEB_CONTEXT = false`). The constant `NUMBER_OF_MINUTES_PER_RUN` controls ticks per run (normally 1).

| Cron | Frequency | Purpose |
|---|---|---|
| `arena.php` | per tick | Battle simulation, stat gains, resource drops |
| `workers.php` | per tick | Resource generation via worker formulas |
| `rifts.php` | hourly | Process queued rift delves (10-battle runs) |
| `world-boss.php` | periodic | World boss raid battles |
| `potions.php` | periodic | Activate/expire potions |
| `season-end.php` | daily | Merge expired season chars into perpetual |

### Guest Mode Parity

`func/guest_processing.php` mirrors cron logic so guests experience offline progression. This is the intentional DRY exception — keep guest processing in sync with the matching cron whenever either is changed.

### Formula Functions (`func/formula_functions.php`)

Key formulas used across crons and guest processing:
- `calculate_monster_attribute(int $level)` → `round(10 * pow($level, 1.4))`
- `worker_yield(harvests, speed_upgrades, skill_level, num_workers, potion_bonus)`
- `calculate_worker_cost(base_cost, multiplier, current_level)`

### Daily Floor Tracking (Redis)

Arena wins update `daily_floor:{user_id}:{Y-m-d}` (48h TTL). This key is the basis for rift stone level ranges when crafting. Guests use `$_SESSION['guest_daily_floor_value']` / `$_SESSION['guest_daily_floor_date']` instead.

# Code Style
### Code Style for PHP
- Adhere strictly to the PSR-12 coding standard.
- Always use strict types (`declare(strict_types=1);`) at the top of PHP files.
- Ensure all functions, methods, and properties have explicit type declarations.
- Use early returns to keep the code structure clean and easy to read (happy path).
- Document with PHPDoc only for generics or complex array shapes that native type hints cannot cover.
- DRY (Do Not Repeat Yourself) coding practices
- API handlers go into `api/`
- Views go into `pages/`
- Code for views (i.e. controllers) go into `code/` with the same file name as the matching view
- `classes/` are a mixture of models and tooling and should always be lower case
- `func/` contains common functions that are one-off tools to maintain DRY

### Code Style for Javascript
- Use vanilla javascript, do not import modules or libraries
- Place reusable pieces of javascript, such as functions, in the `public/js` directory
- DRY (Do Not Repeat Yourself) coding practices

### Code Style for HTML & CSS
- Use vanilla HTML and CSS where possible
- Place css customizations in `public/css/custom.css`
- DRY (Do Not Repeat Yourself) coding practices for CSS

# General Rules
### Database
- NEVER update the `.sql` files
- NEVER attempt to modify the underlying database structure directly. Instead, ask the user to perform structural changes.
- ALWAYS try to use existing functions that call the database before using raw SQL in the DAL class
- ALWAYS use bound parameters to update the database as designed in `DAL.php`

### Performance
- All views, api calls, code paths, and so forth that are not in the `crons/` directory should return in less than 200ms.
- Lists over 50 items must always be paginated
- Keep performance in mind when performing tasks

### Security
- ALWAYS check that the system adheres to the OWASP Top 10
- ALWAYS filter all user input for correctness and safety

### Periodic Actions
The `crons/` directory and `func/guest_processing.php` should cover the same functionality so that guest mode functions correctly. This is the one situation where DRY can be ignored.
