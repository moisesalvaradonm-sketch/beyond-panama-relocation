# Beyond Panama Relocation — Lead Engine

An end-to-end lead capture / CRM / segmentation / email automation / AI content
platform for **beyondpanamarelocation.com**. Built for Hostinger shared hosting
(PHP 8.1+ / MySQL 8+) with Brevo for email delivery and the Anthropic API for
the AI Content Engine.

---

## 1. What this ships with

- **CRM database** (25+ tables): contacts, interests, locations, quiz results,
  activities, segments, campaigns, templates, queue, events, automations,
  content library, knowledge base, form submissions, users, sessions, settings.
- **Public routes** — `/api/bpr/routes/*.php`
  - `quiz.php` — accepts quiz answers + email, scores, creates contact,
    triggers the quiz nurture automation.
  - `contact.php` — generic contact creation (newsletter, form).
  - `form.php` — property/consultation/guide/residency form intake.
  - `track.php` — behaviour events (page views, WhatsApp clicks, downloads…).
  - `open.php` / `click.php` — pixel + link-tracking for sent emails.
  - `unsubscribe.php` — signed public unsubscribe link.
- **Admin routes** — `/api/bpr/admin/*.php` (session-token protected):
  `login`, `logout`, `dashboard`, `contacts`, `templates`, `campaigns`,
  `automations`, `content`, `knowledge`, `ai`, `settings`.
- **Cron jobs** — `/api/bpr/cron/*.php` (require `?token=CRON_TOKEN`):
  `email_queue.php` (every 5 min), `automations.php` (every 5 min),
  `reactivation.php` (daily), `ai_content.php` (weekly).
- **Admin SPA** — `/admin/` (plain HTML/CSS/JS, no build step): dashboard,
  contacts list + detail, campaigns, templates, automations, content, KB,
  AI panel, settings.
- **Frontend integration**:
  - Quiz component `src/components/quiz/Quiz.tsx` now also POSTs to the Lead
    Engine and drops a `bpr_cid` cookie for cross-page attribution.
  - Tracking script `public/js/bpr-tracker.js` loaded from `Layout.astro`.
  - Legacy `subscribe.php` and `property-inquiry.php` now feed the CRM.
- **AI Content Engine** — Claude API integration guarded by an approved
  Knowledge Base, with hard rules against hallucinated facts.

---

## 2. Install (Hostinger)

### 2.1 Prerequisites in hPanel
1. **MySQL database** — hPanel → Databases → Create a database. Note down:
   name, user, password, host (usually `localhost`).
2. **PHP version 8.1+** — hPanel → Advanced → PHP Configuration.
3. **A cron job** — hPanel → Cron Jobs (steps below).

### 2.2 Upload the files
Deploy the Astro build (`npm run build && upload dist/*`). Everything in
`public/` ends up under `dist/`. On the server, you should have:

```
/api/bpr/                (backend)
/api/bpr/admin/
/api/bpr/cron/
/api/bpr/lib/
/api/bpr/routes/
/api/bpr/config.example.php
/api/bpr/install.php
/api/bpr/schema.sql
/api/bpr/seeds.sql
/admin/                  (admin panel: index.html, app.js, styles.css)
/js/bpr-tracker.js
```

### 2.3 Create `config.php`
On the server, copy `config.example.php` to `config.php` (SFTP or File Manager)
and edit:

- `db.*` — MySQL credentials
- `brevo.api_key` — you already have this in `.env` (`BREVO_API_KEY`).
- `brevo.list_id` — you have `BREVO_LIST_ID=3`.
- `anthropic.api_key` — create at https://console.anthropic.com
- `security.app_secret` — `openssl rand -hex 32` locally, paste it.
- `security.cron_token` — same again, paste it.
- `security.link_secret` — same again, paste it.

**`config.php` is protected by `.htaccess` — do not commit it to git.**

### 2.4 Run the installer once
Open in your browser:

```
https://beyondpanamarelocation.com/api/bpr/install.php
```

Fill the form to create the first admin user (email + password of 12+ chars).
When it finishes it writes `install.php.done`. **Delete `install.php` from
the server after that** — the .htaccess also 404s the `.done` file to prevent
info leaks, but removing the installer is safer.

### 2.5 Schedule the cron jobs
In hPanel → Cron Jobs → Add. Use the "Custom" schedule.

