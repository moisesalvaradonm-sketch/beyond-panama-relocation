-- =====================================================================
-- Beyond Panama Relocation — seed data
-- Run AFTER schema.sql. Password for the admin user is set via install.php.
-- =====================================================================

-- Score rules (configurable later from admin)
INSERT INTO bpr_score_rules (event, delta) VALUES
  ('newsletter_signup',      5),
  ('quiz_completed',        15),
  ('quiz_result',            5),
  ('email_open',             2),
  ('email_click',            5),
  ('page_view',              1),
  ('page_view_residency',   10),
  ('page_view_property',    10),
  ('page_view_business',     8),
  ('page_view_retirement',   8),
  ('guide_download',        10),
  ('consultation_request',  25),
  ('whatsapp_click',        20),
  ('form_submit',           10),
  ('repeat_visit',           5),
  ('unsubscribe',          -50)
ON DUPLICATE KEY UPDATE delta = VALUES(delta);

-- Settings (edit from admin later)
INSERT INTO bpr_settings (name, value) VALUES
  ('business_name',    'Beyond Panama Relocation'),
  ('website_url',      'https://beyondpanamarelocation.com'),
  ('whatsapp_number',  '50760844256'),
  ('whatsapp_url',     'https://wa.me/50760844256'),
  ('consultation_url', 'https://beyondpanamarelocation.com/en/#contact'),
  ('sender_name',      'Beyond Panama Relocation'),
  ('sender_email',     'contact@beyondpanamarelocation.com'),
  ('reply_to',         'contact@beyondpanamarelocation.com'),
  ('default_language', 'en'),
  ('email_footer',     'Beyond Panama Relocation · Panama City, Panama · <a href="{{unsubscribe_url}}">Unsubscribe</a>'),
  ('ai_content_mode',  'manual')  -- manual | semi | full
ON DUPLICATE KEY UPDATE value = value;

-- Segments (dynamic)
INSERT INTO bpr_segments (slug, name, description, rule_json) VALUES
  ('retirement',    'Retirement leads',    'Interested in retiring in Panama',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','main_interest','op','eq','value','retirement'),
      JSON_OBJECT('field','interest','op','has','value','retirement')
    ))),
  ('residency',     'Residency leads',     'Interested in Panama residency / visas',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','main_interest','op','eq','value','residency'),
      JSON_OBJECT('field','interest','op','has','value','residency')
    ))),
  ('property',      'Property leads',      'Buying / renting / investing in property',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','main_interest','op','eq','value','property'),
      JSON_OBJECT('field','buying_status','op','in','value', JSON_ARRAY('buying','renting'))
    ))),
  ('business',      'Business leads',      'Business / entrepreneurship / remote work',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','main_interest','op','in','value',JSON_ARRAY('business','remote_work'))
    ))),
  ('panama_city',   'Panama City',         'Quiz result or location tag = Panama City',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','quiz_result','op','eq','value','panama_city'),
      JSON_OBJECT('field','location','op','has','value','panama_city')
    ))),
  ('boquete',       'Boquete',             'Quiz result or location tag = Boquete',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','quiz_result','op','eq','value','boquete'),
      JSON_OBJECT('field','location','op','has','value','boquete')
    ))),
  ('coronado',      'Coronado',            'Quiz result or location tag = Coronado',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','quiz_result','op','eq','value','coronado'),
      JSON_OBJECT('field','location','op','has','value','coronado')
    ))),
  ('ready_to_move', 'Ready to move',       'Timeline <= 3 months or ready',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','timeline','op','in','value',JSON_ARRAY('under_3_months','ready','3_6_months'))
    ))),
  ('long_term',     'Long term',           'Timeline 1-2 years or 2+ years',
    JSON_OBJECT('any', JSON_ARRAY(
      JSON_OBJECT('field','timeline','op','in','value',JSON_ARRAY('1_2_years','2plus_years','researching'))
    ))),
  ('engaged',       'Engaged leads',       'Score 21-50',
    JSON_OBJECT('all', JSON_ARRAY(
      JSON_OBJECT('field','lead_score','op','gte','value',21),
      JSON_OBJECT('field','lead_score','op','lte','value',50)
    ))),
  ('hot',           'Hot leads',           'Score 51+',
    JSON_OBJECT('all', JSON_ARRAY(
      JSON_OBJECT('field','lead_score','op','gte','value',51)
    )))
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Baseline knowledge base entries (REPLACE with your real, verified data)
INSERT INTO bpr_knowledge (topic, key_name, value_text, source, verified_at, confidence, language) VALUES
  ('company','name','Beyond Panama Relocation','internal',CURRENT_DATE,'high','any'),
  ('company','whatsapp','+507 6084-4256','internal',CURRENT_DATE,'high','any'),
  ('company','email','contact@beyondpanamarelocation.com','internal',CURRENT_DATE,'high','any'),
  ('company','services','Relocation guidance, residency support, property search, business setup, retirement planning.','internal',CURRENT_DATE,'high','any'),
  ('locations','panama_city','Cosmopolitan capital. Best for business, remote work, international schools, healthcare and city lifestyle. Hot & humid year-round.','internal',CURRENT_DATE,'high','any'),
  ('locations','boquete','Mountain town in Chiriqui. Cooler climate (~18-24C), expat community, coffee farms, outdoor lifestyle. Popular for retirement.','internal',CURRENT_DATE,'high','any'),
  ('locations','coronado','Beach town ~80 min from Panama City. Suburban, established expat community, gated communities, easy access to services.','internal',CURRENT_DATE,'high','any'),
  ('locations','bocas_del_toro','Caribbean archipelago. Laid-back, surf and island lifestyle, remote-work friendly, seasonal tourism.','internal',CURRENT_DATE,'high','any'),
  ('locations','pedasi','Small Pacific coast town in Los Santos. Slower pace, growing expat community, beaches.','internal',CURRENT_DATE,'high','any')
