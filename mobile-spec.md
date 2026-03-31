# Questify — Mobile App Specification
**Version 1.1 — Repository: `questify-mobile`**
**Read `questify-overview.md` first for product context, user roles, and business rules.**

---

## 1. Stack

| Layer | Technology |
|---|---|
| Framework | NativePHP (Laravel-based native mobile) |
| Maps | Google Maps SDK |
| Geolocation | NativePHP native GPS bridge |
| Auth | Laravel Sanctum — Bearer token stored in secure local storage |
| Real-time | Laravel Reverb client (WebSockets) |
| Localisation | Laravel i18n — English (en) and Danish (da) |

---

## 2. API Communication

The mobile app communicates exclusively with the backend via the REST API documented in `questify-backend-spec.md`. The app never connects to the database directly.

**Base URL:** Configured via environment variable `QUESTIFY_API_URL`

**Authentication:** All authenticated requests include the header:
```
Authorization: Bearer {sanctum_token}
```

**Language header:** All requests include:
```
Accept-Language: {locale}   -- e.g. 'en' or 'da'
```
This tells the backend which language to use for translated response strings.

**Token storage:** Sanctum token is stored in NativePHP's secure encrypted storage — never in plain local storage.

**Social auth:** For Google, Facebook, Apple, and Microsoft login, the app opens the backend's `/auth/{provider}/redirect` URL in an in-app browser. After the OAuth flow, the backend callback returns a Sanctum token that is passed back to the app via a deep link.

---

## 3. Localisation

- Supported locales: English (`en`) and Danish (`da`)
- On first launch, read the device locale. If it matches `da`, set app language to Danish. Otherwise default to English.
- Registered users: `locale` preference stored on their account via `PUT /api/v1/user/profile`
- Guests: locale preference stored in local app storage
- All UI strings use Laravel's `__()` helper with keys from `lang/en/` and `lang/da/` files
- Quest content (titles, descriptions, question text) is displayed as-is — no translation

---

## 4. Navigation Structure

### Unauthenticated / Guest
```
Welcome Screen
├── Join a Quest (guest path)
│   ├── Enter Session Code  OR  Scan QR
│   └── Enter Display Name → Lobby Screen
├── Sign Up
└── Log In
```

### Authenticated (Bottom Tab Bar)
```
┌─────────────────────────────────────────┐
│  🗺 Discover │ 🎒 My Quests │ ➕ Create │ 👤 Profile │
└─────────────────────────────────────────┘
```

Guests who tap My Quests, Create, or Profile are shown a prompt to sign up.

---

## 5. Screen Specifications

---

### 5.1 Splash Screen
- Questify logo centred
- Tagline below logo
- Auto-advances after 2 seconds
- On advance: check for stored Sanctum token → if valid, go to Discover tab; if not, go to Welcome Screen

---

### 5.2 Welcome Screen
- Questify logo (smaller, top area)
- "Join a Quest" button — primary CTA, opens Join Flow
- "Sign Up" button — opens Sign Up screen
- "Log In" link — opens Log In screen

---

### 5.3 Sign Up Screen
- Fields: Name, Email, Password, Confirm Password
- Social sign-up buttons: Google, Facebook, Apple, Microsoft (each triggers OAuth via in-app browser → `/auth/{provider}/redirect`)
- Terms & Privacy Policy checkbox (required)
- "Create Account" submit button
- "Already have an account? Log In" link
- On success: store Sanctum token, navigate to Discover tab

---

### 5.4 Log In Screen
- Fields: Email, Password
- Social login buttons: Google, Facebook, Apple, Microsoft
- "Forgot password?" link → Password Reset Screen
- "Log In" submit button
- "Don't have an account? Sign Up" link
- On success: store token, navigate to Discover tab

---

### 5.5 Password Reset Screen
- Email field
- "Send Reset Link" button
- Calls `POST /api/v1/auth/forgot-password`
- Shows confirmation message after submission

---

### 5.6 Discover Screen — Map View (default)

