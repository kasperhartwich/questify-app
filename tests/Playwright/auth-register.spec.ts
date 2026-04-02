import { test, expect } from '@playwright/test';

/**
 * Generate a unique suffix for test data to avoid collisions between runs.
 */
const unique = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

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
        // Email should be pre-filled from step 1
        await page.fill('input[name="password"]', 'password123');

        // Submit registration
        await page.click('button[type="submit"]');

        // Should redirect to discover page
        await page.waitForURL('**/discover/list', { timeout: 15000 });
    });

    test('register with phone number only', async ({ page }) => {
        const suffix = unique();
        // Use a phone number where last 6 digits serve as the OTP code in local env
        const phoneLocal = '20123456';
        const otpCode = phoneLocal.slice(-6); // "123456"

        await page.goto('/register');

        // Step 1: Enter phone number and send SMS
        await page.selectOption('select[wire\\:model="country_code"]', '+45');
        await page.fill('input[name="phone"]', phoneLocal);
        await page.click('button:has-text("Send SMS")');

        // Step 2: OTP verification screen
        await page.waitForSelector('[wire\\:model="phone_code"]', { timeout: 10000 });

        // Fill OTP code digit by digit into the code boxes
        const codeInputs = page.locator('input[inputmode="numeric"]');
        const inputCount = await codeInputs.count();

        if (inputCount > 1) {
            // Individual digit boxes
            for (let i = 0; i < otpCode.length && i < inputCount; i++) {
                await codeInputs.nth(i).fill(otpCode[i]);
            }
        } else {
            // Single input field
            await codeInputs.first().fill(otpCode);
        }

        await page.click('button:has-text("Verify")');

        // Step 3: Complete profile
        await page.waitForSelector('input[name="first_name"]', { timeout: 10000 });
        await page.fill('input[name="first_name"]', 'Phone');
        await page.fill('input[name="display_name"]', `phonetester_${suffix}`);

        await page.click('button[type="submit"]');

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

        // Logout by clearing session and navigating
        await page.goto('/logout');
        await page.waitForURL('**/', { timeout: 10000 });

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
        const suffix = unique();
        // Use a unique phone number
        const phoneLocal = `50${suffix.slice(0, 6).replace(/\D/g, '0').padEnd(6, '0')}`;
        const otpCode = phoneLocal.slice(-6);

        // First registration
        await page.goto('/register');
        await page.selectOption('select[wire\\:model="country_code"]', '+45');
        await page.fill('input[name="phone"]', phoneLocal);
        await page.click('button:has-text("Send SMS")');

        await page.waitForSelector('[wire\\:model="phone_code"]', { timeout: 10000 });
        const codeInputs = page.locator('input[inputmode="numeric"]');
        const inputCount = await codeInputs.count();
        if (inputCount > 1) {
            for (let i = 0; i < otpCode.length && i < inputCount; i++) {
                await codeInputs.nth(i).fill(otpCode[i]);
            }
        } else {
            await codeInputs.first().fill(otpCode);
        }
        await page.click('button:has-text("Verify")');

        await page.waitForSelector('input[name="first_name"]', { timeout: 10000 });
        await page.fill('input[name="first_name"]', 'Phone');
        await page.fill('input[name="display_name"]', `phonedup1_${suffix}`);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/discover/list', { timeout: 15000 });

        // Logout
        await page.goto('/logout');
        await page.waitForURL('**/', { timeout: 10000 });

        // Second registration with same phone
        await page.goto('/register');
        await page.selectOption('select[wire\\:model="country_code"]', '+45');
        await page.fill('input[name="phone"]', phoneLocal);
        await page.click('button:has-text("Send SMS")');

        // Should show validation error for phone
        const phoneError = page.locator('p.text-coral');
        await expect(phoneError.first()).toBeVisible({ timeout: 10000 });
    });
});