| Every    | URL                                                                                         |
| -------- | ------------------------------------------------------------------------------------------- |
| `*/5 * * * *`  | `curl -s "https://beyondpanamarelocation.com/api/bpr/cron/email_queue.php?token=YOUR_CRON_TOKEN"` |
| `*/5 * * * *`  | `curl -s "https://beyondpanamarelocation.com/api/bpr/cron/automations.php?token=YOUR_CRON_TOKEN"`  |
| `*/10 * * * *` | `curl -s "https://beyondpanamarelocation.com/api/bpr/cron/campaigns.php?token=YOUR_CRON_TOKEN"` (sends scheduled + weekly/monthly newsletters) |
| `0 3 * * *`    | `curl -s "https://beyondpanamarelocation.com/api/bpr/cron/reactivation.php?token=YOUR_CRON_TOKEN"` |
| `0 9 * * 1`    | `curl -s "https://beyondpanamarelocation.com/api/bpr/cron/ai_content.php?token=YOUR_CRON_TOKEN"`   |

### 2.5b Upgrading an existing install (recurring newsletters)
Open once: `https://beyondpanamarelocation.com/api/bpr/migrate.php?token=YOUR_CRON_TOKEN`
(adds `recurrence`, `recur_day`, `recur_time` to `bpr_campaigns`; safe to repeat).

### 2.6 Sanity test
```
https://beyondpanamarelocation.com/api/bpr/test.php?token=YOUR_CRON_TOKEN
```

This creates a "Patrick" contact, runs the quiz, exercises segmentation,
scoring, intent detection, automations, the email queue, unsubscribe signing,
and reports pass/fail.

---

## 3. Admin panel

Open https://beyondpanamarelocation.com/admin/ and sign in with the admin user
you created in 2.4.

Sections:

- **Dashboard** — totals, engagement in the last 30 days, breakdown by
  interest / segment / quiz result / source.
- **Contacts** — filter by status/intent/segment, search, click a row for
  a full profile with timeline + AI email preview.
- **Campaigns** — create + send one-off campaigns to a segment or to all
  subscribers with consent. Send is asynchronous (queued).
- **Templates** — edit email templates. Variables supported: `{{first_name}}`,
  `{{recommended_location}}`, `{{quiz_match}}`, `{{timeline}}`, `{{budget}}`,
  `{{main_interest}}`, `{{preferred_area}}`, `{{consultation_url}}`,
  `{{whatsapp_url}}`, `{{unsubscribe_url}}`, `{{business_name}}`, `{{year}}`.
- **Automations** — toggle workflows on/off, see enrollments.
- **Content Library** — draft/review/publish AI-generated articles.
- **AI Content Engine** — ping Claude, generate an article on demand.
- **Knowledge Base** — facts the AI is allowed to cite. **Anything not here
  will not be invented by the AI.** Add residency requirements, prices,
  timelines, etc. as they are confirmed.
- **Settings** — business name, URLs, sender, lead-score rules.

---

## 4. AI Content Engine

The AI receives the Knowledge Base as authoritative context and is instructed
to refuse to invent numbers/laws/prices. The three modes (in `bpr_settings`
`ai_content_mode`, or `features.auto_publish_ai` in config):

- **manual** — AI generates only when you click "Generate" in the admin.
- **semi** (default) — the weekly cron generates one article and files it as
  `status = review`. Nothing is sent or published until you approve.
- **full** — `features.auto_publish_ai = true` publishes automatically.

Extend the topic pool in `cron/ai_content.php`.

---

## 5. Data flow (a fresh lead)

1. Visitor lands on the site. `bpr-tracker.js` logs `page_view` and captures
   UTM parameters in a cookie.
2. They complete the quiz. `Quiz.tsx` POSTs to `/api/bpr/routes/quiz.php`
   with the normalised answers.
3. `Contact::upsert` creates the contact, `Quiz::scoreAndStore` computes the
   Panama recommendation, `Activity::log` records quiz_completed + quiz_result,
   `LeadScorer::bump` updates the score, `Segmenter::recompute` assigns
   segments, `IntentDetector::evaluate` sets `intent_level`.
4. `Automation::enrollByTrigger('quiz_completed')` enrols them in
   `quiz_nurture`. The runner (cron) immediately queues the first email
   using their language and populates all `{{variables}}`.
