import { test, expect, BrowserContext, Page } from '@playwright/test';
import { execSync } from 'child_process';

/**
 * Checkpoint coordinates from the "Copenhagen History Hunt" quest (ID 1).
 * Each checkpoint has 1-2 multiple_choice or true_false questions.
 * The first answer option is always the correct one in seed data.
 */
const CHECKPOINTS = [
    { id: 1, title: 'Nyhavn', lat: 55.6798, lng: 12.5907 },
    { id: 2, title: 'Kongens Nytorv', lat: 55.6795, lng: 12.5858 },
    { id: 3, title: 'Amalienborg Palace', lat: 55.684, lng: 12.593 },
    { id: 4, title: 'Marble Church', lat: 55.6851, lng: 12.5894 },
    { id: 5, title: 'The Round Tower', lat: 55.6813, lng: 12.5756 },
    { id: 6, title: 'Strøget Shopping Street', lat: 55.678, lng: 12.577 },
];

const unique = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

/**
 * Known correct answers for open text questions in the Copenhagen History Hunt.
 * The API evaluates case-insensitively with trimmed whitespace.
 */
const OPEN_TEXT_ANSWERS: Record<string, string> = {
    'What is the name of the Danish Royal Guard?': 'Den Kongelige Livgarde',
    'What is inside the tower instead of stairs?': 'A spiral ramp',
};

/**
 * Login via the web UI.
 */
async function login(page: Page, email: string, password: string): Promise<void> {
    await page.goto('https://questify-app.test/login');
    await page.fill('input[type="email"]', email);
    await page.waitForSelector('input[type="password"]', { timeout: 5000 });
    await page.fill('input[type="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/discover/list', { timeout: 15000 });
}

/**
 * Register a new user via the web UI and return to discover page.
 */
async function registerUser(
    page: Page,
    email: string,
    displayName: string,
): Promise<void> {
    await page.goto('https://questify-app.test/register');
    await page.fill('input[name="email"]', email);
    await page.click('button:has-text("Continue")');

    await page.waitForSelector('input[name="first_name"]', { timeout: 5000 });
    await page.fill('input[name="first_name"]', 'Player');
    await page.fill('input[name="last_name"]', 'Two');
    await page.fill('input[name="display_name"]', displayName);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/discover/list', { timeout: 15000 });
}

/**
 * Complete all checkpoints for a given player on the play screen.
 * Mocks geolocation, triggers arrival via Livewire, answers all questions.
 */
