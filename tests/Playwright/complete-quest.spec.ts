import { test, expect } from '@playwright/test';

/**
 * Checkpoint coordinates from the "Copenhagen History Hunt" quest (ID 1).
 * Each checkpoint has 1-2 multiple_choice or true_false questions.
 * The first answer option is always the correct one.
 */
const CHECKPOINTS = [
    { id: 1, title: 'Nyhavn', lat: 55.6798, lng: 12.5907 },
    { id: 2, title: 'Kongens Nytorv', lat: 55.6795, lng: 12.5858 },
    { id: 3, title: 'Amalienborg Palace', lat: 55.684, lng: 12.593 },
    { id: 4, title: 'Marble Church', lat: 55.6851, lng: 12.5894 },
    { id: 5, title: 'The Round Tower', lat: 55.6813, lng: 12.5756 },
    { id: 6, title: 'Strøget Shopping Street', lat: 55.678, lng: 12.577 },
];

test('complete the Copenhagen History Hunt quest end-to-end', async ({
    page,
    context,
}) => {
    // ── Step 0: Set locale to English ──
    await page.goto('/locale/en');

    // ── Step 1: Login ──
    await page.goto('/login');
    await page.fill('input[type="email"]', 'bent@example.com');
    // Password field appears after email is filled (wire:model.live)
    await page.waitForSelector('input[type="password"]', { timeout: 5000 });
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/discover/list', { timeout: 15000 });

    // ── Step 2: Open quest detail ──
    await page.goto('/quests/1');
    await page.waitForSelector('h1:has-text("Copenhagen History Hunt")', {
        timeout: 10000,
    });

    // ── Step 3: Start solo quest ──
    // The default play mode is "solo", click the first Start Quest button
    const startBtn = page.locator('button').filter({ hasText: /Start Quest/i }).first();
    await startBtn.click();

    // Wait for redirect to the play screen /session/{code}/play
    await page.waitForURL(/\/session\/[A-Za-z0-9]+\/play/, { timeout: 30000 });
    const playUrl = page.url();
    const sessionCode = playUrl.match(/\/session\/([A-Za-z0-9]+)\/play/)?.[1];
    expect(sessionCode).toBeTruthy();

    // ── Step 4: Complete each checkpoint ──
    for (let i = 0; i < CHECKPOINTS.length; i++) {
        const cp = CHECKPOINTS[i];

        // Mock geolocation to the checkpoint's coordinates
        await context.setGeolocation({
            latitude: cp.lat,
            longitude: cp.lng,
        });

        // Wait for page to settle, then trigger arrival via Livewire directly
        // (browser geolocation mocking via Playwright doesn't reliably trigger watchPosition)
        await page.waitForTimeout(2000);
        await page.screenshot({ path: `test-results/checkpoint-${i + 1}-before-arrival.png` });

        // Call arriveAtCheckpoint directly via Livewire
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
        await page.screenshot({ path: `test-results/checkpoint-${i + 1}-after-arrival.png` });

        // Wait for the "Answer Questions" button to appear
        // Use goToQuestions button or the wire:click="goToQuestions" element
        const answerBtn = page.locator('button[wire\\:click="goToQuestions"]');
        await answerBtn.waitFor({ timeout: 15000 });

        // Navigate to the question screen
        await page.click('button:has-text("Answer Questions")');
        await page.waitForURL(/\/session\/[A-Za-z0-9]+\/question\/\d+/, {
            timeout: 15000,
        });

        // ── Answer all questions at this checkpoint ──
        let onQuestionScreen = true;
        while (onQuestionScreen) {
            // Wait for question page to load
            await page.waitForTimeout(2000);
            await page.screenshot({ path: `test-results/checkpoint-${i + 1}-question.png` });

            // Check if there are answer buttons (multiple choice / true-false)
            const answerButtons = page.locator('button[wire\\:key^="answer-"]');
            const answerCount = await answerButtons.count();

            if (answerCount > 0) {
                // Click the first answer (always correct per our seed data)
                await answerButtons.first().click();
            } else {
                // Open text question — type an answer
                const textarea = page.locator('textarea');
                if ((await textarea.count()) > 0) {
                    await textarea.fill('test answer');
                }
            }

            // Click Submit Answer
            const submitBtn = page.locator('button').filter({ hasText: /Submit Answer/i });
            if ((await submitBtn.count()) > 0) {
                await submitBtn.click();
            }

            // Wait for feedback to appear
            await page.waitForSelector('button:has-text("Next")', {
                timeout: 10000,
            });

            // Click Next
            await page.click('button:has-text("Next")');

            // Wait for navigation or next question to load
            await page.waitForTimeout(1000);

            const currentUrl = page.url();
            if (!currentUrl.includes('/question/')) {
                onQuestionScreen = false;
            }
        }

        // After completing a checkpoint's questions, we should be either:
        // - back on /play (more checkpoints to go)
        // - on /complete (last checkpoint done)
        const urlAfterCheckpoint = page.url();
        if (i < CHECKPOINTS.length - 1) {
            expect(urlAfterCheckpoint).toContain('/play');
        }
    }

    // ── Step 5: Verify quest completion ──
    await page.waitForURL(/\/session\/[A-Za-z0-9]+\/complete/, {
        timeout: 15000,
    });
    await expect(
        page.locator('h1:has-text("Quest Complete")'),
    ).toBeVisible();
});