**API calls:**
- `GET /api/v1/quests?lat={lat}&lng={lng}` — fetch nearby public quests on load and on map region change

**Layout:**
- Full-screen Google Map centred on user's current GPS location
- Each quest shown as a map pin at its starting checkpoint coordinates
- Pin style: differentiated by difficulty (colour-coded: green = easy, orange = medium, red = hard)

**Tap a pin → Bottom Sheet Card:**
- Cover image (thumbnail)
- Title
- Category badge + difficulty badge
- Estimated duration (e.g. "~35 min")
- Checkpoint count (e.g. "6 stops")
- `sessions_count` displayed as "Played X times"
- Star rating (`average_rating`)
- "View Quest" button → Quest Detail Screen

**Header:**
- Search icon → activates Search Bar (filters map + list)
- List/Map toggle button

**Floating button:** "Filter" → opens filter sheet: category, difficulty, max duration, min rating

---

### 5.7 Discover Screen — List View

Same data as map view, displayed as a scrollable list of cards sorted by distance.

**Card content:**
- Cover image
- Title, category badge, difficulty badge
- Distance from user (e.g. "1.2 km away")
- Estimated duration, checkpoint count, rating

Tap card → Quest Detail Screen

---

### 5.8 Quest Detail Screen

**API call:** `GET /api/v1/quests/{id}`

**Layout (scrollable):**
- Full-width hero cover image
- Title (large)
- Creator name ("By {name}")
- Row: category badge, difficulty badge
- Stats row: estimated duration · checkpoint count · sessions played · star rating
- Description text
- Map preview (Google Maps, non-interactive) — shows **starting checkpoint pin only**. Never reveals the full route.
- "Ratings & Reviews" section — list of user ratings with comments
- Sticky bottom bar with CTAs:
    - Public quest, logged in → "Start Solo" + "Start Group Session"
    - Public quest, guest → "Start Solo" (opens join flow inline) + "Start Group Session" (prompts sign up)
    - Private / school quest → "Enter Access Code" text field + "Join" button first; on correct code, CTAs appear

---

### 5.9 Quest Creation Flow

Registered users only. Multi-step form with a step progress indicator at the top (Step 1 of 5, etc.). All steps validated before advancing. Draft saved automatically on each step advance via `PUT /api/v1/quests/{id}`.

**Step 1 — Basics**
- Title (text input, required)
- Description (multiline text, required)
- Cover image (image picker / camera, required)
- Category (picker, required)
- Difficulty (segmented control: Easy / Medium / Hard, required)
- Estimated duration in minutes (number input, required)
- Visibility (segmented control: Public / Private / School)
    - If Private or School: "Access Code" field appears (auto-generate button available)
- "Next" button → Step 2

**Step 2 — Checkpoints**
- Full-screen Google Map (interactive)
- "Add Checkpoint" floating button → tap map to drop a pin
- Each pin is numbered in sequence
- Tapping a placed pin opens a bottom sheet form:
    - Title (required)
    - Description / flavour text (optional)
    - Image (optional)
    - Hint text (optional)
    - Arrival radius override (optional — shows quest default as placeholder)
    - "Save" / "Delete" actions
- Below the map: draggable list of checkpoints for reordering (updates `order_index`)
- Minimum 2 checkpoints required to advance
- "Next" button → Step 3

**Step 3 — Questions**
- List of checkpoints, each expandable
- Per checkpoint: "Add Question" button
- Per question form:
    - Question text (required)
    - Optional image
    - Question type selector: Multiple Choice / True & False / Open Text
    - Answer fields based on type:
        - Multiple choice: 2–4 answer fields, radio to mark one as correct
        - True & False: two fixed options, toggle correct answer
        - Open text: one "accepted answer" field
- At least 1 question per checkpoint required to advance
- "Next" button → Step 4

**Step 4 — Game Rules**
- Wrong answer behaviour (radio group):
    - Retry Free
    - Retry with Penalty → reveals "Penalty points" number input
    - Timed Lockout → reveals "Lockout seconds" number input
    - 3 Strikes then Hint
