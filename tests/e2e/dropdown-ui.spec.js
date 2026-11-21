import { test, expect } from '@playwright/test';
import path from 'node:path';

const root = process.cwd();
const themes = ['light', 'dark'];

async function loadDropdownFixture(page, theme) {
    await page.setContent(`
        <!doctype html>
        <html lang="en" data-bs-theme="${theme}">
        <head><meta name="viewport" content="width=device-width, initial-scale=1"></head>
        <body>
            <main class="container p-4">
                <div class="row g-3">
                    <form id="branchContextForm" style="width: 126px">
                        <label class="form-label" for="active-branch">Active campus</label>
                        <select class="form-select form-select-sm" id="active-branch" name="branch_id" onchange="window.branchChanged = true">
                            <option value="1">Main campus</option>
                            <option value="2">North campus</option>
                        </select>
                    </form>
                    <div class="col-6" style="max-width: 340px">
                        <label class="form-label" for="class-select">Class</label>
                        <select class="form-select" id="class-select">
                            <option value="">Select class</option>
                            ${Array.from({ length: 30 }, (_, index) => `<option value="${index + 1}">Grade ${index + 1} - A</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-6" style="max-width: 340px">
                        <label class="form-label">Country</label>
                        <div class="dropdown international-country-picker">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle international-country-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">Select country</button>
                            <div class="dropdown-menu international-country-menu" role="listbox">
                                <div class="p-2"><input class="form-control" aria-label="Search countries"></div>
                                ${Array.from({ length: 30 }, (_, index) => `<button type="button" class="dropdown-item international-country-option">Country ${index + 1}</button>`).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="col-6" style="max-width: 220px">
                        <label class="form-label">Nationality</label>
                        <div class="dropdown international-nationality-picker" data-nationality-picker>
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle international-nationality-toggle"
                                data-bs-toggle="dropdown" data-nationality-toggle aria-expanded="false">
                                <span class="phone-flag bi bi-globe" data-nationality-flag></span>
                                <span data-nationality-label>Select nationality</span>
                            </button>
                            <div class="dropdown-menu international-nationality-menu" role="listbox">
                                <div class="p-2"><input class="form-control" aria-label="Search nationalities" data-nationality-search></div>
                                ${['Sri Lankan', 'Indian', 'British'].map((value, index) => `<button type="button" class="dropdown-item international-nationality-option" role="option" data-nationality-option data-value="${value}" data-code="${index}" data-flag="lk">${value}</button>`).join('')}
                            </div>
                            <select class="visually-hidden" data-nationality-value><option value="">Select nationality</option></select>
                        </div>
                    </div>
                    <div class="col-6" style="max-width: 220px">
                        <label class="form-label">Actions</label>
                        <div class="dropup">
                            <button type="button" id="actions-toggle" class="btn btn-secondary dropdown-toggle"
                                data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                            <div class="dropdown-menu">
                                <button type="button" class="dropdown-item">Action one</button>
                                <button type="button" class="dropdown-item">Action two</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Phone country</label>
                        <div class="input-group" data-international-phone>
                            <div class="dropdown phone-country-picker">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle phone-country-toggle"
                                    data-bs-toggle="dropdown" data-phone-toggle data-phone-lengths="10"
                                    data-national-prefix="0" aria-expanded="false">Select country</button>
                                <div class="dropdown-menu phone-country-menu" role="listbox">
                                    <div class="p-2"><input class="form-control" aria-label="Search phone countries"></div>
                                    ${Array.from({ length: 30 }, (_, index) => `<button type="button" class="dropdown-item phone-country-option" role="option" data-phone-option data-region="US" data-calling-code="${index + 1}" data-phone-lengths="10" data-national-prefix="0" data-search="country ${index + 1}">+${index + 1} Country ${index + 1}</button>`).join('')}
                                </div>
                            </div>
                            <select class="visually-hidden" data-phone-region><option value="US" selected>US</option></select>
                            <input class="form-control" aria-label="Phone number" data-phone-number data-phone-lengths="10" data-phone-max-length="10" data-national-prefix="0">
                        </div>
                    </div>
                </div>
            </main>
        </body>
        </html>
    `);

    await page.addStyleTag({ path: path.join(root, 'public/vendor/bootstrap/bootstrap.min.css') });
    await page.addStyleTag({ path: path.join(root, 'public/css/glass.css') });
    await page.addScriptTag({ path: path.join(root, 'public/vendor/bootstrap/bootstrap.bundle.min.js') });
    await page.addScriptTag({ path: path.join(root, 'public/js/app.js') });
}

async function expectMenuBelowAnchor(page, toggleSelector, menuSelector, widthAnchorSelector, matchWidth = true) {
    const toggle = page.locator(toggleSelector);
    const menu = page.locator(menuSelector);
    await toggle.click();
    await expect(menu).toBeVisible();

    const geometry = await page.evaluate(({ menu, toggle, widthAnchor }) => {
        const menuElement = document.querySelector(menu);
        const toggleElement = document.querySelector(toggle);
        const widthElement = document.querySelector(widthAnchor);
        const menuRect = menuElement.getBoundingClientRect();
        const toggleRect = toggleElement.getBoundingClientRect();
        const widthRect = widthElement.getBoundingClientRect();
        const style = getComputedStyle(menuElement);
        const textStyle = getComputedStyle(menuElement.querySelector('[role="option"], .dropdown-item'));
        const luminance = (value) => {
            const channels = value.match(/[\d.]+/g).slice(0, 3).map(Number).map((channel) => {
                const normalized = channel / 255;
                return normalized <= 0.04045
                    ? normalized / 12.92
                    : Math.pow((normalized + 0.055) / 1.055, 2.4);
            });
            return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
        };
        const backgroundLuminance = luminance(style.backgroundColor);
        const textLuminance = luminance(textStyle.color);
        const contrastRatio = (Math.max(backgroundLuminance, textLuminance) + 0.05)
            / (Math.min(backgroundLuminance, textLuminance) + 0.05);

        return {
            menuTop: menuRect.top,
            menuLeft: menuRect.left,
            menuWidth: menuRect.width,
            toggleBottom: toggleRect.bottom,
            anchorLeft: widthRect.left,
            anchorWidth: widthRect.width,
            position: style.position,
            maxHeight: style.maxHeight,
            overflowY: style.overflowY,
            background: style.backgroundColor,
            color: textStyle.color,
            contrastRatio,
        };
    }, { menu: menuSelector, toggle: toggleSelector, widthAnchor: widthAnchorSelector });

    expect(geometry.position).not.toBe('fixed');
    expect(geometry.menuTop).toBeGreaterThanOrEqual(geometry.toggleBottom - 1);
    expect(Math.abs(geometry.menuLeft - geometry.anchorLeft)).toBeLessThan(2);
    if (matchWidth) {
        expect(Math.abs(geometry.menuWidth - geometry.anchorWidth)).toBeLessThan(2);
    }
    expect(geometry.background).not.toBe('rgba(0, 0, 0, 0)');
    expect(geometry.contrastRatio).toBeGreaterThanOrEqual(4.5);
    expect(geometry.maxHeight).not.toBe('none');

    return geometry;
}

async function closeMenuWithEscape(page, menuSelector) {
    await page.keyboard.press('Escape');
    await expect(page.locator(menuSelector)).toBeHidden();
}

async function expectScrollable(locator) {
    const scrollState = await locator.evaluate((element) => {
        const overflow = getComputedStyle(element).overflowY;
        element.scrollTop = element.scrollHeight;
        return {
            overflow,
            scrollTop: element.scrollTop,
            scrollHeight: element.scrollHeight,
            clientHeight: element.clientHeight,
        };
    });
    expect(scrollState.overflow).toBe('auto');
    expect(scrollState.scrollHeight).toBeGreaterThan(scrollState.clientHeight);
    expect(scrollState.scrollTop).toBeGreaterThan(0);
}

for (const theme of themes) {
    test.describe(`Dropdown layout in ${theme} theme`, () => {
        test('shared dropdown types stay aligned, scrollable, and readable', async ({ page }, testInfo) => {
            const consoleErrors = [];
            const pageErrors = [];
            const failedRequests = [];
            page.on('console', (message) => {
                if (message.type() === 'error') consoleErrors.push(message.text());
            });
            page.on('pageerror', (error) => pageErrors.push(error.message));
            page.on('requestfailed', (request) => failedRequests.push(request.url()));
            await loadDropdownFixture(page, theme);

            const searchableGeometry = await expectMenuBelowAnchor(
                page,
                '.searchable-select:has(#class-select) .searchable-select-toggle',
                '.searchable-select:has(#class-select) .searchable-select-menu',
                '.searchable-select:has(#class-select)'
            );
            const options = page.locator('.searchable-select:has(#class-select) .searchable-select-options');
            await expectScrollable(options);
            await page.screenshot({
                path: path.join(root, 'QA/Evidence/Screenshots', `Dropdown-Searchable-${theme}-${testInfo.project.name}.png`),
                fullPage: true,
            });
            await closeMenuWithEscape(page, '.searchable-select:has(#class-select) .searchable-select-menu');

            const branchGeometry = await expectMenuBelowAnchor(
                page,
                '#branchContextForm .searchable-select-toggle',
                '#branchContextForm .searchable-select-menu',
                '#branchContextForm .searchable-select'
            );
            await page.locator('#branchContextForm .searchable-select-menu')
                .getByRole('option', { name: 'North campus', exact: true })
                .click();
            await expect(page.locator('#active-branch')).toHaveValue('2');
            await expect(page.locator('#branchContextForm .searchable-select-toggle')).toHaveText('North campus');
            expect(await page.evaluate(() => window.branchChanged)).toBe(true);

            const countryGeometry = await expectMenuBelowAnchor(
                page,
                '.international-country-toggle',
                '.international-country-menu',
                '.international-country-picker'
            );
            await expectScrollable(page.locator('.international-country-menu'));
            await closeMenuWithEscape(page, '.international-country-menu');

            const nationalityGeometry = await expectMenuBelowAnchor(
                page,
                '.international-nationality-toggle',
                '.international-nationality-menu',
                '.international-nationality-picker'
            );
            await closeMenuWithEscape(page, '.international-nationality-menu');

            const phoneGeometry = await expectMenuBelowAnchor(
                page,
                '.phone-country-toggle',
                '.phone-country-menu',
                '.phone-country-picker'
            );
            await expectScrollable(page.locator('.phone-country-menu'));
            await closeMenuWithEscape(page, '.phone-country-menu');

            const standardGeometry = await expectMenuBelowAnchor(
                page,
                '#actions-toggle',
                '#actions-toggle + .dropdown-menu',
                '#actions-toggle',
                false
            );

            expect(searchableGeometry.menuWidth).toBeGreaterThan(0);
            expect(branchGeometry.menuWidth).toBeGreaterThan(0);
            expect(countryGeometry.menuWidth).toBeGreaterThan(0);
            expect(nationalityGeometry.menuWidth).toBeGreaterThan(0);
            expect(phoneGeometry.menuWidth).toBeGreaterThan(0);
            expect(standardGeometry.menuWidth).toBeGreaterThan(0);
            expect(consoleErrors).toEqual([]);
            expect(pageErrors).toEqual([]);
            expect(failedRequests).toEqual([]);

            await page.screenshot({
                path: path.join(root, 'QA/Evidence/Screenshots', `Dropdown-${theme}-${testInfo.project.name}.png`),
                fullPage: true,
            });
            await closeMenuWithEscape(page, '#actions-toggle + .dropdown-menu');
        });
    });
}
