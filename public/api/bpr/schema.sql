-- =====================================================================
-- Beyond Panama Relocation — Lead Engine schema
-- MySQL 8+ / MariaDB 10.5+
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------------------
-- Users (admin panel)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','editor','sales','readonly') NOT NULL DEFAULT 'admin',
  active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_sessions (
  token       CHAR(64) NOT NULL PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at  DATETIME NOT NULL,
  ip          VARCHAR(45) NULL,
  user_agent  VARCHAR(255) NULL,
  KEY (user_id),
  KEY (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Contacts (the CRM core)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_contacts (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email              VARCHAR(190) NOT NULL UNIQUE,
  first_name         VARCHAR(120) NULL,
  last_name          VARCHAR(120) NULL,
  phone              VARCHAR(40)  NULL,
  country            VARCHAR(80)  NULL,           -- nationality / origin
  current_country    VARCHAR(80)  NULL,
  current_city       VARCHAR(120) NULL,
  language           ENUM('en','es','fr') NOT NULL DEFAULT 'en',

  main_interest      ENUM('relocation','residency','retirement','property',
                          'business','remote_work','healthcare','lifestyle',
                          'investment','banking','other') NULL,
  timeline           ENUM('researching','2plus_years','1_2_years','6_12_months',
                          '3_6_months','under_3_months','ready') NULL,

  buying_status      ENUM('buying','renting','undecided') NULL,
  budget             ENUM('under_100k','100_200k','200_300k','300_500k',
                          '500k_1m','1m_plus') NULL,
  preferred_area     VARCHAR(120) NULL,
  property_type      ENUM('condo','house','land','commercial','investment') NULL,

  lead_source        VARCHAR(80)  NULL,   -- quiz, newsletter, blog, google, meta, whatsapp...
  utm_source         VARCHAR(120) NULL,
  utm_medium         VARCHAR(120) NULL,
  utm_campaign       VARCHAR(120) NULL,
  utm_content        VARCHAR(120) NULL,

  lead_score         INT NOT NULL DEFAULT 0,
  lead_status        ENUM('cold','engaged','hot','very_hot') NOT NULL DEFAULT 'cold',
  intent_level       ENUM('none','low','medium','high','ready') NOT NULL DEFAULT 'none',

  marketing_consent  TINYINT(1) NOT NULL DEFAULT 0,
  consent_source     VARCHAR(80)  NULL,
  consent_at         DATETIME NULL,
  unsubscribed_at    DATETIME NULL,

  last_email_sent_at   DATETIME NULL,
  last_email_opened_at DATETIME NULL,
  last_click_at        DATETIME NULL,
  last_activity_at     DATETIME NULL,

  notes              TEXT NULL,

  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_status    (lead_status),
  KEY idx_score     (lead_score),
  KEY idx_interest  (main_interest),
  KEY idx_timeline  (timeline),
  KEY idx_consent   (marketing_consent),
  KEY idx_last_act  (last_activity_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-value: secondary interests
CREATE TABLE IF NOT EXISTS bpr_contact_interests (
  contact_id INT UNSIGNED NOT NULL,
  interest   VARCHAR(60)  NOT NULL,
  score      INT NOT NULL DEFAULT 0,
  PRIMARY KEY (contact_id, interest),
  CONSTRAINT fk_ci_contact FOREIGN KEY (contact_id) REFERENCES bpr_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-value: interested Panama locations
CREATE TABLE IF NOT EXISTS bpr_contact_locations (
  contact_id INT UNSIGNED NOT NULL,
  location   VARCHAR(60)  NOT NULL,
  score      INT NOT NULL DEFAULT 0,
  PRIMARY KEY (contact_id, location),
  CONSTRAINT fk_cl_contact FOREIGN KEY (contact_id) REFERENCES bpr_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Quiz results / answers
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_quiz_results (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id    INT UNSIGNED NOT NULL,
  quiz_key      VARCHAR(60) NOT NULL DEFAULT 'where_to_live',
  result_key    VARCHAR(60) NOT NULL,          -- panama_city, boquete, coronado, ...
  result_label  VARCHAR(120) NOT NULL,
  match_percent TINYINT UNSIGNED NOT NULL,
  alternates    JSON NULL,                     -- [{key,label,pct}, ...]
  answers_json  JSON NULL,                     -- raw answers
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (contact_id),
  KEY (result_key),
  CONSTRAINT fk_qr_contact FOREIGN KEY (contact_id) REFERENCES bpr_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Activity / behaviour timeline
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_activities (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id   INT UNSIGNED NULL,
  event        VARCHAR(60) NOT NULL,   -- newsletter_signup, quiz_completed, page_view, email_open, email_click, whatsapp_click, form_submit, guide_download, consultation_request, unsubscribe
  category     VARCHAR(60) NULL,       -- e.g. interest tag: residency, property, boquete
  url          VARCHAR(500) NULL,
  title        VARCHAR(255) NULL,
  meta         JSON NULL,
  score_delta  INT NOT NULL DEFAULT 0,
  ip           VARCHAR(45) NULL,
  user_agent   VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (contact_id),
  KEY (event),
  KEY (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Segments (dynamic, evaluated on contact update)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_segments (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(60) NOT NULL UNIQUE,
  name         VARCHAR(120) NOT NULL,
  description  VARCHAR(255) NULL,
  rule_json    JSON NOT NULL,       -- { all: [ {field, op, value}, ... ] }
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_contact_segments (
  contact_id INT UNSIGNED NOT NULL,
  segment_id INT UNSIGNED NOT NULL,
  added_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (contact_id, segment_id),
  KEY (segment_id),
  CONSTRAINT fk_cs_contact FOREIGN KEY (contact_id) REFERENCES bpr_contacts(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_segment FOREIGN KEY (segment_id) REFERENCES bpr_segments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Email templates & campaigns & queue
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_email_templates (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(120) NOT NULL UNIQUE,
  name         VARCHAR(190) NOT NULL,
  language     ENUM('en','es','fr') NOT NULL DEFAULT 'en',
  subject      VARCHAR(255) NOT NULL,
  preheader    VARCHAR(255) NULL,
  body_html    MEDIUMTEXT NOT NULL,
  body_text    MEDIUMTEXT NULL,
  ai_generated TINYINT(1) NOT NULL DEFAULT 0,
  status       ENUM('draft','approved','archived') NOT NULL DEFAULT 'draft',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_campaigns (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(190) NOT NULL,
  template_id    INT UNSIGNED NOT NULL,
  segment_id     INT UNSIGNED NULL,           -- if NULL: everyone with consent
  language       ENUM('en','es','fr') NOT NULL DEFAULT 'en',
  recurrence     ENUM('none','weekly','monthly') NOT NULL DEFAULT 'none',
  recur_day      TINYINT UNSIGNED NULL,       -- weekly: 1=Mon..7=Sun, monthly: 1..28
  recur_time     TIME NULL,                   -- Panama time (America/Panama)
  status         ENUM('draft','scheduled','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
  scheduled_at   DATETIME NULL,               -- UTC; for recurring = next run
  sent_at        DATETIME NULL,
  total_sent     INT NOT NULL DEFAULT 0,
  total_opens    INT NOT NULL DEFAULT 0,
  total_clicks   INT NOT NULL DEFAULT 0,
  total_unsubs   INT NOT NULL DEFAULT 0,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (status),
  KEY (scheduled_at),
  CONSTRAINT fk_cp_tpl     FOREIGN KEY (template_id) REFERENCES bpr_email_templates(id),
  CONSTRAINT fk_cp_segment FOREIGN KEY (segment_id)  REFERENCES bpr_segments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_email_queue (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id    INT UNSIGNED NOT NULL,
  template_id   INT UNSIGNED NULL,
  campaign_id   INT UNSIGNED NULL,
  enrollment_id BIGINT UNSIGNED NULL,        -- automation enrollment reference
  subject       VARCHAR(255) NOT NULL,
  body_html     MEDIUMTEXT NOT NULL,
  body_text     MEDIUMTEXT NULL,
  status        ENUM('pending','sending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  attempts      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  error         VARCHAR(500) NULL,
  provider_id   VARCHAR(120) NULL,       -- Brevo messageId
  scheduled_at  DATETIME NOT NULL,
  sent_at       DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (status),
  KEY (scheduled_at),
  KEY (contact_id),
  CONSTRAINT fk_eq_contact FOREIGN KEY (contact_id) REFERENCES bpr_contacts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_email_events (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  queue_id     BIGINT UNSIGNED NULL,
  campaign_id  INT UNSIGNED NULL,
  contact_id   INT UNSIGNED NULL,
  event        ENUM('sent','delivered','open','click','bounce','spam','unsubscribe','failed') NOT NULL,
  url          VARCHAR(500) NULL,
  meta         JSON NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (queue_id),
  KEY (campaign_id),
  KEY (contact_id),
  KEY (event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Automations (workflows)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_automations (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(120) NOT NULL UNIQUE,
  name         VARCHAR(190) NOT NULL,
  trigger_type ENUM('quiz_completed','contact_created','interest_added',
                    'segment_entered','tag','manual','stale_days') NOT NULL,
  trigger_config JSON NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_automation_steps (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  automation_id INT UNSIGNED NOT NULL,
  position      SMALLINT UNSIGNED NOT NULL,
  step_type     ENUM('wait','send_email','if_opened','if_clicked','add_score',
                     'set_field','tag','goto','end') NOT NULL,
  config_json   JSON NOT NULL,          -- { wait_hours: 48, template_slug: '...', ... }
  KEY (automation_id, position),
  CONSTRAINT fk_as_auto FOREIGN KEY (automation_id) REFERENCES bpr_automations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bpr_automation_enrollments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  automation_id INT UNSIGNED NOT NULL,
  contact_id    INT UNSIGNED NOT NULL,
  current_step  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  next_run_at   DATETIME NOT NULL,
  status        ENUM('active','completed','stopped','error') NOT NULL DEFAULT 'active',
  context_json  JSON NULL,
  started_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at  DATETIME NULL,
  KEY (status, next_run_at),
  KEY (contact_id),
  UNIQUE KEY uniq_auto_contact (automation_id, contact_id),
  CONSTRAINT fk_ae_auto    FOREIGN KEY (automation_id) REFERENCES bpr_automations(id) ON DELETE CASCADE,
  CONSTRAINT fk_ae_contact FOREIGN KEY (contact_id)    REFERENCES bpr_contacts(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Content library (blog + AI-generated pieces)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_content (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug           VARCHAR(190) NOT NULL UNIQUE,
  title          VARCHAR(255) NOT NULL,
  category       VARCHAR(60) NULL,     -- relocation|residency|retirement|property|business|lifestyle
  language       ENUM('en','es','fr') NOT NULL DEFAULT 'en',
  location_tag   VARCHAR(60) NULL,     -- panama_city, boquete, ...
  interest_tags  JSON NULL,            -- ["residency","retirement",...]
  summary        TEXT NULL,
  body_md        MEDIUMTEXT NULL,
  meta_title     VARCHAR(255) NULL,
  meta_desc      VARCHAR(500) NULL,
  hero_prompt    VARCHAR(500) NULL,
  status         ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
  ai_generated   TINYINT(1) NOT NULL DEFAULT 0,
  needs_review   TINYINT(1) NOT NULL DEFAULT 0,
  published_at   DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY (status),
  KEY (category),
  KEY (language)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Knowledge base (approved facts the AI can cite)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_knowledge (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic        VARCHAR(120) NOT NULL,       -- residency, pensionado, healthcare, ...
  key_name     VARCHAR(190) NOT NULL,       -- e.g. pensionado.minimum_income
  value_text   MEDIUMTEXT NOT NULL,
  source       VARCHAR(255) NULL,
  verified_at  DATE NULL,
  confidence   ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  language     ENUM('en','es','fr','any') NOT NULL DEFAULT 'any',
  UNIQUE KEY uniq_topic_key (topic, key_name, language)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Form submissions (generic capture)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_form_submissions (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id   INT UNSIGNED NULL,
  form_key     VARCHAR(80) NOT NULL,      -- property_inquiry, consultation, guide_download
  payload_json JSON NOT NULL,
  ip           VARCHAR(45) NULL,
  user_agent   VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (form_key),
  KEY (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Settings (single row of key/value)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_settings (
  name  VARCHAR(80) NOT NULL PRIMARY KEY,
  value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Score rules (configurable)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bpr_score_rules (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event      VARCHAR(60) NOT NULL UNIQUE,
  delta      INT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
