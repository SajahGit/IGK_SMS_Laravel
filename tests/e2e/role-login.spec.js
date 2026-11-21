import { test, expect } from '@playwright/test';
import { qaRoles } from './helpers/roles.js';
import { login } from './helpers/auth.js';

test.describe('Eight-role login and dashboard smoke tests', () => {
    for (const role of qaRoles) {
        test(`${role.role} can reach its dashboard`, async ({ page }) => {
            await login(page, role);
            await page.goto(role.dashboard);
            await expect(page).not.toHaveURL(/login|two-factor\/challenge/);
            await expect(page.locator('body')).toBeVisible();
        });
    }
});
