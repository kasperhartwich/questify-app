# Questify Mobile

Before starting any task, read these files in order:
1. `overview.md` — product overview, user roles, business rules
2. `mobile-spec.md` — full mobile app specification

All backend API calls, data models, and business logic are defined in those files.
Do not invent endpoints or data structures — use only what is specified.

## What's Included

A pre-configured Laravel + NativePHP Mobile starter template.

- **Laravel** - Latest version with standard configuration
- **NativePHP Mobile** - Pre-installed and configured
- **Laravel Boost**
- **CLAUDE.md** - Generated guidelines for the AI assistant

## Automated Updates

This repository has a GitHub Action that runs daily to keep dependencies up to date:

- Runs `composer update` and commits `composer.lock`
- Runs `npm update` and commits `package-lock.json`
- Runs `npm run build` to verify the build still works (assets are gitignored)

