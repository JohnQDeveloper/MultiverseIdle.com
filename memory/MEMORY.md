# MultiverseIdle v0.2 Memory

## Project Structure
- `pages/` - View files (HTML/PHP templates)
- `code/` - Controller files (matching filenames to pages/)
- `classes/` - Models/classes (always lowercase filenames)
- `func/` - Shared utility functions
- `templates/` - Shared template partials (header, game-header, footer)
- `public/` - Web root (index.php router, css/, js/, img/)
- `sql/` - Reference SQL schema (NEVER modify these)

## Routing
- `public/index.php` handles all routing
- Loads `code/{page}.php` (controller) then `pages/{page}.php` (view)
- Character is loaded in `public/index.php` and auto-saved if `$CharacterDataCache != $Character->Data`
- Pages requiring auth redirect to login; public pages: login, register, index, verify-email

## Database
- Main table: `characters` with columns: id, user_id, name, level, arena_floor, gold, iron, herbs, gems, credits, subscription_expires, last_free_credits_claim, party_json, worker_json, rift_queued, world_boss_queued, highest_rift_level, last_arena_time/log, last_rift_time/log, last_seen, world_boss_log, active_potion_id, potion_expire_time
- DAL class: use `$DAL->r()` for reads, `$DAL->w()` for writes with bound params
- NEVER update .sql files; ask user to run migrations

## Key Patterns
- CSRF token: `$_SESSION['csrf-token']` — include in all POST forms
- Alerts: set `$alert_success` / `$alert_danger` in controller, displayed by game-header.php
- Resources displayed in game-header.php resource bar
- Character data accessed via `$Character->Data['field']`
- CSS framework: PicoCSS v2 + custom.css
- `human_num()` for formatting large numbers

## Store Feature (added 2026-03-12)
- `credits` and `subscription_expires` columns added to `characters` table
- Store page at `/store` — free credits button, QoL subscription (1/3/6/12 months), demo buy button
- Subscription stacks/extends if already active
- `last_free_credits_claim` column tracks 24-hour cooldown on free credit claims; button disables and shows next claim time
- Migration SQL: `ALTER TABLE characters ADD COLUMN credits BIGINT UNSIGNED DEFAULT 0; ALTER TABLE characters ADD COLUMN subscription_expires DATETIME DEFAULT NULL; ALTER TABLE characters ADD COLUMN last_free_credits_claim DATETIME DEFAULT NULL;`
