import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { qaRoles } from './helpers/roles.js';
import { login } from './helpers/auth.js';

const resultsDir = path.resolve('QA/Results');
const resultsFile = path.join(resultsDir, 'STAGE4_SIDEBAR_RESULTS.csv');

function appendCsv(row) {
    fs.mkdirSync(resultsDir, { recursive: true });
    if (!fs.existsSync(resultsFile)) {
        fs.writeFileSync(resultsFile, 'Test_ID,Role,Page,Route,Status,HTTP_Status,Console_Errors,Network_Failures,Theme,Viewport,Evidence_File,Remarks\n');
    }
    const values = Object.values(row).map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`);
    fs.appendFileSync(resultsFile, `${values.join(',')}\n`);
}

test.describe('Eight-role sidebar read-only smoke tests', () => {
    for (const role of qaRoles) {
        test(`${role.role} sidebar pages load`, async ({ page }, testInfo) => {
            test.setTimeout(600_000);
            const consoleErrors = [];
            const networkFailures = [];
            page.on('console', (message) => {
                if (message.type() === 'error') consoleErrors.push(message.text());
            });
            page.on('requestfailed', (request) => {
                networkFailures.push(`${request.method()} ${request.url()} ${request.failure()?.errorText ?? ''}`);
            });

            await login(page, role);
            await page.goto(role.dashboard);
            await expect(page.locator('body')).toBeVisible();

            const roleDir = role.role.replaceAll('/', '-').replaceAll(' ', '-');
            const screenshotDir = path.resolve('QA/Evidence/Screenshots', roleDir);
            fs.mkdirSync(screenshotDir, { recursive: true });

            await page.locator('html').evaluate((html) => html.setAttribute('data-bs-theme', 'light'));
            await page.screenshot({
                path: path.join(screenshotDir, `TC-${roleDir}-DASHBOARD-LIGHT-${testInfo.project.name}.png`),
                fullPage: true,
            });
            await page.getByRole('button', { name: 'Switch theme' }).click();
            await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');
            await page.screenshot({
                path: path.join(screenshotDir, `TC-${roleDir}-DASHBOARD-DARK-${testInfo.project.name}.png`),
                fullPage: true,
            });
            await page.reload();
            await expect(page.locator('html')).toHaveAttribute('data-bs-theme', 'dark');

            const menuButton = page.getByRole('button', { name: 'Menu' });
            if (await menuButton.isVisible()) {
                await menuButton.click();
            }

            const links = await page.locator('aside#sidebar a.nav-glass[href]').evaluateAll((anchors) =>
                [...new Map(anchors.map((anchor) => [anchor.getAttribute('href'), {
                    href: anchor.getAttribute('href'),
                    label: anchor.textContent?.trim() || anchor.getAttribute('href') || '',
                }])).values()]
            );

            for (const [index, link] of links.entries()) {
                const testId = `TC-${role.role.toUpperCase().replaceAll(/[^A-Z0-9]+/g, '-')}-SIDEBAR-${String(index + 1).padStart(3, '0')}`;
                const response = await page.goto(link.href, {
                    waitUntil: 'domcontentloaded',
                    timeout: 60_000,
                });
                const status = response?.status() ?? 0;
                const resultStatus = status >= 200 && status < 400 ? 'PASS' : 'FAIL';
                const routeLabel = new URL(link.href, 'http://127.0.0.1:8000').pathname;

                if (status >= 400) {
                    await page.screenshot({
                        path: path.join(screenshotDir, `${testId}-${routeLabel.replaceAll(/[^a-zA-Z0-9]+/g, '-')}-${testInfo.project.name}-FAIL.png`),
                        fullPage: true,
                    });
                }

                appendCsv({
                    testId,
                    role: role.role,
                    page: link.label,
                    route: routeLabel,
                    status: resultStatus,
                    httpStatus: status,
                    consoleErrors: consoleErrors.splice(0).join(' | '),
                    networkFailures: networkFailures.splice(0).join(' | '),
                    theme: 'dark',
                    viewport: testInfo.project.name,
                    evidenceFile: status >= 400
                        ? `QA/Evidence/Screenshots/${roleDir}/${testId}-${routeLabel.replaceAll(/[^a-zA-Z0-9]+/g, '-')}-${testInfo.project.name}-FAIL.png`
                        : '',
                    remarks: status >= 200 && status < 400 ? 'Page response successful' : 'Unexpected HTTP or redirect status',
                });
            }
        });
    }
});