- Scoring section:
    - "Points per correct answer" (number input, default 100)
    - Speed bonus toggle
    - Wrong attempt penalty deduction toggle
    - Quest completion time bonus toggle
- "Next" button → Step 5

**Step 5 — Review & Publish**
- Summary cards for each step
- Tap any card to jump back and edit
- "Save as Draft" button → `POST /api/v1/quests/{id}/publish` with status=draft
- "Submit for Review" (public) / "Publish" (private/school) → `POST /api/v1/quests/{id}/publish`
- On publish: success screen with share button (shareable quest link)

---

### 5.10 Start Solo Flow

Triggered by "Start Solo" on Quest Detail Screen.

1. Call `POST /api/v1/sessions` with `{ quest_id, play_mode: "solo" }`
2. Call `POST /api/v1/sessions/{code}/join` with `{ display_name: user.name, user_id: user.id }`
3. Call `POST /api/v1/sessions/{code}/start`
4. Navigate directly to the Navigation Screen for Checkpoint 1

No lobby screen for solo play.

---

### 5.11 Start Group Session Flow

Triggered by "Start Group Session" on Quest Detail Screen.

1. Play mode picker sheet: Solo / Competitive – Individual / Competitive – Teams
2. Call `POST /api/v1/sessions` with `{ quest_id, play_mode }`
3. Navigate to **Lobby Screen**

---

### 5.12 Lobby Screen

**API calls:**
- `POST /api/v1/sessions/{code}/start` — when host taps Start
- WebSocket: subscribe to `session.{code}` channel, listen for `ParticipantJoined` and `SessionStarted` events

**Layout:**
- Quest title and cover image (top)
- Large session code (e.g. "XK92PL") — tap to copy
- QR code (generated from the invite URL)
- "Share Link" button → native share sheet with invite URL
- Play mode badge (e.g. "Competitive – Teams")
- "Participants" section — live list, updates in real time via `ParticipantJoined` event
    - If `competitive_teams` mode: drag participants into team columns; host assigns team names
- "Start Quest" button (host only, enabled when ≥ 1 participant has joined)

**For participants (non-host):**
- Same screen but without "Start Quest"
- Shows "Waiting for host to start…" message
- Listens for `SessionStarted` event → auto-navigates to Navigation Screen on receipt

---

### 5.13 Join Flow (Guest or Registered User)

**Entry points:** Welcome Screen "Join a Quest", deep link, QR scan

1. "Enter Code" text input (6 characters, uppercase) OR QR scanner
2. Call `GET /api/v1/sessions/{code}` — shows quest title and cover image as preview
3. "Your name" text input (display name)
4. "Join Quest" button → `POST /api/v1/sessions/{code}/join`
5. Navigate to Lobby Screen (participant view)

---

### 5.14 Navigation Screen

Shown between checkpoints. The player navigates to the current checkpoint.

**API calls:**
- `POST /api/v1/sessions/{code}/arrived` — sent automatically when GPS triggers arrival
- WebSocket: listen on `session.{code}` for `LeaderboardUpdated`

**Layout:**
- Full-screen Google Map
- Blue pulsing dot = player's current GPS location (live)
- Destination marker = current checkpoint location
- Distance label (e.g. "~180m away") — updates as player moves
- Estimated walking time
- Checkpoint name (e.g. "Stop 2: Nørreport Station")
- Hint button (shown if hint exists):
    - `retry_free` / `retry_penalty` / `lockout` modes: tap to reveal hint immediately
    - `three_strikes_hint` mode: button shows "Hint locked" until 3 wrong attempts; after 3 the backend returns the hint text in the answer response
- Competitive modes only: collapsible leaderboard strip at the top of the screen (updated via `LeaderboardUpdated` WebSocket event)

