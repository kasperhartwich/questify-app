import { test, expect } from '@playwright/test';

/**
 * Generate a unique suffix for test data to avoid collisions between runs.
 */
const unique = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

/**
 * Fill the 6-digit OTP code boxes component.
 * The component renders 6 individual <input type="text" inputmode="numeric"> boxes.
 * When all 6 are filled, the form auto-submits.
 */
async function fillOtpCode(page: import('@playwright/test').Page, code: string) {
    const boxes = page.locator('input[inputmode="numeric"][maxlength="1"]');
    await boxes.first().waitFor({ state: 'visible', timeout: 10000 });

    // Click first box and type all digits — the Alpine handleInput auto-advances focus
    await boxes.first().click();
    for (const digit of code) {
        await page.keyboard.press(digit);
        // Small delay to let Alpine process the input event
        await page.waitForTimeout(100);
    }
}

/**
 * Clear the session by navigating to a page that triggers logout,
 * then verify we're logged out by checking for unauthenticated state.
 */
async function logout(page: import('@playwright/test').Page) {
    // Delete cookies to clear the session
    const context = page.context();
    await context.clearCookies();
    await page.goto('/');
    await page.waitForLoadState('networkidle');
}

// Shared phone number between test 2 and test 4 (tests run serially)
let registeredPhoneLocal = '';

test.describe('Registration flows', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/locale/en');
    });

    test('register with email only', async ({ page }) => {
        const suffix = unique();
        const email = `test-email-${suffix}@example.com`;

        await page.goto('/register');

        // Step 1: Enter email and continue
        await page.fill('input[name="email"]', email);
        await page.click('button:has-text("Continue")');

        // Step 2: Fill registration details
        await page.waitForSelector('input[name="first_name"]', { timeout: 5000 });
        await page.fill('input[name="first_name"]', 'Test');
        await page.fill('input[name="last_name"]', 'User');
        await page.fill('input[name="display_name"]', `tester_${suffix}`);
        await page.fill('input[name="password"]', 'password123');

        // Submit registration
        await page.click('button[type="submit"]');

        // Should redirect to discover page
        await page.waitForURL('**/discover/list', { timeout: 15000 });
    });

    test('register with phone number only', async ({ page }) => {
        const suffix = unique();
        // Generate a random 8-digit phone number; last 6 digits = OTP code in local env
        const randomDigits = Math.floor(10000000 + Math.random() * 89999999).toString();
        const phoneLocal = randomDigits;
        registeredPhoneLocal = phoneLocal; // Save for duplicate test
        const fullPhone = `+45${randomDigits}`;
        const otpCode = fullPhone.slice(-6);

        await page.goto('/register');

        // Step 1: Enter phone number and send SMS
        await page.selectOption('select[wire\\:model="country_code"]', '+45');
        await page.fill('input[name="phone"]', phoneLocal);
        await page.click('button:has-text("Send SMS")');

        // Step 2: OTP verification screen — wait for the code boxes to appear
        await fillOtpCode(page, otpCode);

        // The code-boxes auto-submits → verifyPhone → login → redirect to /register?step=3
        // Wait for the redirect to complete and step 3 form to load
        await page.waitForURL('**/register?step=3', { timeout: 15000 });
        await page.waitForSelector('input[name="first_name"]', { timeout: 5000 });

        // Step 3: Complete profile
        await page.fill('input[name="first_name"]', 'Phone');
        await page.fill('input[name="display_name"]', `phonetester_${suffix}`);
        await page.click('button:has-text("Continue")');

        // Should redirect to discover page
        await page.waitForURL('**/discover/list', { timeout: 15000 });
    });

    test('shows error for duplicate email registration', async ({ page }) => {
        const suffix = unique();
        const email = `test-dup-${suffix}@example.com`;

        // First registration
        await page.goto('/register');
        await page.fill('input[name="email"]', email);
        await page.click('button:has-text("Continue")');

        await page.waitForSelector('input[name="first_name"]', { timeout: 5000 });
        await page.fill('input[name="first_name"]', 'First');
        await page.fill('input[name="display_name"]', `dup1_${suffix}`);
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/discover/list', { timeout: 15000 });

        // Logout
        await logout(page);

        // Second registration with same email
        await page.goto('/register');
        await page.fill('input[name="email"]', email);
        await page.click('button:has-text("Continue")');

        await page.waitForSelector('input[name="first_name"]', { timeout: 5000 });
        await page.fill('input[name="first_name"]', 'Second');
        await page.fill('input[name="display_name"]', `dup2_${suffix}`);
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');

        // Should show validation error for email
        const emailError = page.locator('p.text-coral');
        await expect(emailError.first()).toBeVisible({ timeout: 10000 });
    });

    test('shows error for duplicate phone registration', async ({ page }) => {
        // Reuse the phone number registered in the "register with phone number only" test
        const phoneLocal = registeredPhoneLocal;
        expect(phoneLocal).toBeTruthy(); // Ensure previous test ran

        await page.goto('/register');
        await page.selectOption('select[wire\\:model="country_code"]', '+45');
        await page.fill('input[name="phone"]', phoneLocal);
        await page.click('button:has-text("Send SMS")');

        // Should show either:
        // - Inline "taken" validation error (422 from backend)
        // - Error dialog with "Too Many Attempts" (429 rate limit) or "taken"
        // Either way, the user stays on step 1 and sees an error
        const inlineError = page.locator('text=taken');
        const dialogError = page.locator('text=Something went wrong');

        await expect(inlineError.or(dialogError).first()).toBeVisible({ timeout: 10000 });
    });
});
