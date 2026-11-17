// End-to-end checks for the Crumbz test site. Runs against the local throwaway WordPress install only.
import puppeteer from 'puppeteer-core';
import { execFileSync } from 'node:child_process';

const BASE = process.env.CRUMBZ_BASE || 'http://localhost:8080/';
const ORIGIN = new URL(BASE).origin;
const CHROME = process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const WP = process.env.WP_BIN || 'wp';
const WP_PATH = process.env.CRUMBZ_WP_PATH || '/tmp/crumbz-wp';
const ADMIN_USER = process.env.CRUMBZ_TEST_ADMIN_USER || 'testadmin';

let pass = 0, fail = 0;
const results = [];
function check(name, ok, detail = '') {
    ok ? pass++ : fail++;
    results.push(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? '  -- ' + detail : ''}`);
}

function wp(args) {
    return execFileSync(WP, [`--path=${WP_PATH}`, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
}

function wpEvalAsAdmin(php) {
    return wp(['eval', `--user=${ADMIN_USER}`, php]);
}

async function getCookieRecord(page) {
    const raw = await page.evaluate(() => {
        const m = document.cookie.split('; ').find(c => c.startsWith('crumbz_consent='));
        return m ? m.slice('crumbz_consent='.length) : null;
    });
    if (!raw) return null;
    try { return JSON.parse(decodeURIComponent(raw)); } catch (e) { return { parseError: true }; }
}

async function clickButton(page, scope, text) {
    const handle = await page.evaluateHandle((scope, text) => {
        const root = document.querySelector(scope);
        if (!root) return null;
        return [...root.querySelectorAll('button, a')].find(b => b.textContent.trim().startsWith(text)) || null;
    }, scope, text);
    const el = handle.asElement();
    if (!el) throw new Error(`no button "${text}" in ${scope}`);
    await el.click();
}

async function isVisible(page, selector) {
    return page.evaluate((sel) => {
        const n = document.querySelector(sel);
        if (!n) return false;
        if (n.hidden) return false;
        if (n.tagName === 'DIALOG') return n.open;
        return n.getBoundingClientRect().width > 0;
    }, selector);
}

async function scriptState(page) {
    return page.evaluate(() => {
        const out = {};
        for (const id of ['test-gtag-js', 'test-fbpixel-js']) {
            const n = document.getElementById(id);
            out[id] = n ? (n.getAttribute('type') === 'text/plain' ? 'blocked' : 'live') : 'missing';
        }
        return out;
    });
}

async function freshPage(browser, extraHeaders = {}) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({ width: 1280, height: 800 });
    page.setDefaultNavigationTimeout(120000);
    // Only the local test site is reachable here; external vendor URLs are aborted so they cannot stall the run.
    await page.setRequestInterception(true);
    page.on('request', (req) => (req.url().startsWith(ORIGIN) ? req.continue() : req.abort()));
    if (Object.keys(extraHeaders).length) await page.setExtraHTTPHeaders(extraHeaders);
    return { ctx, page };
}

async function main() {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: true,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    try {
        // Base scenarios: no jurisdictions, the three published pages linked at version 1 (not the wizard drafts).
        wpEvalAsAdmin('$s = Crumbz_Settings::all(); $s["jurisdictions"] = []; foreach (["cookie-policy"=>4, "privacy-policy"=>5, "terms"=>6] as $id => $pid) { $s["documents"][$id]["page_id"] = $pid; $s["documents"][$id]["version"] = 1; } update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($s));');

        // ---- A: first visit, then Reject all ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            check('A1 banner shows on first visit', await isVisible(page, '.crumbz-banner'));
            check('A2 settings icon hidden while banner is open', !(await isVisible(page, '.crumbz-fab')));
            check('A3 no cookie before a choice', (await getCookieRecord(page)) === null);
            const s0 = await scriptState(page);
            check('A4 Google and Meta scripts blocked before a choice', s0['test-gtag-js'] === 'blocked' && s0['test-fbpixel-js'] === 'blocked', JSON.stringify(s0));
            await clickButton(page, '.crumbz-banner', 'Reject all');
            const r = await getCookieRecord(page);
            check('A5 Reject all records refusal (all categories off)', r && !r.cats.preferences && !r.cats.statistics && !r.cats.marketing, JSON.stringify(r));
            check('A6 banner closes after Reject all', !(await isVisible(page, '.crumbz-banner')));
            check('A7 settings icon appears after a choice', await isVisible(page, '.crumbz-fab'));
            const s1 = await scriptState(page);
            check('A8 scripts still blocked after refusal', s1['test-gtag-js'] === 'blocked' && s1['test-fbpixel-js'] === 'blocked', JSON.stringify(s1));
            await page.reload({ waitUntil: 'networkidle0' });
            check('A9 reload: banner stays closed (choice remembered)', !(await isVisible(page, '.crumbz-banner')));
            check('A10 reload: icon still visible', await isVisible(page, '.crumbz-fab'));
            const serverScripts = await page.evaluate(() => document.querySelectorAll('script[type="text/plain"]').length);
            check('A11 reload: blocked script tags still inert on the server output', serverScripts >= 2, `count=${serverScripts}`);

            // Icon opens the settings dialog.
            await page.click('.crumbz-fab');
            await new Promise(r => setTimeout(r, 300));
            check('A12 tapping the icon opens the settings dialog', await isVisible(page, '.crumbz-dialog'));
            await clickButton(page, '.crumbz-dialog', '×');
            await new Promise(r => setTimeout(r, 200));
            check('A13 dialog closes with the close button', !(await isVisible(page, '.crumbz-dialog')));

            // Hide the icon permanently.
            await page.click('.crumbz-fab');
            await new Promise(r => setTimeout(r, 300));
            await clickButton(page, '.crumbz-dialog', 'Hide this icon');
            await new Promise(r => setTimeout(r, 200));
            check('A14 Hide this icon hides the icon', !(await isVisible(page, '.crumbz-fab')));
            const rh = await getCookieRecord(page);
            check('A15 hidden flag recorded', rh && rh.hidden === true, JSON.stringify(rh));
            await page.reload({ waitUntil: 'networkidle0' });
            check('A16 icon stays hidden after reload', !(await isVisible(page, '.crumbz-fab')));

            // Clearing cookies resets the whole flow.
            await page.evaluate(() => { document.cookie = 'crumbz_consent=; Max-Age=0; Path=/; SameSite=Lax'; });
            await page.reload({ waitUntil: 'networkidle0' });
            check('A17 clearing cookies brings the banner back', await isVisible(page, '.crumbz-banner'));
            await ctx.close();
        }

        // ---- B: Accept all unblocks the scripts ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Accept all');
            const r = await getCookieRecord(page);
            check('B1 Accept all records consent for every category', r && r.cats.preferences && r.cats.statistics && r.cats.marketing, JSON.stringify(r));
            check('B2 Accept all records the three linked documents at version 1', !!r && r.docs['cookie-policy'] === 1 && r.docs['privacy-policy'] === 1 && r.docs['terms'] === 1, JSON.stringify(r && r.docs));
            const s = await scriptState(page);
            check('B3 scripts unblocked in the open page after Accept all', s['test-gtag-js'] === 'live' && s['test-fbpixel-js'] === 'live', JSON.stringify(s));
            await page.reload({ waitUntil: 'networkidle0' });
            const s2 = await scriptState(page);
            check('B4 after reload, server renders the allowed scripts live', s2['test-gtag-js'] === 'live' && s2['test-fbpixel-js'] === 'live', JSON.stringify(s2));
            await ctx.close();
        }

        // ---- C: Customize - statistics only ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Customize');
            await new Promise(r => setTimeout(r, 300));
            const checkedBefore = await page.evaluate(() => [...document.querySelectorAll('.crumbz-dialog input[type=checkbox]:not(:disabled)')].map(c => c.checked));
            check('C1 EU-style defaults: nothing pre-ticked (no choice yet)', checkedBefore.every(v => v === false), JSON.stringify(checkedBefore));
            await page.evaluate(() => {
                const input = document.getElementById('crumbz-cat-statistics');
                input.click();
            });
            await clickButton(page, '.crumbz-dialog', 'Save choices');
            await new Promise(r => setTimeout(r, 200));
            const r = await getCookieRecord(page);
            check('C2 Save with statistics only records statistics=true, marketing=false', r && r.cats.statistics === true && r.cats.marketing === false && r.cats.preferences === false, JSON.stringify(r && r.cats));
            await page.reload({ waitUntil: 'networkidle0' });
            const s = await scriptState(page);
            check('C3 statistics script live, marketing script still blocked', s['test-gtag-js'] === 'live' && s['test-fbpixel-js'] === 'blocked', JSON.stringify(s));
            await ctx.close();
        }

        // ---- D: Dismiss (x) counts as refusal ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', '×');
            await new Promise(r => setTimeout(r, 200));
            const r = await getCookieRecord(page);
            check('D1 dismissing the banner records a refusal', r && !r.cats.statistics && !r.cats.marketing, JSON.stringify(r && r.cats));
            check('D2 dismissed banner does not come back on reload', (await page.reload({ waitUntil: 'networkidle0' })) && !(await isVisible(page, '.crumbz-banner')));
            await ctx.close();
        }

        // ---- E: Drag the icon, then it stays where it was dropped ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Accept all');
            await new Promise(r => setTimeout(r, 200));
            const box = await (await page.$('.crumbz-fab')).boundingBox();
            await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
            await page.mouse.down();
            for (let i = 1; i <= 10; i++) {
                await page.mouse.move(box.x + box.width / 2 + i * 30, box.y + box.height / 2 - i * 25);
            }
            await page.mouse.up();
            await new Promise(r => setTimeout(r, 200));
            const nb = await (await page.$('.crumbz-fab')).boundingBox();
            check('E1 dragging moves the icon', Math.abs(nb.x - box.x) > 100 && Math.abs(nb.y - box.y) > 100, `from (${Math.round(box.x)},${Math.round(box.y)}) to (${Math.round(nb.x)},${Math.round(nb.y)})`);
            const dialogAfterDrag = await isVisible(page, '.crumbz-dialog');
            check('E2 a drag does not also open the dialog', !dialogAfterDrag);
            await page.reload({ waitUntil: 'networkidle0' });
            const rb = await (await page.$('.crumbz-fab')).boundingBox();
            check('E3 icon keeps its dragged place after reload (same tab session)', Math.abs(rb.x - nb.x) < 3 && Math.abs(rb.y - nb.y) < 3, `${Math.round(rb.x)},${Math.round(rb.y)}`);
            await ctx.close();
        }

        // ---- E4-E6: icon position is kept in a cookie only when Preferences is accepted ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Reject all');
            await new Promise(r => setTimeout(r, 200));
            const box = await (await page.$('.crumbz-fab')).boundingBox();
            await page.mouse.move(box.x + 24, box.y + 24);
            await page.mouse.down();
            for (let i = 1; i <= 10; i++) { await page.mouse.move(box.x + 24 + i * 30, box.y + 24 - i * 25); }
            await page.mouse.up();
            await new Promise(r => setTimeout(r, 200));
            const posCookie = await page.evaluate(() => document.cookie.includes('crumbz_icon='));
            check('E4 refused Preferences: no icon-position cookie is set', !posCookie);
            await page.reload({ waitUntil: 'networkidle0' });
            const rb = await (await page.$('.crumbz-fab')).boundingBox();
            check('E5 refused Preferences: icon is back at its default place after reload', Math.abs(rb.x - box.x) < 3 && Math.abs(rb.y - box.y) < 3, `${Math.round(rb.x)},${Math.round(rb.y)} (default was ${Math.round(box.x)},${Math.round(box.y)})`);
            await ctx.close();
        }
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Accept all');
            await new Promise(r => setTimeout(r, 200));
            const box = await (await page.$('.crumbz-fab')).boundingBox();
            await page.mouse.move(box.x + 24, box.y + 24);
            await page.mouse.down();
            for (let i = 1; i <= 10; i++) { await page.mouse.move(box.x + 24 + i * 30, box.y + 24 - i * 25); }
            await page.mouse.up();
            await new Promise(r => setTimeout(r, 200));
            const posCookie = await page.evaluate(() => document.cookie.includes('crumbz_icon='));
            const dragged = await (await page.$('.crumbz-fab')).boundingBox();
            check('E6 accepted Preferences: icon-position cookie is set', posCookie);
            const page2 = await ctx.newPage();
            await page2.setRequestInterception(true);
            page2.on('request', (req) => (req.url().startsWith(ORIGIN) ? req.continue() : req.abort()));
            await page2.setViewport({ width: 1280, height: 800 });
            await page2.goto(BASE, { waitUntil: 'networkidle0' });
            const nb = await (await page2.$('.crumbz-fab')).boundingBox();
            check('E7 accepted Preferences: a new tab in the same browser keeps the icon where it was dropped', Math.abs(nb.x - dragged.x) < 3 && Math.abs(nb.y - dragged.y) < 3, `${Math.round(nb.x)},${Math.round(nb.y)} (dropped at ${Math.round(dragged.x)},${Math.round(dragged.y)})`);
            await ctx.close();
        }

        // ---- J: Escape on the banner is a refusal ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await page.keyboard.press('Escape');
            await new Promise(r => setTimeout(r, 200));
            const r = await getCookieRecord(page);
            check('J1 Escape on the banner records a refusal', !!r && !r.cats.statistics && !r.cats.marketing, JSON.stringify(r && r.cats));
            await ctx.close();
        }

        // ---- K: leaving without a choice is not consent ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await page.goto(`${BASE}?page_id=2`, { waitUntil: 'networkidle0' });
            check('K1 leaving without a choice sets no consent record', (await getCookieRecord(page)) === null);
            const s = await scriptState(page);
            check('K2 leaving without a choice keeps optional scripts blocked', s['test-gtag-js'] === 'blocked' && s['test-fbpixel-js'] === 'blocked', JSON.stringify(s));
            check('K3 the banner shows again on the next page', await isVisible(page, '.crumbz-banner'));
            await ctx.close();
        }

        // ---- T1/T2: theme choice is stored only when Preferences is accepted ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Accept all');
            await new Promise(r => setTimeout(r, 200));
            await page.click('.crumbz-fab');
            await new Promise(r => setTimeout(r, 300));
            await clickButton(page, '.crumbz-dialog', 'Theme');   // auto -> light
            await new Promise(r => setTimeout(r, 200));
            const themeCookie = await page.evaluate(() => document.cookie.split('; ').find(c => c.startsWith('crumbz_theme=')) || null);
            check('T1 accepted Preferences: theme choice is stored in the crumbz_theme cookie', themeCookie === 'crumbz_theme=light', themeCookie || 'none');
            await page.reload({ waitUntil: 'networkidle0' });
            const dataTheme = await page.evaluate(() => document.getElementById('crumbz-root').getAttribute('data-theme'));
            check('T2 accepted Preferences: the stored theme is applied after reload', dataTheme === 'light', dataTheme);
            await ctx.close();
        }
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Reject all');
            await new Promise(r => setTimeout(r, 200));
            await page.click('.crumbz-fab');
            await new Promise(r => setTimeout(r, 300));
            await clickButton(page, '.crumbz-dialog', 'Theme');
            const themeCookie = await page.evaluate(() => document.cookie.includes('crumbz_theme='));
            check('T3 refused Preferences: no theme cookie is set', !themeCookie);
            await page.reload({ waitUntil: 'networkidle0' });
            const dataTheme = await page.evaluate(() => document.getElementById('crumbz-root').getAttribute('data-theme'));
            check('T4 refused Preferences: the theme choice does not persist after reload', dataTheme === 'auto', dataTheme);
            await ctx.close();
        }

        // ---- F: Theme toggle cycles and applies ----
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            const t0 = await page.evaluate(() => document.getElementById('crumbz-root').getAttribute('data-theme'));
            await clickButton(page, '.crumbz-banner', 'Theme');
            const t1 = await page.evaluate(() => document.getElementById('crumbz-root').getAttribute('data-theme'));
            check('F1 theme toggle cycles auto -> light', t0 === 'auto' && t1 === 'light', `${t0} -> ${t1}`);
            await ctx.close();
        }

        // ---- G: California + GPC ----
        wpEvalAsAdmin('$s = Crumbz_Settings::all(); $s["jurisdictions"] = ["ca"]; update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($s));');
        {
            const { ctx, page } = await freshPage(browser, { 'Sec-GPC': '1' });
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Accept all');
            await new Promise(r => setTimeout(r, 200));
            const s = await scriptState(page);
            check('G1 California + GPC: marketing script stays blocked even after Accept all', s['test-fbpixel-js'] === 'blocked', JSON.stringify(s));
            check('G2 California + GPC: statistics script still allowed after Accept all', s['test-gtag-js'] === 'live', JSON.stringify(s));
            await page.click('.crumbz-fab');
            await new Promise(r => setTimeout(r, 300));
            const doNotSell = await page.evaluate(() => [...document.querySelectorAll('.crumbz-dialog button')].some(b => /Do Not Sell/.test(b.textContent)));
            check('G3 Do Not Sell or Share button shown for California', doNotSell);
            const gpcVisible = await isVisible(page, '#crumbz-gpc-note') || await page.evaluate(() => { const n = document.querySelector('.crumbz-dialog .crumbz-note'); return !!n && !n.hidden; });
            check('G4 GPC note shown in the dialog', gpcVisible);
            await ctx.close();
        }

        // ---- H: a document version bump asks everyone again ----
        wpEvalAsAdmin('$s = Crumbz_Settings::all(); $s["jurisdictions"] = []; $s["documents"]["privacy-policy"]["page_id"] = 5; $s["documents"]["privacy-policy"]["version"] = 1; update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($s));');
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            await clickButton(page, '.crumbz-banner', 'Accept all');
            const r1 = await getCookieRecord(page);
            check('H1 accepting records the privacy policy at version 1', r1 && r1.docs['privacy-policy'] === 1, JSON.stringify(r1 && r1.docs));
            wpEvalAsAdmin('$s = Crumbz_Settings::all(); $s["documents"]["privacy-policy"]["version"] = 2; update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($s));');
            await page.reload({ waitUntil: 'networkidle0' });
            check('H2 bumping the document version brings the banner back', await isVisible(page, '.crumbz-banner'));
            await ctx.close();
        }

        // ---- I: disabled plugin does nothing ----
        wpEvalAsAdmin('$s = Crumbz_Settings::all(); $s["enabled"] = 0; $s["jurisdictions"] = []; update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($s));');
        {
            const { ctx, page } = await freshPage(browser);
            await page.goto(BASE, { waitUntil: 'networkidle0' });
            const hasRoot = await page.evaluate(() => !!document.getElementById('crumbz-root'));
            check('I1 disabled: no Crumbz UI is loaded', !hasRoot);
            const s = await scriptState(page);
            check('I2 disabled: third-party scripts are not blocked by Crumbz', s['test-gtag-js'] === 'live' && s['test-fbpixel-js'] === 'live', JSON.stringify(s));
            await ctx.close();
        }
        wpEvalAsAdmin('$s = Crumbz_Settings::all(); $s["enabled"] = 1; update_option(Crumbz_Settings::OPTION, Crumbz_Settings::sanitize($s));');
    } finally {
        await browser.close();
    }

    console.log(results.join('\n'));
    console.log(`\n${pass} passed, ${fail} failed`);
    process.exitCode = fail ? 1 : 0;
}

main().catch((e) => {
    console.error('TEST RUN ERROR:', e);
    process.exitCode = 2;
});