**Arrival detection:**
- The app continuously monitors device GPS
- When distance to checkpoint coordinates falls within `checkpoint_arrival_radius_meters` (or the checkpoint's override), trigger arrival automatically — no button required
- Send `POST /api/v1/sessions/{code}/arrived` with `{ participant_id, checkpoint_id, latitude, longitude }`
- On success response: slide in the Question Screen

---

### 5.15 Question Screen

Shown after automatic arrival at a checkpoint.

**API calls:**
- `POST /api/v1/sessions/{code}/answer` — on each answer submission

**Layout:**
- Checkpoint title + optional image shown briefly with arrival animation ("You've arrived at [title]!")
- Questions shown one at a time in `order_index` sequence
- Per question:
    - Question text (prominent)
    - Optional question image
    - Answer UI based on `question_type`:
        - `multiple_choice`: large tappable option cards (2–4 options), shuffled on display
        - `true_false`: two large buttons ("True" / "False")
        - `open_text`: text input field + "Submit" button
- Tapping an option or submitting text calls `POST /api/v1/sessions/{code}/answer`

**On correct answer response:**
- Green success animation
- Points earned shown briefly (e.g. "+115 pts")
- If `next === "question"`: next question slides in
- If `next === "checkpoint_complete"` and more checkpoints remain: "Next Checkpoint →" button appears → navigates to Navigation Screen
- If `next === "checkpoint_complete"` and this was the last checkpoint: navigate to Quest Complete Screen

**On incorrect answer response:**
- Red shake animation
- Behaviour based on `behaviour` field in response:
    - `retry_free`: "Try again" — question stays active
    - `retry_penalty`: "Incorrect — −{points} pts. Try again."
    - `lockout`: "Locked. Try again in {X} seconds." — countdown timer shown, input disabled
    - `three_strikes_hint` (when `hint` field present in response): hint text revealed below the question; "Try again" remains active

---

### 5.16 Quest Complete Screen

**API calls:** None — all data comes from the final answer response and leaderboard

**Layout:**
- Celebration animation (confetti or similar)
- "Quest Complete!" heading
- Player's total score (large)
- Time taken (e.g. "Completed in 38 minutes")
- Full session leaderboard (all participants, sorted by score)
    - In teams mode: team scores first, then individual breakdown
- "Rate this Quest" section (registered users only):
    - 1–5 star picker
    - Optional comment text field
    - "Submit Rating" button → `POST /api/v1/quests/{id}/rate`
- "Share Result" button → native share sheet
- "Back to Discover" button

---

### 5.17 Live Session Dashboard (host only)

Accessible during an active group session via a floating "Dashboard" button visible only to the host.

**API call:** `GET /api/v1/sessions/{code}/dashboard` — initial load
**WebSocket:** subscribe to `session.{code}.host` for all host events

**Layout:**
- "Quest in Progress" header with session code
- Participant list (or team groups in competitive_teams mode):
    - Display name
    - Current checkpoint (e.g. "3 of 6")
    - Current score
    - Status badge: Navigating / Answering / Finished / DNF
- Team mode: participants grouped by team with team total scores
- Updates in real time via WebSocket events (`CheckpointArrived`, `CheckpointCompleted`, `QuestCompleted`)
- "End Session" button → confirmation dialog → `POST /api/v1/sessions/{code}/end`
    - All incomplete participants marked DNF
    - `SessionEnded` event broadcast → all participant devices navigate to Quest Complete Screen

---

### 5.18 My Quests Screen

**Played tab**
- API: `GET /api/v1/user/sessions`
- Scrollable list of past sessions
- Per card: quest cover image, quest title, date played, finishing position (e.g. "2nd of 8"), total score

**Created tab**
- API: `GET /api/v1/user/quests`
- Scrollable list of created quests
- Per card: cover image, title, status badge (Draft / Pending Review / Published / Archived), sessions played count, average rating
- Actions per card:
    - Edit (drafts only) → Quest Creation Flow pre-filled
    - Start Session → play mode picker → Lobby Screen
    - Archive → confirmation → `DELETE /api/v1/quests/{id}`
    - View → Quest Detail Screen

---

### 5.19 Profile Screen

**API calls:**
- `GET /api/v1/auth/me` — load profile data
- `PUT /api/v1/user/profile` — save changes
- `DELETE /api/v1/auth/social/{provider}` — unlink social account
- `DELETE /api/v1/user` — delete account

**Layout:**
- Avatar (tappable to change — image picker)
- Name (editable inline)
- Email (display only)
- Stats row: Quests Played · Quests Created · Total Points · Best Score

**Settings sections:**
- **Language** — segmented control: English / Danish. On change: update `locale` via `PUT /api/v1/user/profile`, re-render app in new locale immediately
- **Linked Accounts** — shows connected social providers (Google, Facebook, Apple, Microsoft). "Connect" / "Disconnect" per provider. Disconnect calls `DELETE /api/v1/auth/social/{provider}`. Block disconnect if it would leave the account with no login method (no password + last provider)
- **Password** — "Change Password" for email accounts; "Add Password" for social-only accounts
- **Notifications** — toggle preferences (stored locally, used for FCM opt-in/out in v2)
- **Log Out** — clears stored Sanctum token, navigates to Welcome Screen
- **Delete Account** — confirmation dialog with warning text → `DELETE /api/v1/user` → clears token, navigates to Welcome Screen

---

## 6. GPS & Geolocation

- Request `always` location permission on first launch (required for background arrival detection while navigating)
- Use NativePHP's native geolocation bridge for continuous position updates during an active quest
- Geolocation is only active during an active session — stop monitoring when session ends or app backgrounds without an active session
- Arrival check algorithm:
  ```
  distance = haversine(player.lat, player.lng, checkpoint.lat, checkpoint.lng)
  radius   = checkpoint.arrival_radius_override ?? quest.checkpoint_arrival_radius_meters
  if distance <= radius → trigger arrival
  ```
- Debounce arrival trigger: once triggered for a checkpoint, do not re-trigger for the same checkpoint
- Show a "GPS signal weak" warning if accuracy > 50m

---

## 7. WebSocket Connection (Laravel Reverb)

- Connect to Reverb on session join (lobby screen)
- Disconnect on quest complete, session end, or app backgrounding beyond a configurable timeout
- Reconnect automatically on network restore during an active session
- Channels:
    - `session.{code}` — all participants subscribe (presence channel)
    - `session.{code}.host` — host only subscribes (private channel)
- Events and their UI effects: see `questify-overview.md` section 5 and backend spec section 10

---

## 8. Offline & Error Handling

- If API call fails during active quest, show a non-blocking toast and retry automatically up to 3 times with exponential backoff
- If arrival POST fails, retry silently — do not block the question screen from appearing
- If answer POST fails, keep the answer UI in a "submitting" state and retry — do not allow a second submission
- If WebSocket disconnects during an active session, show a subtle reconnecting indicator and attempt reconnect every 5 seconds
- No offline mode — if connectivity is fully lost, show a full-screen "No connection" overlay with a retry button

---

## 9. Color Palette

### Primary
| Name         | Hex       | Usage                       |
|--------------|-----------|-----------------------------|
| Forest       | `#0B3D2E` | Primary brand, nav, cards   |
| Forest mid   | `#165C45` | Hover, variants             |
| Forest light | `#1E7A58` | Links, accents              |

### Accent
| Name         | Hex       | Usage                       |
|--------------|-----------|-----------------------------|
| Amber        | `#F5A623` | CTAs, pins, rewards         |
| Amber dark   | `#C8811A` | Text on amber background    |
| Amber light  | `#FDE8BA` | Badges, highlights          |

### Semantic
| Name         | Hex       | Usage                       |
|--------------|-----------|-----------------------------|
| Coral        | `#E85C3A` | Live, urgent, hard          |
| Coral light  | `#FCDDD7` | Error badges                |
| Success      | `#D4EDE4` | Completed, done             |

### Neutrals
| Name         | Hex       | Usage                       |
|--------------|-----------|-----------------------------|
| Bark         | `#2C1810` | Headings, bold text         |
| Cream        | `#FAF5EB` | App background              |
| Cream dark   | `#F0E8D6` | Cards, surfaces             |
| Border       | `#E5DDD0` | Dividers, outlines          |
| Muted        | `#7A7470` | Secondary text              |
