import { expect } from '@playwright/test';

export async function login(page, role) {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(role.email);
    await page.locator('input[name="password"]').fill(process.env.QA_PASSWORD ?? 'password');
    await page.getByRole('button', { name: /login|sign in/i }).click();
    await expect(page).toHaveURL(/.*/, { timeout: 15_000 });

    if (page.url().includes('/two-factor/challenge')) {
        const autoFill = page.getByRole('button', { name: 'Auto-fill' });
        await expect(autoFill).toBeVisible({ timeout: 10_000 });
        await autoFill.click();
        await page.getByRole('button', { name: /verify & sign in/i }).click();
        await expect(page).not.toHaveURL(/login|two-factor\/challenge/, { timeout: 15_000 });
    }
}
