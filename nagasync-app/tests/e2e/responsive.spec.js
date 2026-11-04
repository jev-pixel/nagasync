import { expect, test } from '@playwright/test';

const VIEWPORTS = [
    { name: 'stress 320', width: 320, height: 568 },
    { name: 'mobile-small 375', width: 375, height: 667 },
    { name: 'mobile-large 430', width: 430, height: 932 },
    { name: 'tablet 768', width: 768, height: 1024 },
    { name: 'desktop 1280', width: 1280, height: 800 },
];

async function login(page, email) {
    await page.goto('/login');
    await page.fill('#email', email);
    await page.fill('#password', 'password');
    await page.click('button[type=submit]');
    await page.waitForURL('**/dashboard');
}

const focusSignature = (el) => el.evaluate((n) => {
    const s = getComputedStyle(n);
    return `${s.boxShadow}|${s.outlineStyle}|${s.outlineWidth}`;
});

// Reach an element the way a keyboard user does, so :focus-visible really applies
async function tabTo(page, locator) {
    await page.evaluate(() => { document.activeElement?.blur(); window.scrollTo(0, 0); });
    for (let i = 0; i < 80; i++) {
        await page.keyboard.press('Tab');
        if (await locator.evaluate((n) => n === document.activeElement)) return true;
    }
    return false;
}

async function focusRingAppears(page, testId) {
    const el = page.getByTestId(testId).first();
    await page.evaluate(() => document.activeElement?.blur());
    const before = await focusSignature(el);

    expect(await tabTo(page, el), `the keyboard could not reach "${testId}"`).toBe(true);

    return (await focusSignature(el)) !== before;
}

for (const vp of VIEWPORTS) {
    test.describe(vp.name, () => {
        const mobile = vp.width < 768;
        test.use({ viewport: { width: vp.width, height: vp.height }, hasTouch: mobile, isMobile: mobile });

        test('no horizontal page scroll, and the right navigation', async ({ page }) => {
            await login(page, 'admin@nagasync.test');

            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);

            if (overflow > 0) {
                // Diagnostic only: name the elements that stick out and are not clipped by an ancestor
                const offenders = await page.evaluate(() => {
                    const vw = document.documentElement.clientWidth;
                    const clipped = (el) => {
                        for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
                            if (getComputedStyle(p).overflowX !== 'visible') return true;
                        }
                        return false;
                    };

                    return [...document.querySelectorAll('body *')]
                        .filter((el) => !clipped(el) && el.getBoundingClientRect().right > vw + 1)
                        .slice(0, 5)
                        .map((el) => `${el.tagName.toLowerCase()}.${String(el.className).slice(0, 70)}`);
                });
                expect(offenders, `page is ${overflow}px wider than the viewport`).toEqual([]);
            }
            expect(overflow).toBeLessThanOrEqual(0);

            if (mobile) {
                await expect(page.getByTestId('bottom-nav')).toBeVisible();
                await expect(page.getByTestId('sidenav')).toBeHidden();
            } else {
                await expect(page.getByTestId('sidenav')).toBeVisible();
                await expect(page.getByTestId('bottom-nav')).toBeHidden();
            }

            await page.screenshot({ path: `tests/e2e/shots/${vp.width}.png`, fullPage: true });
        });

        test('the page background is the canvas colour (the stylesheet loaded)', async ({ page }) => {
            await login(page, 'citizen@nagasync.test');

            expect(await page.evaluate(() => getComputedStyle(document.body).backgroundColor)).toBe('rgb(245, 246, 251)');
        });

        test('keyboard focus is visible on the FAB and the search box', async ({ page }) => {
            await login(page, 'citizen@nagasync.test');

            expect(await focusRingAppears(page, 'fab')).toBe(true);
            expect(await focusRingAppears(page, 'search')).toBe(true);
        });

        if (vp.width < 1024) {
            test('carousel scrolls sideways without moving the page', async ({ page }) => {
                await login(page, 'citizen@nagasync.test');
                const carousel = page.getByTestId('folder-carousel');

                expect(await carousel.evaluate((el) => el.scrollWidth > el.clientWidth)).toBe(true);
                expect(await carousel.evaluate((el) => getComputedStyle(el).overflowX)).toBe('auto');

                await carousel.evaluate((el) => el.scrollTo({ left: 400, behavior: 'instant' }));
                expect(await carousel.evaluate((el) => el.scrollLeft)).toBeGreaterThan(0);
                expect(await page.evaluate(() => window.scrollX)).toBe(0);

                expect(await focusRingAppears(page, 'folder-card')).toBe(true);
            });
        }

        if (vp.width >= 768) {
            test('FAB reacts to hover', async ({ page }) => {
                await login(page, 'citizen@nagasync.test');
                const fab = page.getByTestId('fab');
                const look = () => fab.evaluate((el) => { const s = getComputedStyle(el); return `${s.transform}|${s.boxShadow}`; });

                const before = await look();
                await fab.hover();
                await page.waitForTimeout(300);

                expect(await look()).not.toBe(before);
            });
        }

        if (vp.width === 768) {
            test('folder cards react to hover', async ({ page }) => {
                await login(page, 'citizen@nagasync.test');
                const card = page.getByTestId('folder-card').first();
                const look = () => card.evaluate((el) => { const s = getComputedStyle(el); return `${s.transform}|${s.boxShadow}`; });

                const before = await look();
                await card.hover();
                await page.waitForTimeout(300);

                expect(await look()).not.toBe(before);
            });
        }
    });
}