5. `cron/email_queue.php` sends the email via Brevo. The HTML is rewritten so
   every link and a 1×1 pixel go through `/api/bpr/routes/click.php` and
   `open.php`. Opens/clicks feed back into `bpr_email_events`, update the
   contact's last-activity, and grant score.
6. The contact browses more pages. The tracker logs `page_view_residency`
   / `page_view_property` etc. based on the URL and grants additional score.
   Anywhere they click "WhatsApp" → `whatsapp_click` → +20.
7. Score crosses thresholds → status jumps `cold` → `engaged` → `hot`.
   Intent crosses to `high` after `whatsapp_click`.
8. If they stop interacting for 90 days, the daily reactivation cron enrols
   them in the reactivation workflow — one email, one chance to re-engage.

---

## 6. Frontend snippets to sprinkle in the site

**WhatsApp button** (already tracked by the tracker script — no change needed
if the link contains `wa.me`):
```html
<a href="https://wa.me/50760844256" data-whatsapp>Chat on WhatsApp</a>
```

**Consultation button** — mark it explicitly so the tracker logs it as a
`consultation_request` (worth +25):
```html
<a href="/en/contact" data-consultation>Book a free consultation</a>
```

**Guide download** — anything ending in `.pdf` is auto-tracked. Otherwise:
```html
<a href="/downloads/panama-guide.pdf" data-guide>Download the guide</a>
```

**Identifying a known contact from another form** on the site:
```js
BPR.identify(<contact_id_returned_by_the_API>);
BPR.track({ event: 'guide_download', category: 'residency' });
```

---

## 7. Security notes

- `config.php` is protected by `.htaccess` (Require all denied) and never
  reachable from the web.
- `lib/*.php` is also 404'd — never call them directly.
- Admin auth is bcrypt + a random 64-char token stored in `bpr_sessions`,
  sent as `Authorization: Bearer <token>`. Sessions expire after 14 days.
- Login rate-limited to 8 attempts / 5 min per IP.
- Public write endpoints rate-limited (20-30 req/h/IP for form-ish endpoints,
  120 req/min for tracking).
- Unsubscribe + open + click URLs are HMAC-signed with `link_secret`.
- Cron endpoints require `?token=CRON_TOKEN`.

**Rotate secrets** at least once a year (or immediately on compromise) by
editing `config.php`. Existing signed URLs will stop validating — this only
affects unsubscribe / tracking links in already-sent emails, so schedule the
rotation right after a big campaign.

---

## 8. Backups

- MySQL dump nightly via hPanel → Backups → Automatic Backups.
- Include the `bpr_` tables (they are the CRM).
- Do not commit `config.php`, `logs/`, or `install.php.done` to git.

---

## 9. Extending

- **Add a new workflow** — insert into `bpr_automations` + `bpr_automation_steps`
  (or via the admin). Trigger types: `quiz_completed`, `contact_created`,
  `interest_added`, `segment_entered`, `tag`, `manual`, `stale_days`.
- **Add a new segment** — insert into `bpr_segments` with a rule JSON:
  `{"any":[{"field":"main_interest","op":"eq","value":"retirement"}]}`.
  Fields supported: `main_interest`, `timeline`, `buying_status`, `budget`,
  `language`, `country`, `lead_score`, `lead_status`, `quiz_result`,
  `interest` (has/in), `location` (has/in).
- **Change scoring** — Settings → Lead-score rules or `UPDATE bpr_score_rules`.
- **Multilingual emails** — create the same template `slug` with
  `language = 'es'` or `'fr'`. The Mailer picks the contact's language first
  and falls back to English.

---

## 10. What is NOT included (roadmap)

- Visual drag-drop automation builder (you edit steps as JSON for now).
- SMS / WhatsApp Business API broadcasts.
- Calendly / Cal.com booking widget integration.
- Meta / Google Ads pixel wiring (add via GTM in the Layout).
- A/B testing UI (schema supports storing versions; UI + winner-pick TBD).
- Automatic republishing of AI content to social channels.
- Brevo webhook receiver (opens/clicks/bounces from Brevo instead of our
  own pixel — add `/api/bpr/routes/brevo-webhook.php` verifying the shared
  secret in `config.security.brevo_webhook_secret`).

All of these can be added later without breaking the current data model.
