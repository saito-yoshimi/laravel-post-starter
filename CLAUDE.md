# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A small Laravel 10 post app ("つぶやき投稿アプリ") distributed as COACHTECH teaching material. Learners use this one app as the subject for Tutorials 13–15 (design → implementation → automation), so the code is intentionally minimal and is expected to be extended. All UI text, code comments, commit messages, and docs are in Japanese — keep to that.

## Commands

Everything runs through Laravel Sail (Docker); there is no host PHP assumed. On Windows, run from a WSL terminal.

```bash
./vendor/bin/sail up -d                       # start app (http://localhost), MySQL, phpMyAdmin (http://localhost:8080)
./vendor/bin/sail down                        # stop
./vendor/bin/sail artisan migrate --seed      # create tables + practice data
./vendor/bin/sail artisan migrate:fresh --seed  # reset; the seeder is not idempotent, so re-seeding needs fresh
./vendor/bin/sail artisan test                # all tests
./vendor/bin/sail artisan test --filter=test_method_name    # single test
./vendor/bin/sail artisan test tests/Feature/ExampleTest.php  # single file
./vendor/bin/sail pint                        # code style (Laravel Pint, default preset)
```

First-time setup (when `vendor/` does not exist yet) is in `README.md`: Composer install via the `laravelsail/php82-composer` container, `cp .env.example .env`, `sail up -d`, `key:generate`, `migrate --seed`. A "Connection refused" from `migrate` right after `sail up` just means MySQL is still starting; retry.

Tests run against the MySQL `testing` database (set in `phpunit.xml`, created by Sail's init script), not SQLite, so Sail must be up to run them. Only the stock `ExampleTest` files exist; there is no `PostFactory`/`CategoryFactory` yet (only `UserFactory`), although both models use `HasFactory`.

## Architecture

**Request flow.** `routes/web.php` has `/` (welcome) plus four post routes inside an `auth` middleware group: `posts.index`, `posts.edit`, `posts.update`, `posts.destroy`. There is no create/store/show — posts only come from the seeder. `PostController` does everything inline (validation via `$request->validate()`, no FormRequest or service classes).

**Authorization.** `PostPolicy` (`update`, `delete`: owner only) is the single source of the "only your own posts" rule. It is not registered in `AuthServiceProvider::$policies`; it resolves by Laravel's policy auto-discovery (`App\Models\Post` → `App\Policies\PostPolicy`). The controller calls `$this->authorize()` in `edit`/`update`/`destroy` (403 for other users' posts), and `posts/index.blade.php` uses `@can` to hide the 編集/削除 buttons. Changes to the rule must keep both sides consistent.

**Authentication.** Laravel Fortify, not Breeze/Jetstream — there are no auth controllers or auth routes in `routes/web.php`. Login/register/logout routes come from Fortify; `FortifyServiceProvider` binds the views (`auth.login`, `auth.register`) and the `app/Actions/Fortify/*` classes hold registration/password logic. Enabled features in `config/fortify.php` are `registration` and `resetPasswords` only (no reset-password views are wired up). Post-login redirect is `/posts` (`config/fortify.php` `home` and `RouteServiceProvider::HOME`).

**Data model.** `User hasMany Post`, `Category hasMany Post`, `Post belongsTo User/Category`. `posts.index` eager-loads `user` and `category`.

**Views.** Five standalone Blade files with no shared layout and no asset pipeline: each has its own inline `<style>` block. `resources/css/app.css` is empty and nothing uses `@vite`, so `npm`/Vite is not needed to run the app. A design change (the "v3" X-style look: white background, hairline borders, centered column, circular avatars, pill buttons) has to be applied in each file. `posts/index.blade.php` derives the avatar gradient from `crc32($post->user->name)` and maps category names to tag colors by their literal Japanese names (お知らせ / 技術メモ / 雑記), so renaming seeded categories falls back to the default grey.

**Seed data.** `DatabaseSeeder` creates 3 categories, 7 users (all password `password`), and 25 posts with staggered `created_at`. `usera@example.com` and `userb@example.com` are the verification accounts — do not change their email/password. Note the README's practice-account table (2 users, 4 posts, `/posts/1`–`/posts/4`) is out of date relative to the seeder: usera owns posts 1, 6, 13, 20 and userb owns 2, 9, 16, 23.

## Non-app folders

- `answers/` — model answers for Tutorial 13 design documents (markdown, `.drawio`, `.png`), organized by chapter (`13-2/` … `13-8/`). Not part of the app. Filename prefixes: `post-` = this app, `cafe-` = a separate exercise app, `register-` = registration test design. Don't modify these when changing app code, and don't treat `cafe-*` as describing this codebase.
- `docs/` — does not exist yet; learners create their own design documents there during Tutorial 13 (e.g. `docs/13-3/`).
