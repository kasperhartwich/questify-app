# Questify Mobile

Before starting any task, read these files in order:
1. `overview.md` — product overview, user roles, business rules
2. `mobile-spec.md` — full mobile app specification

All backend API calls, data models, and business logic are defined in those files.
Do not invent endpoints or data structures — use only what is specified.

## iOS Deployment (App Store / TestFlight)

The app is currently on TestFlight (not publicly released).

### Version Bumping

Before packaging, bump the version:
```bash
php artisan native:release patch   # 0.0.1 → 0.0.2
php artisan native:release minor   # 0.0.2 → 0.1.0
php artisan native:release major   # 0.1.0 → 1.0.0
```
Also increment `NATIVEPHP_APP_VERSION_CODE` in `.env` (integer build number).

### Building the IPA

The project uses CI-style manual signing. Required env vars to pass at build time:

```bash
IOS_DISTRIBUTION_CERTIFICATE_PATH=/tmp/questify-dist.p12 \
IOS_DISTRIBUTION_CERTIFICATE_PASSWORD=temp123 \
IOS_DISTRIBUTION_PROVISIONING_PROFILE_PATH="/Users/kasper/Library/MobileDevice/Provisioning Profiles/9c30f5b6-6ec3-46c6-bbdb-aa08397b5d6a.mobileprovision" \
IOS_TEAM_ID=2KF539NBBF \
EXTRACTED_PROVISIONING_PROFILE_UUID=9c30f5b6-6ec3-46c6-bbdb-aa08397b5d6a \
php artisan native:package ios --export-method=app-store --rebuild --no-interaction
```

If no .p12 certificate file exists, export it first:
```bash
security export -k ~/Library/Keychains/login.keychain-db -t identities -f pkcs12 -P "temp123" -o /tmp/questify-dist.p12
```

The IPA is output to `nativephp/ios/build/export/NativePHP.ipa`.

### Uploading to App Store Connect

Add `--upload-to-app-store` to the package command, plus API key credentials:
```bash
--upload-to-app-store \
--api-key-path=/path/to/api-key.p8 \
--api-key-id=ABC123DEF \
--api-issuer-id=01234567-89ab-cdef-0123-456789abcdef
```

### Key Details

- **Team ID**: `2KF539NBBF` (Kasper Hartwich)
- **App ID**: `com.focusweb.questify`
- **Provisioning Profile**: `9c30f5b6-6ec3-46c6-bbdb-aa08397b5d6a` (name: "Questify")
- **Distribution Certificate**: `Apple Distribution: Kasper Hartwich (2KF539NBBF)` — SHA1: `DE528B8FE19D28D892A3841320051175E9414C94`
- Always run `npm run build` before packaging

## iOS Simulator Testing

You CAN and SHOULD run `php artisan native:run ios` yourself. After completing a UI task, always rebuild and verify it in the simulator before reporting it as done.

Use `xcrun simctl` to take screenshots and `cliclick` to tap UI elements. Ghost OS cannot interact with the Simulator's internal webview.

### Device

- **iPhone 17 Pro UDID**: `D82C54BB-96D7-4277-A154-D22AA8ABFED2`

### Commands

```bash
# Boot simulator and open window
xcrun simctl boot D82C54BB-96D7-4277-A154-D22AA8ABFED2
open -a Simulator

# Run app on simulator (skips device selection prompt)
php artisan native:run ios D82C54BB-96D7-4277-A154-D22AA8ABFED2 --no-tty

# Take screenshot (read with Read tool to view)
xcrun simctl io D82C54BB-96D7-4277-A154-D22AA8ABFED2 screenshot /tmp/sim.png

# Uninstall app (clears cached views/sessions)
xcrun simctl uninstall D82C54BB-96D7-4277-A154-D22AA8ABFED2 com.focusweb.questify

# Terminate app
xcrun simctl terminate D82C54BB-96D7-4277-A154-D22AA8ABFED2 com.focusweb.questify

# Launch app (without rebuilding)
xcrun simctl launch D82C54BB-96D7-4277-A154-D22AA8ABFED2 com.focusweb.questify
```

### Clicking UI Elements

The Simulator window's content area starts at macOS coordinates that can be found via AppleScript:

```bash
osascript -e '
tell application "System Events"
    tell process "Simulator"
        set p to position of window 1
        set s to size of window 1
        -- Find the content group (the actual phone screen render area)
        set elems to entire contents of window 1
    end tell
end tell
'
```

The content group position and size (e.g. `pos:1347,126 size:402,874`) maps to the simulator screenshot (1206x2622 at 3x retina). To convert screenshot pixel coordinates to macOS click coordinates:

```
mac_x = content_x + (screenshot_x / 3)
mac_y = content_y + (screenshot_y / 3)
```

Then click with: `cliclick c:{mac_x},{mac_y}`

### Public Pages (no auth required)

`/join`, `/login`, `/register`

### Tester Login

The app has a "Tester" button on the login page that calls `POST /api/v1/auth/tester` (no credentials needed) to instantly log in as a test user.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== nativephp/mobile rules ===

## NativePHP Mobile

- NativePHP Mobile is a Laravel package for building native iOS and Android apps using PHP and native UI components. It runs a full PHP runtime directly on the device with SQLite — no web server required.
- Documentation: `https://nativephp.com/docs/mobile/3/**`
- IMPORTANT: Always activate the `nativephp-mobile` skill every time you work on any NativePHP functionality.

### Build Commands — Tell the User, Never Run

**CRITICAL: Never execute any of these commands yourself. Always instruct the user to run them manually in their terminal.**

| Command | Purpose |
|---|---|
| `npm run build -- --mode=ios` | Build frontend assets for iOS |
| `npm run build -- --mode=android` | Build frontend assets for Android |
| `php artisan native:run ios` | Compile and run on iOS simulator/device |
| `php artisan native:run android` | Compile and run on Android emulator/device |
| `php artisan native:run ios --watch` | Build, deploy, then start hot reload — all in one |
| `php artisan native:watch` | Hot reload (watch for file changes) |
| `php artisan native:open` | Open project in Xcode or Android Studio |

**Always ask which platform before giving any build or run command.** If the user hasn't specified iOS or Android, ask: "Which platform do you want to build/test on — iOS or Android?" Never assume a platform.

When the platform is confirmed, give the relevant command(s) above and tell the user to run it in their terminal. Do not run it yourself.
</laravel-boost-guidelines>

</laravel-boost-guidelines>

</laravel-boost-guidelines>

</laravel-boost-guidelines>

</laravel-boost-guidelines>
