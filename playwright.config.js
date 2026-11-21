import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: true,
    retries: 0,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'QA/Evidence/HTML-Reports', open: 'never' }],
    ],
    use: {
        baseURL: 'http://127.0.0.1:8011',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        viewport: { width: 1920, height: 1080 },
        colorScheme: 'dark',
    },
    projects: [
        { name: 'desktop-dark-1920', use: { ...devices['Desktop Chrome'], viewport: { width: 1920, height: 1080 }, colorScheme: 'dark' } },
        { name: 'desktop-light-1366', use: { ...devices['Desktop Chrome'], viewport: { width: 1366, height: 768 }, colorScheme: 'light' } },
        { name: 'tablet-light-768', use: { ...devices['iPad Mini'], browserName: 'chromium', viewport: { width: 768, height: 1024 }, colorScheme: 'light' } },
        { name: 'mobile-dark-390', use: { ...devices['iPhone 13'], browserName: 'chromium', viewport: { width: 390, height: 844 }, colorScheme: 'dark' } },
        { name: 'mobile-light-360', use: { ...devices['Pixel 7'], browserName: 'chromium', viewport: { width: 360, height: 800 }, colorScheme: 'light' } },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8011',
        url: 'http://127.0.0.1:8011',
        reuseExistingServer: false,
        timeout: 120_000,
        env: {
            APP_ENV: 'testing',
            DB_CONNECTION: 'mysql',
            DB_DATABASE: 'school_management_system_testing_new_db',
        },
    },
});
