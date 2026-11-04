# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: responsive.spec.js >> stress 320 >> no horizontal page scroll, and the right navigation
- Location: tests\e2e\responsive.spec.js:49:9

# Error details

```
Test timeout of 30000ms exceeded.
```

```
Error: page.goto: net::ERR_ABORTED; maybe frame was detached?
Call log:
  - navigating to "http://127.0.0.1:8000/login", waiting until "load"

```

# Test source

```ts
  1   | import { expect, test } from '@playwright/test';
  2   | 
  3   | const VIEWPORTS = [
  4   |     { name: 'stress 320', width: 320, height: 568 },
  5   |     { name: 'mobile-small 375', width: 375, height: 667 },
  6   |     { name: 'mobile-large 430', width: 430, height: 932 },
  7   |     { name: 'tablet 768', width: 768, height: 1024 },
  8   |     { name: 'desktop 1280', width: 1280, height: 800 },
  9   | ];
  10  | 
  11  | async function login(page, email) {
> 12  |     await page.goto('/login');
      |                ^ Error: page.goto: net::ERR_ABORTED; maybe frame was detached?
  13  |     await page.fill('#email', email);
  14  |     await page.fill('#password', 'password');
  15  |     await page.click('button[type=submit]');
  16  |     await page.waitForURL('**/dashboard');
  17  | }
  18  | 
  19  | const focusSignature = (el) => el.evaluate((n) => {
  20  |     const s = getComputedStyle(n);
  21  |     return `${s.boxShadow}|${s.outlineStyle}|${s.outlineWidth}`;
  22  | });
  23  | 
  24  | // Reach an element the way a keyboard user does, so :focus-visible really applies
  25  | async function tabTo(page, locator) {
  26  |     await page.evaluate(() => { document.activeElement?.blur(); window.scrollTo(0, 0); });
  27  |     for (let i = 0; i < 80; i++) {
  28  |         await page.keyboard.press('Tab');
  29  |         if (await locator.evaluate((n) => n === document.activeElement)) return true;
  30  |     }
  31  |     return false;
  32  | }
  33  | 
  34  | async function focusRingAppears(page, testId) {
  35  |     const el = page.getByTestId(testId).first();
  36  |     await page.evaluate(() => document.activeElement?.blur());
  37  |     const before = await focusSignature(el);
  38  | 
  39  |     expect(await tabTo(page, el), `the keyboard could not reach "${testId}"`).toBe(true);
  40  | 
  41  |     return (await focusSignature(el)) !== before;
  42  | }
  43  | 
  44  | for (const vp of VIEWPORTS) {
  45  |     test.describe(vp.name, () => {
  46  |         const mobile = vp.width < 768;
  47  |         test.use({ viewport: { width: vp.width, height: vp.height }, hasTouch: mobile, isMobile: mobile });
  48  | 
  49  |         test('no horizontal page scroll, and the right navigation', async ({ page }) => {
  50  |             await login(page, 'admin@nagasync.test');
  51  | 
  52  |             const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  53  | 
  54  |             if (overflow > 0) {
  55  |                 // Diagnostic only: name the elements that stick out and are not clipped by an ancestor
  56  |                 const offenders = await page.evaluate(() => {
  57  |                     const vw = document.documentElement.clientWidth;
  58  |                     const clipped = (el) => {
  59  |                         for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
  60  |                             if (getComputedStyle(p).overflowX !== 'visible') return true;
  61  |                         }
  62  |                         return false;
  63  |                     };
  64  | 
  65  |                     return [...document.querySelectorAll('body *')]
  66  |                         .filter((el) => !clipped(el) && el.getBoundingClientRect().right > vw + 1)
  67  |                         .slice(0, 5)
  68  |                         .map((el) => `${el.tagName.toLowerCase()}.${String(el.className).slice(0, 70)}`);
  69  |                 });
  70  |                 expect(offenders, `page is ${overflow}px wider than the viewport`).toEqual([]);
  71  |             }
  72  |             expect(overflow).toBeLessThanOrEqual(0);
  73  | 
  74  |             if (mobile) {
  75  |                 await expect(page.getByTestId('bottom-nav')).toBeVisible();
  76  |                 await expect(page.getByTestId('sidenav')).toBeHidden();
  77  |             } else {
  78  |                 await expect(page.getByTestId('sidenav')).toBeVisible();
  79  |                 await expect(page.getByTestId('bottom-nav')).toBeHidden();
  80  |             }
  81  | 
  82  |             await page.screenshot({ path: `tests/e2e/shots/${vp.width}.png`, fullPage: true });
  83  |         });
  84  | 
  85  |         test('the page background is the canvas colour (the stylesheet loaded)', async ({ page }) => {
  86  |             await login(page, 'citizen@nagasync.test');
  87  | 
  88  |             expect(await page.evaluate(() => getComputedStyle(document.body).backgroundColor)).toBe('rgb(245, 246, 251)');
  89  |         });
  90  | 
  91  |         test('keyboard focus is visible on the FAB and the search box', async ({ page }) => {
  92  |             await login(page, 'citizen@nagasync.test');
  93  | 
  94  |             expect(await focusRingAppears(page, 'fab')).toBe(true);
  95  |             expect(await focusRingAppears(page, 'search')).toBe(true);
  96  |         });
  97  | 
  98  |         if (vp.width < 1024) {
  99  |             test('carousel scrolls sideways without moving the page', async ({ page }) => {
  100 |                 await login(page, 'citizen@nagasync.test');
  101 |                 const carousel = page.getByTestId('folder-carousel');
  102 | 
  103 |                 expect(await carousel.evaluate((el) => el.scrollWidth > el.clientWidth)).toBe(true);
  104 |                 expect(await carousel.evaluate((el) => getComputedStyle(el).overflowX)).toBe('auto');
  105 | 
  106 |                 await carousel.evaluate((el) => el.scrollTo({ left: 400, behavior: 'instant' }));
  107 |                 expect(await carousel.evaluate((el) => el.scrollLeft)).toBeGreaterThan(0);
  108 |                 expect(await page.evaluate(() => window.scrollX)).toBe(0);
  109 | 
  110 |                 expect(await focusRingAppears(page, 'folder-card')).toBe(true);
  111 |             });
  112 |         }
```