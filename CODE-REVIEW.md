# Questify App — Code Review & Release Readiness

**Date:** 2026-04-03
**Reviewed by:** Tech Lead
**Repositories:** `questify-app` (frontend) · `questify-admin` (backend)

---

## Executive Summary

The app has a solid foundation — the core data model, API layer, authentication, scoring system, and admin panel are well-structured. However, there are **critical gaps** that block a v1 release: incomplete real-time infrastructure, missing phone/OTP auth, no team gameplay support, and several bugs in the mobile gameplay flow.

**Estimated completion:** ~60-70% of v1 spec is implemented. Remaining work is concentrated in real-time features, phone auth, team mode, and polish.

**Estimated remaining work:** ~24-30 dev days (5-6 weeks solo, 3-4 weeks with 2 developers).

---

## 1. Critical Bugs (FIXED — merged in branch `claude/code-review-release-estimate-F8viG`)

These have already been fixed and pushed:

| # | Bug | Fix Applied |
|---|-----|-------------|
| 1 | **Echo/Reverb WebSocket never initialized** — Livewire components listen for Echo events but `bootstrap.js` never sets up Laravel Echo. All real-time features were dead (lobby, leaderboard, host dashboard, session start signal). | Installed `laravel-echo` + `pusher-js`, configured Echo with Reverb broadcaster in `bootstrap.js` |
| 2 | **Active quest map used Google Maps API** — `⚡active-quest.blade.php` references `google.maps.Map` but the app only ships Mapbox GL via npm. Navigation screen map wouldn't render. | Rewrote map section to use `mapboxgl.Map` and `mapboxgl.Marker`, matching the rest of the app |
| 3 | **`deleteFcmToken` didn't pass the token** — `UserApiResource.php:66` called `$this->client->delete('/fcm-tokens')` without appending the token | Changed to `$this->client->delete("/fcm-tokens/{$token}")` |
| 4 | **Quest wizard showed "Step X of 4"** but there are 5 steps — Step indicator `total="4"` across all steps, step 5 showed "4 of 4" | Updated all indicators to `total="5"`, step 5 now shows `current="5"` |

---

## 2. Missing Features — Spec vs Implementation

### 2.1 HIGH PRIORITY — Core to v1 (Blocks Release)

#### Phone/OTP Registration & Login
- **Status:** ❌ Not implemented in either repo
- **Spec requires:** `POST /auth/register/phone`, `POST /auth/verify-otp`, `POST /auth/login/phone`
- **Missing:** Endpoints, OTP storage/validation logic, SMS integration (Twilio/SNS), `phone_number` column on users table
- **Files to create/modify:**
  - Backend: New controller methods, migration for phone_number, OTP model/table, SMS service
  - Frontend: Phone input UI exists in `⚡login.blade.php` and `⚡register.blade.php` but calls non-existent API endpoints

#### Team Mode (Competitive Teams)
- **Status:** ⚠️ Enum exists, no data model or UI
- **Spec requires:** Team assignment in lobby, team names, team leaderboard aggregation
- **Missing:**
  - No `team_id` / `team_name` on `SessionParticipant` model (backend)
  - No team assignment UI in `⚡lobby.blade.php` (drag participants into teams)
  - No team-based leaderboard aggregation in `GameplayController`
- **Files to modify:**
  - Backend: Migration to add team columns, `SessionController`, `GameplayController`
  - Frontend: `⚡lobby.blade.php`, `⚡active-quest.blade.php`, `⚡quest-complete.blade.php`

#### Social Auth Deep Link Return
- **Status:** ⚠️ Unclear if working
- **Spec says:** OAuth callback returns Sanctum token via deep link back to mobile app
- **Risk:** `SocialAuthController` callback exists but the deep link mechanism (`questify://` scheme) for returning the token to NativePHP isn't verified
- **Action:** Test the full OAuth flow on a device/simulator and verify token handoff

#### QR Code Scanner for Join Flow
- **Status:** ❌ Not implemented
- **Spec requires:** QR scanning to join sessions (join screen should have scan option)
- **Note:** `nativephp/mobile-scanner` is installed in composer.json
- **Missing:** No scanner UI in `⚡join.blade.php`; only manual code entry exists
- **Files to modify:** `resources/views/pages/join/⚡join.blade.php`

