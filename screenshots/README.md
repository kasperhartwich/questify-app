# App screenshots

Every screen of the app, captured at 402x874 (iPhone 17 Pro point size, 1x) from
the local backend with test data. Plain screenshots, no device frame. They are
not App Store size (1320x2868); retake at that size for the store.

| File | Screen | How to reach it |
|---|---|---|
| 01-welcome | Welcome | Signed out, open `/` |
| 02-login | Log in | `/login` |
| 03-login-filled | Log in, email typed (password field and button appear) | Type an email on `/login` |
| 04-register | Sign up | `/register` |
| 05-forgot-email | Forgot password, email | `/forgot-password` |
| 06-forgot-code | Reset password, 6-digit code | Send the email on the forgot screen |
| 07-forgot-new-password | Choose a new password | Enter the right code |
| 08-join-code | Join quest, code | `/join` |
| 09-join-code-filled | Join quest, code typed | Type a session code |
| 10-join-name | Display name | Continue with a valid code |
| 11-discover-list | Discover, nearby list | Signed in, `/discover/list`, location allowed |
| 12-discover-filters | Discover with the filter row open | Filter button on Discover |
| 13-discover-map | Discover, full map | Map on Discover, `/discover/map` |
| 14-quest-detail | Quest, overview | A quest from Discover |
| 15-quest-detail-checkpoints | Quest, checkpoints tab | Checkpoints tab |
| 16-quest-detail-leaderboard | Quest, leaderboard tab | Leaderboard tab |
| 17-my-quests-favourites-empty | My Quests, no favourites | `/my-quests` |
| 18-my-quests-favourites | My Quests, one favourite | Bookmark a quest first |
| 19-my-quests-created-empty | My Quests, nothing created | Created tab |
| 20-my-quests-history-empty | My Quests, no history | History tab |
| 21-create-1-info | Create, step 1 quest info | `/create` |
| 22-create-2-checkpoints | Create, step 2 checkpoints | Two taps on the map |
| 23-create-3-questions | Create, step 3 questions | Name both checkpoints |
| 24-create-3-add-question | Create, step 3 with a question form | "Add question" |
| 25-create-4-settings | Create, step 4 settings | After the questions |
| 26-create-5-details | Create, step 5 details | Next: Details |
| 27-create-6-review | Create, step 6 review and publish | Pick category and difficulty, Next: Review |
| 28-lobby | Session lobby | Join a session |
| 29-play-far-from-checkpoint | Play, checkpoint too far ("1275 m away") | Active session, player 1275 m away |
| 30-play-in-range | Play, in range (Answer Questions button, arrival circle) | Player at the checkpoint |
| 31-question-multiple-choice | Question, multiple choice | Answer Questions |
| 32-question-selected | Question, answer picked | Tap an answer |
| 33-question-correct | Correct answer | Submit the right answer |
| 34-question-true-false | Question, true or false | Next question |
| 35-question-wrong | Wrong answer (Try again) | Submit a wrong answer |
| 36-question-last-correct | Last question answered | Try again, then the right answer |
| 37-play-next-checkpoint | Play, next checkpoint | Next after the last question |
| 38-quest-complete | Quest complete, score, rating | `/session/{code}/complete` |
| 39-host-dashboard | Host dashboard | `/session/{code}/host` as the host |
| 40-profile | Profile | `/profile` |
| 41-profile-settings | Profile settings | Cog on Profile |

The centre button of the bottom bar reads "Join quest" on Discover and Profile
(11, 12, 40) and "Quest" on the other screens.

Not captured: the update sheet (the local backend has no `/app-update` route
yet), the quest-complete state with a rating given, and the social login
buttons (switched off locally).

## How to regenerate

1. Run the local backend (`questify-admin.test` through Herd) and serve this
   app: in `.env` set `QUESTIFY_API_URL=http://questify-admin.test`, comment out
   `URL::forceHttps()` in `AppServiceProvider`, then
   `php artisan serve --port=8000`. Revert both afterwards.
2. Create a local user with a verified email and a random password (never use
   production accounts or the demo password), and delete it when done.
3. Open the app with `playwright-cli`, viewport 402x874, location allowed and
   set with `setGeolocation` (Copenhagen, 55.6761, 12.5683 for Discover; the
   first checkpoint of quest 1 is 55.6798, 12.5907).
4. For the play screens, create a `quest_sessions` row for quest 1 (hosted by
   the test user), join it through `/join`, set it `active`, and move the
   geolocation near and far from the checkpoint. For the complete and host
   screens set the participant's `quest_completed_at` and the session to
   `completed`.
5. Save each screen with `page.screenshot({path})`.