ON DUPLICATE KEY UPDATE value_text = VALUES(value_text);

-- Core automation: Quiz result nurture (6 emails over ~18 days)
INSERT INTO bpr_automations (slug, name, trigger_type, trigger_config, is_active) VALUES
  ('quiz_nurture', 'Quiz result nurture', 'quiz_completed', JSON_OBJECT(), 1),
  ('retirement_nurture', 'Retirement nurture', 'interest_added', JSON_OBJECT('interest','retirement'), 1),
  ('residency_nurture', 'Residency nurture', 'interest_added', JSON_OBJECT('interest','residency'), 1),
  ('property_nurture', 'Property nurture', 'interest_added', JSON_OBJECT('interest','property'), 1),
  ('business_nurture', 'Business nurture', 'interest_added', JSON_OBJECT('interest','business'), 1),
  ('reactivation', 'Stale lead reactivation', 'stale_days', JSON_OBJECT('days',90), 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Automation steps for quiz_nurture (references template slugs — templates come from templates seed below)
SET @a := (SELECT id FROM bpr_automations WHERE slug = 'quiz_nurture');
INSERT INTO bpr_automation_steps (automation_id, position, step_type, config_json) VALUES
  (@a, 10, 'send_email', JSON_OBJECT('template_slug','quiz_result_v1')),
  (@a, 20, 'wait',       JSON_OBJECT('hours', 48)),
  (@a, 30, 'send_email', JSON_OBJECT('template_slug','quiz_why_location_v1')),
  (@a, 40, 'wait',       JSON_OBJECT('hours', 72)),
  (@a, 50, 'send_email', JSON_OBJECT('template_slug','quiz_5_things_v1')),
  (@a, 60, 'wait',       JSON_OBJECT('hours', 72)),
  (@a, 70, 'send_email', JSON_OBJECT('template_slug','quiz_cost_v1')),
  (@a, 80, 'wait',       JSON_OBJECT('hours', 96)),
  (@a, 90, 'send_email', JSON_OBJECT('template_slug','quiz_first_trip_v1')),
  (@a,100, 'wait',       JSON_OBJECT('hours',144)),
  (@a,110, 'send_email', JSON_OBJECT('template_slug','quiz_book_consultation_v1')),
  (@a,120, 'end',        JSON_OBJECT());

-- Baseline templates (placeholders — will be replaced/rewritten by the AI engine)
INSERT INTO bpr_email_templates (slug, name, language, subject, preheader, body_html, status) VALUES
  ('quiz_result_v1', 'Quiz result — Panama match', 'en',
    '{{first_name}}, we found your Panama match',
    'Your personalised Panama location result is inside.',
    '<p>Hi {{first_name}},</p><p>Based on your answers, <strong>{{recommended_location}}</strong> came out as your best fit ({{quiz_match}}% match).</p><p>Here is why it may suit you and what to look at next.</p><p><a href="{{consultation_url}}">Book a free consultation</a> · <a href="{{whatsapp_url}}">Chat on WhatsApp</a></p>',
    'approved'),
  ('quiz_why_location_v1','Why your recommended location may fit','en',
    'Why {{recommended_location}} could work for you',
    'A closer look at the location the quiz recommended.',
    '<p>Hi {{first_name}},</p><p>Here are 3 concrete reasons {{recommended_location}} scored highest for you.</p>','approved'),
  ('quiz_5_things_v1','5 things to know before moving to Panama','en',
    '5 things nobody tells you before moving to Panama',
    'The practical stuff most guides skip.',
    '<p>Hi {{first_name}},</p><p>These are the 5 things our clients wish they had known earlier.</p>','approved'),
  ('quiz_cost_v1','How much does it cost to live in Panama?','en',
    'Real cost of living in Panama (with numbers)',
    'What a comfortable monthly budget actually looks like.',
    '<p>Hi {{first_name}},</p><p>Here is a realistic monthly budget breakdown for {{recommended_location}}.</p>','approved'),
  ('quiz_first_trip_v1','Planning your first trip to Panama','en',
    'How to plan your first scouting trip',
    'A simple 7-day itinerary framework.',
    '<p>Hi {{first_name}},</p><p>Use this framework to plan a scouting trip that actually helps you decide.</p>','approved'),
  ('quiz_book_consultation_v1','Want help planning your move?','en',
    'Ready to talk?',
    'Free 30-min consultation with a Panama relocation advisor.',
    '<p>Hi {{first_name}},</p><p>You have been researching Panama for a while now. If you would like a real conversation with someone who has helped others do the same, <a href="{{consultation_url}}">book a free consultation</a> or <a href="{{whatsapp_url}}">message us on WhatsApp</a>.</p>','approved'),
  ('reactivation_v1','Are you still thinking about Panama?','en',
    '{{first_name}}, still thinking about Panama?',
    'A quick check-in — nothing else.',
    '<p>Hi {{first_name}},</p><p>It has been a while. Are you still exploring the idea of moving to Panama? Reply with a single word — <em>yes</em>, <em>later</em> or <em>stop</em> — and we will adapt what we send you.</p>','approved')
ON DUPLICATE KEY UPDATE name = VALUES(name);
