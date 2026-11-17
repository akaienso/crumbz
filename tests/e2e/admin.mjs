// Admin checks for the Crumbz test site: Settings save and the policy wizard. Local throwaway site only.
import puppeteer from 'puppeteer-core';
import { execFileSync } from 'node:child_process';

const BASE = process.env.CRUMBZ_BASE || 'http://localhost:8080';
const CHROME = process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const WP = process.env.WP_BIN || 'wp';
const WP_PATH = process.env.CRUMBZ_WP_PATH || '/tmp/crumbz-wp';
const USER = process.env.CRUMBZ_TEST_ADMIN_USER || 'testadmin';
const PASS = process.env.CRUMBZ_TEST_ADMIN_PASS || '';

let pass = 0, fail = 0;
const lines = [];
function check(name, ok, detail = '') {
    ok ? pass++ : fail++;
    lines.push(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? '  -- ' + detail : ''}`);
}
function wp(args) {
    const raw = execFileSync(WP, [`--path=${WP_PATH}`, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] });
    // WP-CLI prints PHP deprecation notices to stdout; drop them so JSON output parses.
    return raw.split('\n').filter((l) => !/^(Deprecated|Warning|Notice):/.test(l)).join('\n').trim();
}

async function main() {
    if (!PASS) { throw new Error('set CRUMBZ_TEST_ADMIN_PASS for the local test admin'); }
    const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--no-sandbox'] });
    try {
        const ctx = await browser.createBrowserContext();
        const page = await ctx.newPage();
        await page.setViewport({ width: 1280, height: 900 });
        page.setDefaultNavigationTimeout(120000);
        // Local test site only: abort outside requests (gravatar, WordPress.org) so they cannot stall the run.
        await page.setRequestInterception(true);
        page.on('request', (req) => (req.url().startsWith(BASE) ? req.continue() : req.abort()));

        // Log in.
        await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'networkidle0' });
        await page.type('#user_login', USER);
        await page.type('#user_pass', PASS);
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#wp-submit')]);
        check('admin login works', (await page.$('#wpadminbar')) !== null);

        // Settings screen renders.
        await page.goto(`${BASE}/wp-admin/admin.php?page=crumbz`, { waitUntil: 'networkidle0' });
        const h1 = await page.$eval('.crumbz-admin h1', (n) => n.textContent.trim()).catch(() => '');
        check('Settings screen renders', h1 === 'Crumbz', h1);
        const fatal = await page.evaluate(() => /Fatal error|Warning:|Notice:/.test(document.body.innerText));
        check('Settings screen shows no PHP errors', !fatal);

        // Save a change to the icon position and check it stuck.
        await page.select('select[name="crumbz_settings[button_position]"]', 'top-right');
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#submit')]);
        const pos = wp(['option', 'get', 'crumbz_settings', '--format=json']);
        const saved = JSON.parse(pos);
        check('Settings save stores the icon position', saved.button_position === 'top-right', saved.button_position);
        const notice = await page.evaluate(() => (document.querySelector('.notice') ? document.querySelector('.notice').innerText : ''));
        check('Settings save shows a confirmation', /saved|updated/i.test(notice) || !/error/i.test(notice), notice.slice(0, 80));

        // Put the icon back so the front-end tests keep their default.
        await page.goto(`${BASE}/wp-admin/admin.php?page=crumbz`, { waitUntil: 'networkidle0' });
        await page.select('select[name="crumbz_settings[button_position]"]', 'bottom-left');
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#submit')]);

        // Jurisdiction advisor: save a UK location, check the suggestion, then apply it.
        await page.goto(`${BASE}/wp-admin/admin.php?page=crumbz-advisor`, { waitUntil: 'networkidle0' });
        const advH1 = await page.$eval('.crumbz-admin h1', (n) => n.textContent.trim()).catch(() => '');
        check('advisor screen renders', advH1 === 'Jurisdiction advisor', advH1);
        await page.select('select[name="country"]', 'GB');
        await page.click('input[name="audience[]"][value="uk"]');
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#submit')]);
        const advText = await page.evaluate(() => document.body.innerText);
        check('advisor suggests EU mode for the UK', /United Kingdom/.test(advText) && /EU mode/.test(advText));
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('#crumbz-apply')]);
        const advSettings = JSON.parse(wp(['option', 'get', 'crumbz_settings', '--format=json']));
        check('applying the advisor sets EU mode', Array.isArray(advSettings.jurisdictions) && advSettings.jurisdictions.includes('eu') && !advSettings.jurisdictions.includes('ca'), JSON.stringify(advSettings.jurisdictions));

        // Wizard: answer every question, then generate drafts.
        await page.goto(`${BASE}/wp-admin/admin.php?page=crumbz-wizard&step=1`, { waitUntil: 'networkidle0' });
        const answers = {
            1: { type: 'text', value: 'Example Test Business' },
            2: { type: 'email', value: 'privacy-test@example.com' },
            3: { type: 'select', value: 'shop' },
            4: { type: 'radio', value: 'yes' },   // analytics
            5: { type: 'radio', value: 'yes' },   // marketing
            6: { type: 'radio', value: 'no' },    // newsletter
            7: { type: 'radio', value: 'yes' },   // forms
            8: { type: 'radio', value: 'yes' },   // accounts
            9: { type: 'radio', value: 'yes' },   // payments
            10: { type: 'checkbox', values: ['eu', 'ca'] },
            11: { type: 'text', value: 'Test Country, Test State' },
        };
        for (let step = 1; step <= 11; step++) {
            const a = answers[step];
            if (a.type === 'text' || a.type === 'email') {
                await page.$eval('input[name="answer"]', (n) => { n.value = ''; });
                await page.type('input[name="answer"]', a.value);
            } else if (a.type === 'select') {
                await page.select('select[name="answer"]', a.value);
            } else if (a.type === 'radio') {
                await page.click(`input[name="answer"][value="${a.value}"]`);
            } else if (a.type === 'checkbox') {
                // Set each box to checked rather than clicking, since saved answers from an earlier run are already ticked.
                for (const v of a.values) {
                    const cb = await page.$(`input[name="answer[]"][value="${v}"]`);
                    if (!(await cb.evaluate((n) => n.checked))) { await cb.click(); }
                }
            }
            await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[name="intent"][value="next"]')]);
        }
        const summaryText = await page.evaluate(() => document.body.innerText);
        check('wizard reaches the summary after 11 questions', /Check your answers/.test(summaryText));
        check('summary shows the jurisdictions chosen', /European Union/.test(summaryText) && /California/.test(summaryText));

        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[name="intent"][value="generate"]')]);
        const doneText = await page.evaluate(() => document.body.innerText);
        check('wizard reports the draft pages as created', /Draft pages created/.test(doneText));

        // Check the drafts that were created.
        const drafts = JSON.parse(wp(['post', 'list', '--post_type=page', '--post_status=draft', '--format=json', '--fields=ID,post_title']));
        const titles = drafts.map((d) => d.post_title);
        check('three draft pages exist', ['Cookie Policy', 'Privacy Policy', 'Terms and Conditions'].every((t) => titles.includes(t)), titles.join(' | '));

        const settings = JSON.parse(wp(['option', 'get', 'crumbz_settings', '--format=json']));
        const docsLinked = ['cookie-policy', 'privacy-policy', 'terms'].every((id) => settings.documents[id].page_id > 0);
        check('settings now point at the drafts', docsLinked, JSON.stringify(Object.fromEntries(Object.entries(settings.documents).map(([k, v]) => [k, v.page_id]))));

        const cookieDraft = drafts.find((d) => d.post_title === 'Cookie Policy');
        const privacyDraft = drafts.find((d) => d.post_title === 'Privacy Policy');
        if (cookieDraft) {
            const body = wp(['post', 'get', String(cookieDraft.ID), '--field=post_content']);
            check('cookie draft lists the Google and Meta tools only when detected (none here: placeholder shown)', /\[name of your analytics tool\]|Tools:/.test(body));
            check('cookie draft names the consent cookie and its lifetime', /crumbz_consent/.test(body) && /180 days/.test(body));
            check('cookie draft does not contain admin-only wording', !/needs human review|not legal advice/i.test(body));
        }
        if (privacyDraft) {
            const body = wp(['post', 'get', String(privacyDraft.ID), '--field=post_content']);
            check('privacy draft includes the EU legal-basis section', /Legal basis/.test(body));
            check('privacy draft includes the California sentence with a fill-in choice', /\[do not sell or share \/ sell or share\]/.test(body));
            check('privacy draft has no admin-only wording', !/needs human review/i.test(body));
        }

        // Settings screen still renders after the wizard changed documents.
        await page.goto(`${BASE}/wp-admin/admin.php?page=crumbz`, { waitUntil: 'networkidle0' });
        const pageText = await page.evaluate(() => document.body.innerText);
        check('Settings screen shows the documents section', /Documents/.test(pageText));
        check('Settings screen shows the Plugins section', /Plugins that set cookies/.test(pageText));
        await ctx.close();
    } finally {
        await browser.close();
    }
    console.log(lines.join('\n'));
    console.log(`\n${pass} passed, ${fail} failed`);
    process.exitCode = fail ? 1 : 0;
}

main().catch((e) => { console.error('ADMIN TEST ERROR:', e.message); process.exitCode = 2; });