---

### 2.2 MEDIUM PRIORITY — Expected in v1

| Feature | Status | Details | Files |
|---------|--------|---------|-------|
| **Password Change / Add Password** | ❌ Missing | Spec allows social-only users to add a password. No UI or API call. | `⚡settings.blade.php`, backend needs endpoint |
| **Linked Accounts Display** | ❌ Missing | `getLinkedAccountsProperty()` returns dummy data. No connect/disconnect buttons. Must block disconnect if last login method. | `⚡settings.blade.php` |
| **Splash Screen** | ❌ Missing | Spec §5.1 requires splash with logo, tagline, 2s delay, token check. No route or component exists. | New: `resources/views/pages/splash/⚡index.blade.php`, `routes/web.php` |
| **Answer Shuffling** | ❌ Missing | Spec: multiple choice answers "shuffled on display." Not implemented. | `⚡question-screen.blade.php` |
| **Offline Error Handling** | ⚠️ Basic | Spec requires toast + 3 retries with exponential backoff. Current: basic error handling only. | `WithApiClient.php` trait |
| **Reconnection Indicator** | ❌ Missing | Spec: "show a subtle reconnecting indicator" when WebSocket disconnects | `layouts/app.blade.php` or session views |
| **Quest Access Code Flow** | ⚠️ Partial | Private/school quests need access code entry before CTAs appear | `⚡quest-detail.blade.php` |
| **GPS Accuracy Warning** | ⚠️ Partial | Native geolocation checks `accuracy > 50` and dispatches `gps-weak` event, but no UI shows the warning | `⚡active-quest.blade.php` |
| **Arrival Debounce** | ⚠️ Unverified | Spec: "once triggered for a checkpoint, do not re-trigger." `arrivedAtCurrent` flag exists but verify across navigation | `⚡active-quest.blade.php` |

### 2.3 LOWER PRIORITY — Polish for v1

| Feature | Status |
|---------|--------|
| Celebration animation (confetti) on quest complete | ❌ Not implemented |
| Share result via native share sheet on complete screen | ⚠️ Partial (share link exists, native sheet not wired) |
| Draggable checkpoint reordering in quest wizard step 2 | ❌ Not implemented |
| Filter sheet on discover screen (category, difficulty, duration, rating) | ⚠️ Basic filtering exists |
| Non-interactive map preview on quest detail (starting pin only) | ❌ Not verified |
| Rate-limiting on auth endpoints | ❌ Missing |

---

## 3. Architecture & Code Quality Issues

### 3.1 Frontend

- **Inline Livewire components:** All components are anonymous classes inside blade views (`new class extends Component {}`). Works but makes unit testing harder and IDE support weaker. Not a blocker but increases maintenance cost.
- **Inconsistent API response paths:** Code like `$response['data']['join_code'] ?? $response['data']['session_code'] ?? $response['data']['code']` suggests the API response structure isn't fully standardized. Audit and normalize.
- **Hardcoded Copenhagen coordinates:** Quest creation map defaults to `[12.5683, 55.6761]` instead of user's location. File: `quest-wizard-view.blade.php:118`
- **Session-based gameplay state:** Uses `session('questify_participant_id')` and `session('questify_checkpoint_index')` — fragile. Consider syncing with server state on page load.
- **Hardcoded country codes:** `⚡login.blade.php:250-267` has a static list of phone country codes without i18n.

### 3.2 Backend

- **Well-structured:** Clean controller/service/resource separation, proper Eloquent relationships, type-safe enums, Filament admin with 6 resources.
- **Scoring service:** Well-implemented (base points, speed bonus 30s window, wrong attempt penalties, completion time bonus).
- **Broadcasting events:** 7 events defined and broadcasting correctly.
- **Missing:** Rate limiting on auth endpoints, team data model, phone/OTP infrastructure.

### 3.3 Security Concerns

| Issue | Severity | Details |
|-------|----------|---------|
| **Token storage** | Medium | API tokens stored in PHP session (`session('questify_api_token')`) instead of NativePHP's `SecureStorage` as spec requires |
| **No rate limiting** | Medium | Login, registration, password reset endpoints have no rate limiting |
| **Hardcoded strings** | Low | Welcome page tagline hardcoded in English instead of using `__()` translations |