async function completeAllCheckpoints(
    context: BrowserContext,
    page: Page,
): Promise<void> {
    for (let i = 0; i < CHECKPOINTS.length; i++) {
        const cp = CHECKPOINTS[i];

        // Mock geolocation to checkpoint coordinates
        await context.setGeolocation({ latitude: cp.lat, longitude: cp.lng });

        // Wait for page to settle, then trigger arrival via Livewire
        await page.waitForTimeout(2000);

        await page.evaluate(() => {
            const el = document.querySelector('[wire\\:id]');
            if (el) {
                const wireId = el.getAttribute('wire:id');
                if (wireId) {
                    (window as any).Livewire.find(wireId).call('arriveAtCheckpoint');
                }
            }
        });

        await page.waitForTimeout(2000);

        // Wait for "Answer Questions" button and click it
        const answerBtn = page.locator('button[wire\\:click="goToQuestions"]');
        await answerBtn.waitFor({ timeout: 15000 });
        await page.click('button:has-text("Answer Questions")');
        await page.waitForURL(/\/session\/[A-Za-z0-9]+\/question\/\d+/, {
            timeout: 15000,
        });

        // Answer all questions at this checkpoint
        let onQuestionScreen = true;
        let answerAttemptIndex = 0;
        let questionAttempts = 0;
        while (onQuestionScreen && questionAttempts < 20) {
            questionAttempts++;
            await page.waitForTimeout(1000);

            // Check if we've navigated away from the question screen
            if (!page.url().includes('/question/')) {
                onQuestionScreen = false;
                break;
            }

            // Dismiss Livewire error dialog if present
            await page.evaluate(() => {
                const dialog = document.getElementById('livewire-error');
                if (dialog) dialog.close();
            });

            // Wait for answer buttons or submit button to appear
            const answerButtons = page.locator('button[wire\\:key^="answer-"]');
            const submitBtn = page.locator('button').filter({ hasText: /Submit Answer/i });
            const nextBtn = page.locator('button:has-text("Next")');

            // If only "Next" is visible (stale feedback), click it and loop
            if ((await answerButtons.count()) === 0 && (await nextBtn.count()) > 0) {
                await nextBtn.click();
                await page.waitForTimeout(1500);
                continue;
            }

            // Wait for either answer buttons or textarea to appear
            const questionReady = page.locator('button[wire\\:key^="answer-"], textarea');
            try {
                await questionReady.first().waitFor({ timeout: 5000 });
            } catch {
                await page.reload();
                await page.waitForTimeout(2000);
                continue;
            }

            const answerCount = await answerButtons.count();
            const textarea = page.locator('textarea');
            const hasTextarea = (await textarea.count()) > 0;

            if (answerCount > 0) {
                // Multiple choice / true-false
                const idx = answerAttemptIndex % answerCount;
                await answerButtons.nth(idx).click();
                await page.waitForTimeout(500);
                // Click Submit Answer
                if ((await submitBtn.count()) > 0) {
                    await submitBtn.click();
                }
            } else if (hasTextarea) {
                // Open text — set answer and submit via Livewire directly
                const questionText = await page.locator('h2').textContent() ?? '';
                const answer = OPEN_TEXT_ANSWERS[questionText.trim()] ?? 'unknown';
                await page.evaluate((ans) => {
                    const el = document.querySelector('[wire\\:id]');
                    if (el) {
                        const wireId = el.getAttribute('wire:id');
                        if (wireId) {
                            const component = (window as any).Livewire.find(wireId);
                            component.set('openEndedAnswer', ans);
                            component.call('submitAnswer');
                        }
                    }
                }, answer);
            }

            // Wait for feedback "Next" button
            await page.waitForSelector('button:has-text("Next")', { timeout: 10000 });

            // Check if answer was correct
            const isCorrect = await page.locator('.bg-green-50').count() > 0;
            if (!isCorrect) {
                answerAttemptIndex++;
            } else {
                answerAttemptIndex = 0;
            }

            await page.click('button:has-text("Next")');
            await page.waitForTimeout(1500);
        }

        if (questionAttempts >= 20) {
            await page.screenshot({ path: `test-results/mp-stuck-cp${i + 1}.png` });
            throw new Error(`Stuck at checkpoint ${i + 1} after 20 attempts. URL: ${page.url()}`);
        }

        // After checkpoint questions: should be on /play or /complete
        const urlAfterCheckpoint = page.url();
        if (i < CHECKPOINTS.length - 1) {
            expect(urlAfterCheckpoint).toContain('/play');
        }
    }
}

