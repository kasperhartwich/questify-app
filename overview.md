# Questify — Shared Overview

## 1. What is Questify?

Questify is a location-based knowledge adventure app. Players navigate to real-world checkpoints using GPS, answer questions when they arrive, and unlock the next location — a knowledge treasure hunt combining geocaching-style navigation with Kahoot-style quizzes.

**Core loop:**
1. A registered user creates a quest: drops checkpoint pins on a map, writes questions per checkpoint, configures scoring and wrong-answer rules
2. Any registered user can open a published quest and start a session — choosing at that point whether to play solo, competitively as individuals, or as teams
3. The host shares a QR code or invite link; others join with just a display name — no account required
4. Everyone navigates to checkpoints via Google Maps-style directions, answers questions on arrival, earns points
5. The host watches a live dashboard showing all participants in real time
6. Quest ends → leaderboard shown to all participants

---

## 2. Two Repositories

| Repo | Stack | Spec file |
|---|---|---|
| `questify-backend` | Laravel 12, Filament 5, MySQL, Reverb | `questify-backend-spec.md` |
| `questify-mobile` | NativePHP (Laravel-based native mobile) | `questify-mobile-spec.md` |

The backend exposes a REST API consumed by the mobile app. The mobile app never connects directly to the database. All business logic, scoring, and moderation lives in the backend.

---

## 3. Tech Stack Summary

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP) |
| Admin Panel | Filament 5 |
| Mobile App | NativePHP |
| Database | MySQL 8 |
| Maps | Google Maps SDK (mobile) + Maps JavaScript API (web) |
| Geolocation | Device GPS via NativePHP native bridge |
| Real-time | Laravel Reverb (WebSockets) |
| Auth | Laravel Sanctum (token-based) |
| Social Auth | Laravel Socialite — Google, Facebook, Apple, Microsoft |
| File Storage | Laravel Storage (S3-compatible) |
| Push Notifications | Laravel Notifications + Firebase FCM |
| Queue | Laravel Horizon (Redis) |
| API Docs | knuckleswtf/scribe |
| Localisation | Laravel i18n — English (en) and Danish (da) |

---

## 4. User Roles

There are only two user types. "Quest Master" is a UI label only — it refers to the registered user currently hosting an active session.

### Guest
- No account required
- Joins any session via QR code, invite link, or 6-character session code
- Enters a display name to participate
- Can browse the public discovery map
- Cannot create quests or host sessions
- No history saved after the session ends

### Registered User
- Email + password, or social login via Google, Facebook, Apple, or Microsoft
- Can create quests (unlimited)
- Can start sessions and share QR codes / invite links
- Has personal history: quests played, quests created, scores
- When hosting a group session, referred to in-app as the **Quest Master**

> Children do not need accounts. A teacher or parent creates the session and shares the QR code. Children join as guests.

---

## 5. Core Concepts

### Quest
A quest is a sequence of real-world checkpoints created by a registered user. Each checkpoint has GPS coordinates, optional descriptive content, and one or more questions the player must answer correctly before proceeding. Quests can be public (moderated), private (access code only), or school (access code only).

### Session
A session is a single play-through of a quest. It has a play mode (solo, competitive individual, or competitive teams), a host (registered user), and one or more participants (who can be guests). The same quest can be played in different modes across different sessions. `play_count` for a quest is always derived as a count of its related sessions — never stored as a column.

### Checkpoint
One stop in the quest route. The player must physically travel to the GPS location. Arrival is detected automatically when the device enters the checkpoint's radius. Once arrived, questions unlock.

### Play Modes
All quests support all three modes. The host chooses at session creation:
- **Solo** — player completes the quest independently, no competition
- **Competitive – Individual** — all players race through the same quest, individual leaderboard
- **Competitive – Teams** — participants are grouped into teams, team leaderboard

### Scoring
Configured per quest by the creator. All options are toggleable and combinable:
- Base points per correct answer
- Speed bonus (faster answer = more points, within a 30-second window)
- Wrong attempt penalty deduction
- Quest completion time bonus

Wrong answer behaviour is also configured per quest: retry free, retry with penalty, timed lockout, or 3-strikes-then-hint.

### Localisation
The app supports English (en) and Danish (da). Device locale is detected on first launch; user can override in profile settings. UI strings are stored in Laravel `lang/` files. Quest content is not translated — creators author in their own language.

---

## 6. Key Business Rules

1. Any registered user can create unlimited quests.
2. Public quests require admin moderation before appearing on the discovery map.
3. Private and school quests skip moderation — never shown on the public map, accessible only via code or direct link.
4. No account is needed to join a session — only a display name.
5. An account is required to create a quest or host a session.
6. Play mode is chosen by the host at session creation, not stored on the quest.
7. The full checkpoint route is never exposed to the client — only the current checkpoint's coordinates are sent.
8. GPS arrival detection is handled client-side. The server records and trusts the client's arrival event.
9. All scores are calculated server-side on answer submission.
10. Open text answers are matched case-insensitively with whitespace trimmed.
11. A player cannot proceed to the next checkpoint until all questions at the current checkpoint are answered correctly.
12. A player can only be in one active session at a time.
13. `play_count` is always accessed via `withCount('sessions')` on the Quest model — never a stored column.
14. Apple Sign In may not return an email — the `email` column on `users` is nullable.
15. A user with only a social account can add a password from Profile → Settings.

---

## 7. Out of Scope for v1

- Branded / partner quests (Marvel, Lego, etc.)
- In-app payments or subscriptions — fully free at launch
- Offline mode — active internet connection required
- Anti-cheat or GPS spoofing detection
- AR camera navigation
- Additional languages beyond English and Danish
- Quest content translation
- Push notifications for session invites (link and QR only in v1)