---

## 4. Test Coverage Gaps

**Current coverage: ~60% estimated across ~95 tests**

| Area | Coverage | Key Gaps |
|------|----------|----------|
| Auth (email) | 85% | Missing: rate limiting, concurrent tokens, token expiration |
| Quest CRUD | 75% | Missing: search endpoint, cover image upload, validation edge cases |
| Gameplay | 70% | Missing: penalty calculations, lockout timing, multi-attempt scoring |
| Sessions | 65% | Missing: concurrent joins, real-time event delivery, team mode |
| User features | 55% | Missing: `GET /user/sessions` (played history), `GET /user/quests` (created list) |
| Scoring | 50% | Missing: bonus + penalty combos, negative scores, boundary conditions |
| Moderation | 20% | Only flag creation tested; admin approve/reject workflow untested |
| Livewire UI | 40% | Page rendering tested; interactions/validation largely untested |

### Critical Tests to Add
1. User created/played quest listing endpoints
2. Quest search functionality (`GET /quests?search=`)
3. Wrong answer penalty point calculations
4. Concurrent session joining (multiplayer safety)
5. Health endpoint
6. Admin moderation approve/reject workflow

---

## 5. Release Estimate

### Tier 1 — Critical Path (Blocks Release)

| Task | Effort | Priority |
|------|--------|----------|
| ~~Fix Echo/Reverb, Mapbox, FCM token, step indicators~~ | ~~1 day~~ | ✅ DONE |
| Phone/OTP registration & login (both repos) | 3-4 days | P0 |
| Team mode: data model + lobby UI + team leaderboard | 3-4 days | P0 |
| QR code scanning in join flow | 1 day | P0 |
| Social auth deep link token return to mobile app | 1-2 days | P0 |
| Secure token storage (move from session to SecureStorage) | 1 day | P0 |
| **Subtotal** | **~10-12 days** | |

### Tier 2 — Expected for v1 Polish

| Task | Effort | Priority |
|------|--------|----------|
| Splash screen component | 0.5 day | P1 |
| Password change / add password UI | 1 day | P1 |
| Linked accounts display + disconnect logic | 1 day | P1 |
| Offline error handling (retry with backoff, reconnection indicator) | 2 days | P1 |
| Answer shuffling for multiple choice | 0.5 day | P1 |
| GPS accuracy warning UI | 0.5 day | P1 |
| Quest access code flow for private/school quests | 1 day | P1 |
| Rate limiting on auth endpoints (backend) | 0.5 day | P1 |
| **Subtotal** | **~7 days** | |

### Tier 3 — Test Coverage & Hardening

| Task | Effort | Priority |
|------|--------|----------|
| Add missing API test coverage (moderation, team mode, penalties, search) | 3-4 days | P2 |
| Expand Livewire component tests | 2 days | P2 |
| Remove hardcoded Playwright test data, use factories | 1-2 days | P2 |
| Performance testing (nearby quests at scale, large leaderboards) | 1-2 days | P2 |
| **Subtotal** | **~7-10 days** | |

### Total

| Tier | Days | Status |
|------|------|--------|
| Tier 1 (Blockers) | 10-12 | Must do |
| Tier 2 (Polish) | 7 | Should do |
| Tier 3 (Testing) | 7-10 | Should do |
| **Total** | **~24-29 dev days** | |

---

## 6. What's Working Well

- Clean Laravel architecture with proper separation of concerns
- Comprehensive data model (quests, checkpoints, questions, sessions, participants)
- Scoring system is well-thought-out and tested
- Filament admin panel for moderation is functional
- API versioning in place (v1)
- Both `en` and `da` translations appear complete for existing screens
- Factory/seeder infrastructure for development
- Good enum usage — no magic strings
- Broadcasting events defined and ready on backend
- NativePHP integration for geolocation, camera, push notifications

---

## 7. Recommended Task Order

1. **Phone/OTP auth** — unblocks a major user signup path
2. **Team mode** — unblocks competitive team gameplay
3. **QR scanner + social auth deep link** — unblocks key join/signup flows
4. **Splash screen + token storage** — first-launch experience + security
5. **Tier 2 polish items** — linked accounts, password change, offline handling
6. **Test coverage** — harden before release