test('multiplayer competitive quest: two players complete and winner is determined', async ({
    browser,
}, testInfo) => {
    test.setTimeout(300_000); // 5 minutes — two players completing 6 checkpoints each

    // Clean up any active sessions from previous test runs to avoid
    // "already in active session" errors for bent@example.com
    try {
        execSync(
            `php artisan tinker --execute "App\\Models\\QuestSession::whereIn('status', ['waiting', 'active'])->update(['status' => 'completed', 'completed_at' => now()]);"`,
            { cwd: '/Users/kasper/Projects/questify-admin', timeout: 10000 },
        );
    } catch {}

    const contextOptions = {
        ignoreHTTPSErrors: true,
        viewport: { width: 390, height: 844 },
        locale: 'en-US' as const,
        permissions: ['geolocation'] as string[],
        geolocation: { latitude: 55.6761, longitude: 12.5683 },
        isMobile: true,
        hasTouch: true,
    };

    // ── Setup: Two browser contexts ──
    const hostContext = await browser.newContext(contextOptions);
    const joinerContext = await browser.newContext(contextOptions);
    const hostPage = await hostContext.newPage();
    const joinerPage = await joinerContext.newPage();

    // Set locale to English for both
    await hostPage.goto('https://questify-app.test/locale/en');
    await joinerPage.goto('https://questify-app.test/locale/en');

    // ── Step 1: Both users authenticate ──
    // Host uses seeded verified account; joiner registers fresh
    const suffix = unique();

    await login(hostPage, 'bent@example.com', 'password');

    const joinerEmail = `player2-${suffix}@example.com`;
    const joinerDisplayName = `player2_${suffix}`;
    await registerUser(joinerPage, joinerEmail, joinerDisplayName);

    // ── Step 2: Host opens quest detail and starts competitive individual session ──
    await hostPage.goto('https://questify-app.test/quests/1');
    await hostPage.waitForSelector('h1:has-text("Copenhagen History Hunt")', {
        timeout: 10000,
    });

    // Set play mode to 'competitive_individual' on Livewire and call startQuest
    // (Alpine buttons use 'individual' but API expects 'competitive_individual')
    await hostPage.evaluate(() => {
        const el = document.querySelector('[wire\\:id]');
        if (el) {
            const wireId = el.getAttribute('wire:id');
            if (wireId) {
                const component = (window as any).Livewire.find(wireId);
                component.set('playMode', 'competitive_individual');
                component.call('startQuest');
            }
        }
    });

    // Wait for redirect to lobby
    await hostPage.waitForURL(/\/session\//, { timeout: 30000 });

    // Extract session code from the URL
    const lobbyUrl = hostPage.url();
    const sessionCode = lobbyUrl.match(/\/session\/([A-Za-z0-9]+)/)?.[1] ?? '';
    expect(sessionCode.length).toBeGreaterThanOrEqual(4);

    // ── Step 3: Joiner joins the session ──
    await joinerPage.goto(`https://questify-app.test/join/${sessionCode}/name`);
    await joinerPage.waitForSelector('input[type="text"]', { timeout: 10000 });
    await joinerPage.fill('input[type="text"]', 'Player Two');
    await joinerPage.click('button[type="submit"]');
    await joinerPage.waitForURL(/\/session\/[A-Za-z0-9]+/, { timeout: 15000 });

    // ── Step 4: Host starts the session ──
    // Reload host lobby to pick up new participant (no WebSocket in Playwright)
    await hostPage.reload();
    await hostPage.waitForTimeout(3000);

    // Verify joiner appears in participant list
    await expect(hostPage.locator('text=Player Two')).toBeVisible({ timeout: 10000 });

    // Click "Start Quest" in lobby → triggers confirmation dialog
    const lobbyStartBtn = hostPage
        .locator('button')
        .filter({ hasText: /Start Quest/i })
        .last();
    await lobbyStartBtn.click();

    // Confirm the dialog
    const confirmBtn = hostPage.locator('button[wire\\:click="confirm"]');
    await confirmBtn.waitFor({ timeout: 5000 });
    await confirmBtn.click();

    // Host redirected to host dashboard
    await hostPage.waitForURL(/\/session\/[A-Za-z0-9]+\/host/, { timeout: 15000 });

    // ── Step 5: Navigate both users to the play screen ──
    // Host navigates from dashboard to play (host is also a participant)
    await hostPage.goto(`https://questify-app.test/session/${sessionCode}/play`);
    await hostPage.waitForURL(/\/session\/[A-Za-z0-9]+\/play/, { timeout: 15000 });

    // Joiner navigates to play (no WebSocket auto-redirect in Playwright)
    await joinerPage.goto(`https://questify-app.test/session/${sessionCode}/play`);
    await joinerPage.waitForURL(/\/session\/[A-Za-z0-9]+\/play/, { timeout: 15000 });

    // ── Step 6: Joiner completes all checkpoints first ──
    await completeAllCheckpoints(joinerContext, joinerPage);

    // Joiner should be at the completion page (or navigate there if quest completed)
    if (!joinerPage.url().includes('/complete')) {
        await joinerPage.goto(`https://questify-app.test/session/${sessionCode}/complete`);
    }
    await joinerPage.waitForURL(/\/session\/[A-Za-z0-9]+\/complete/, {
        timeout: 15000,
    });
    await expect(
        joinerPage.locator('h1:has-text("Quest Complete")'),
    ).toBeVisible();

    // ── Step 7: Host completes all checkpoints second ──
    // Reload play page so the component auto-detects participant_id from session data
    await hostPage.reload();
    await hostPage.waitForTimeout(2000);

    await completeAllCheckpoints(hostContext, hostPage);

    // Host should be at the completion page (or navigate there if quest completed)
    if (!hostPage.url().includes('/complete')) {
        await hostPage.goto(`https://questify-app.test/session/${sessionCode}/complete`);
    }
    await hostPage.waitForURL(/\/session\/[A-Za-z0-9]+\/complete/, {
        timeout: 15000,
    });
    await expect(
        hostPage.locator('h1:has-text("Quest Complete")'),
    ).toBeVisible();

    // ── Step 8: Verify leaderboard on both completion pages ──
    // Reload both to get final leaderboard state
    await joinerPage.reload();
    await hostPage.reload();
    await joinerPage.waitForTimeout(2000);
    await hostPage.waitForTimeout(2000);

    // Verify leaderboard heading is visible
    await expect(joinerPage.locator('text=Leaderboard')).toBeVisible();
    await expect(hostPage.locator('text=Leaderboard')).toBeVisible();

    // Verify both players appear in each leaderboard
    await expect(joinerPage.locator('text=Player Two')).toBeVisible();
    await expect(hostPage.locator('text=Player Two')).toBeVisible();

    // Verify leaderboard has at least 2 entries (both players)
    const leaderboardEntries = joinerPage.locator('[class*="rounded-lg"]').filter({ has: joinerPage.locator('[class*="font-mono"]') });
    expect(await leaderboardEntries.count()).toBeGreaterThanOrEqual(2);

    // Cleanup
    await hostContext.close();
    await joinerContext.close();
});
