<?php

namespace Database\model;

class NotificationTemplates extends \Database\Model {

    public static ?string $uidPrefix = "ntpl";

    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "slug" => ["type" => "string", "nullable" => true, "default" => null],
        "type" => ["type" => "enum", "values" => ["email", "sms", "bell"], "default" => "email"],
        "category" => ["type" => "enum", "values" => ["template", "component"], "default" => "template"],
        "subject" => ["type" => "string", "nullable" => true, "default" => null],
        "content" => "text",
        "html_content" => ["type" => "text", "nullable" => true, "default" => null],
        "placeholders" => ["type" => "text", "nullable" => true, "default" => null],
        "status" => ["type" => "enum", "values" => ["active", "inactive", "draft", "template"], "default" => "draft"],
        "created_by" => ["type" => "string", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["type", "category", "status", "created_by"];
    public static array $uniques = ["uid", "slug"];

    protected static array $requiredRows = [
        [
            "uid" => "ntpl_email_header",
            "name" => "E-mail Header",
            "slug" => "email_header",
            "type" => "email",
            "category" => "component",
            "subject" => null,
            "content" => "{{brand.name}}",
            "html_content" => '<div style="text-align: center; padding: 30px 20px; background: #f8f9fa; border-radius: 8px 8px 0 0;">
    {{brand.logo}}
</div>
<div style="padding: 30px 20px;">
    <h1 style="margin: 0 0 10px 0; font-size: 24px; color: #333;">{{email_title}}</h1>
</div>',
            "status" => "template",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_email_header_location",
            "name" => "E-mail Header (Location)",
            "slug" => "email_header_location",
            "type" => "email",
            "category" => "component",
            "subject" => null,
            "content" => "{{location.name}}\n\n{{email_title}}",
            "html_content" => '<div style="text-align: center; padding: 0; border-radius: 8px 8px 0 0; overflow: hidden;">
    {{location_hero_html}}
    <div style="padding: 20px; background: #f8f9fa;">
        <h2 style="margin: 0; font-size: 20px; color: #333;">{{location.name}}</h2>
    </div>
</div>
<div style="padding: 30px 20px;">
    <h1 style="margin: 0 0 10px 0; font-size: 24px; color: #333;">{{email_title}}</h1>
</div>',
            "status" => "template",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_email_content_start",
            "name" => "E-mail Content (Start)",
            "slug" => "email_content_start",
            "type" => "email",
            "category" => "component",
            "subject" => null,
            "content" => "",
            "html_content" => '<div style="padding: 0 20px 30px 20px; font-size: 16px; line-height: 1.6; color: #333;">',
            "status" => "template",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_email_content_end",
            "name" => "E-mail Content (Slut)",
            "slug" => "email_content_end",
            "type" => "email",
            "category" => "component",
            "subject" => null,
            "content" => "",
            "html_content" => '</div>',
            "status" => "template",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_email_footer",
            "name" => "E-mail Footer",
            "slug" => "email_footer",
            "type" => "email",
            "category" => "component",
            "subject" => null,
            "content" => "Med venlig hilsen,\n{{brand.name}}\n\n{{brand.company_name}}\n{{brand.company_address}}\nCVR: {{brand.cvr}}\n{{brand.email}} | {{brand.phone}}",
            "html_content" => '<div style="padding: 30px 20px; background: #f8f9fa; border-top: 1px solid #e0e0e0; border-radius: 0 0 8px 8px; text-align: center;">
    <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">Med venlig hilsen,<br><strong>{{brand.name}}</strong></p>
    <div style="font-size: 12px; color: #999; line-height: 1.5;">
        <p style="margin: 0;">{{brand.company_name}}</p>
        <p style="margin: 0;">{{brand.company_address}}</p>
        <p style="margin: 5px 0 0 0;">CVR: {{brand.cvr}}</p>
        <p style="margin: 10px 0 0 0;">
            <a href="mailto:{{brand.email}}" style="color: #666; text-decoration: none;">{{brand.email}}</a> |
            <a href="tel:{{brand.phone}}" style="color: #666; text-decoration: none;">{{brand.phone}}</a>
        </p>
    </div>
</div>',
            "status" => "template",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_email_footer_unsubscribe",
            "name" => "E-mail Footer (Afmeld)",
            "slug" => "email_footer_unsubscribe",
            "type" => "email",
            "category" => "component",
            "subject" => null,
            "content" => "Med venlig hilsen,\n{{brand.name}}\n\n{{brand.company_name}}\n{{brand.company_address}}\nCVR: {{brand.cvr}}\n{{brand.email}} | {{brand.phone}}\n\nDu modtager denne e-mail fordi du er tilmeldt vores nyhedsbrev.\nAfmeld nyhedsbrev: {{unsubscribe_link}}",
            "html_content" => '<div style="padding: 30px 20px; background: #f8f9fa; border-top: 1px solid #e0e0e0; border-radius: 0 0 8px 8px; text-align: center;">
    <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">Med venlig hilsen,<br><strong>{{brand.name}}</strong></p>
    <div style="font-size: 12px; color: #999; line-height: 1.5;">
        <p style="margin: 0;">{{brand.company_name}}</p>
        <p style="margin: 0;">{{brand.company_address}}</p>
        <p style="margin: 5px 0 0 0;">CVR: {{brand.cvr}}</p>
        <p style="margin: 10px 0 0 0;">
            <a href="mailto:{{brand.email}}" style="color: #666; text-decoration: none;">{{brand.email}}</a> |
            <a href="tel:{{brand.phone}}" style="color: #666; text-decoration: none;">{{brand.phone}}</a>
        </p>
    </div>
    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0; font-size: 11px; color: #aaa;">
        <p style="margin: 0;">Du modtager denne e-mail fordi du er tilmeldt vores nyhedsbrev.</p>
        <p style="margin: 5px 0 0 0;"><a href="{{unsubscribe_link}}" style="color: #999;">Afmeld nyhedsbrev</a></p>
    </div>
</div>',
            "status" => "template",
            "created_by" => null,
        ],

        // =====================================================
        // CONSUMER WELCOME TEMPLATES (user.registered)
        // =====================================================
        [
            "uid" => "ntpl_consumer_welcome_email",
            "name" => "Velkommen til forbruger (email)",
            "slug" => "consumer_welcome_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Velkommen til {{brand.name}}!",
            "content" => "Hej {{user.full_name}},

Velkommen til {{brand.name}}!

Din konto er nu oprettet og klar til brug. Med {{brand.name}} kan du:

- Betale nemt og sikkert hos vores tilsluttede forretninger
- Holde styr på dine betalinger og ordrer
- Vælge fleksible betalingsmuligheder

Log ind på din konto her: {{app.url}}

Har du spørgsmål? Kontakt os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong>Velkommen til {{brand.name}}!</strong></p>
<p>Din konto er nu oprettet og klar til brug. Med {{brand.name}} kan du:</p>
<ul style="padding-left: 20px; margin: 15px 0;">
    <li>Betale nemt og sikkert hos vores tilsluttede forretninger</li>
    <li>Holde styr på dine betalinger og ordrer</li>
    <li>Vælge fleksible betalingsmuligheder</li>
</ul>
<p style="text-align: center; margin: 25px 0;">
    <a href="{{app.url}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Log ind på din konto</a>
</p>
<p>Har du spørgsmål? Kontakt os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_consumer_welcome_sms",
            "name" => "Velkommen til forbruger (SMS)",
            "slug" => "consumer_welcome_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Velkommen til {{brand.name}}, {{user.first_name}}! Din konto er klar. Log ind: {{app.url}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_consumer_welcome_bell",
            "name" => "Velkommen til forbruger (bell)",
            "slug" => "consumer_welcome_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Velkommen til {{brand.name}}!",
            "content" => "Din konto er oprettet og klar til brug. Udforsk dine muligheder i dashboardet.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // ORDER CONTRACT TEMPLATES (order.completed)
        // =====================================================
        [
            "uid" => "ntpl_order_contract_email",
            "name" => "Ordrekontrakt (email)",
            "slug" => "order_contract_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din aftale er bekræftet - Ordre {{order.uid}}",
            "content" => "Hej {{user.full_name}},

Din ordre hos {{location.name}} er nu bekræftet.

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Beskrivelse: {{order.caption}}
Samlet beløb: {{payment_plan.total_amount_formatted}}
Lokation: {{location.name}}
Dato: {{order.created_datetime}}

BETALINGSPLAN
-------------
{{payment_plan.schedule_summary}}

Din kontrakt er vedhæftet denne email som PDF.

Se din ordre her: {{order_link}}

Har du spørgsmål? Kontakt {{location.name}} eller os på {{brand.email}}

{{viva_note}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Din ordre hos <strong>{{location.name}}</strong> er nu bekræftet.</p>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Samlet beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment_plan.total_amount_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Lokation:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dato:</td><td style="padding: 5px 0; text-align: right;">{{order.created_datetime}}</td></tr>
    </table>
</div>

<div style="background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FE5722;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Betalingsplan</h3>
    <pre style="margin: 0; font-family: inherit; white-space: pre-wrap; font-size: 14px; color: #555;">{{payment_plan.schedule_summary}}</pre>
</div>

<p><strong>Din kontrakt er vedhæftet denne email som PDF.</strong></p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{order_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se din ordre</a>
</p>

<p style="font-size: 13px; color: #666;">Har du spørgsmål? Kontakt {{location.name}} eller os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>

<p style="font-size: 12px; color: #999; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">{{viva_note}}</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_order_contract_sms",
            "name" => "Ordrekontrakt (SMS)",
            "slug" => "order_contract_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Hej {{user.full_name}}.\nKøbet hos {{location.name}} på {{payment_plan.total_amount_formatted}} er blevet godkendt, og den første betaling på {{payment_plan.first_amount_formatted}} er trukket. Hold øje med dit dashboard hos {{brand.url}} for at se hvornår næste betalinger forfalder. Du kan finde kontrakten mellem dig og {{location.name}} på dit dashboard eller i din email, hvis du har slået email-notifikationer til.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_order_contract_bell",
            "name" => "Ordrekontrakt (bell)",
            "slug" => "order_contract_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Ordre bekræftet",
            "content" => "Din ordre {{order.uid}} på {{payment_plan.total_amount_formatted}} hos {{location.name}} er bekræftet.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // PAYMENT DUE REMINDERS (payment.due_reminder)
        // =====================================================
        [
            "uid" => "ntpl_payment_reminder_5day_email",
            "name" => "Betalingspåmindelse 5 dage (email)",
            "slug" => "payment_reminder_5day_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Påmindelse: Betaling forfalder om 5 dage",
            "content" => "Hej {{user.full_name}},

Dette er en venlig påmindelse om, at din næste betaling forfalder om 5 dage.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Forfaldsdato: {{payment.due_date_formatted}}
Rate: {{payment.installment_number}} af {{payment_plan.total_installments}}
Ordre: {{order.caption}}
Forretning: {{location.name}}

Betal her: {{payment_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Dette er en venlig påmindelse om, at din næste betaling forfalder om <strong>5 dage</strong>.</p>

<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #1976d2; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.due_date_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rate:</td><td style="padding: 5px 0; text-align: right;">{{payment.installment_number}} af {{payment_plan.total_installments}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordre:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{payment_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Betal nu</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_reminder_1day_email",
            "name" => "Betalingspåmindelse 1 dag (email)",
            "slug" => "payment_reminder_1day_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Husk: Betaling forfalder i morgen!",
            "content" => "Hej {{user.full_name}},

Din betaling forfalder i morgen!

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Forfaldsdato: {{payment.due_date_formatted}}
Rate: {{payment.installment_number}} af {{payment_plan.total_installments}}
Ordre: {{order.caption}}
Forretning: {{location.name}}

Betal nu for at undgå forsinkelse: {{payment_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong style="color: #f57c00;">Din betaling forfalder i morgen!</strong></p>

<div style="background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f57c00;">
    <h3 style="margin: 0 0 15px 0; color: #e65100; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #e65100;">{{payment.due_date_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rate:</td><td style="padding: 5px 0; text-align: right;">{{payment.installment_number}} af {{payment_plan.total_installments}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordre:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{payment_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Betal nu</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_reminder_5day_sms",
            "name" => "Betalingspåmindelse 5 dage (SMS)",
            "slug" => "payment_reminder_5day_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Hej {{user.first_name}}, din betaling på {{payment.formatted_amount}} forfalder om 5 dage ({{payment.due_date_formatted}}). Betal her: {{payment_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_reminder_1day_sms",
            "name" => "Betalingspåmindelse 1 dag (SMS)",
            "slug" => "payment_reminder_1day_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "HUSK: Din betaling på {{payment.formatted_amount}} forfalder i morgen! Betal nu: {{payment_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_reminder_5day_bell",
            "name" => "Betalingspåmindelse 5 dage (bell)",
            "slug" => "payment_reminder_5day_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Betaling forfalder om 5 dage",
            "content" => "Din betaling på {{payment.formatted_amount}} til {{location.name}} forfalder {{payment.due_date_formatted}}.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_reminder_1day_bell",
            "name" => "Betalingspåmindelse 1 dag (bell)",
            "slug" => "payment_reminder_1day_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Betaling forfalder i morgen!",
            "content" => "Din betaling på {{payment.formatted_amount}} forfalder i morgen. Betal nu for at undgå forsinkelse.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // PAYMENT OVERDUE TEMPLATES (payment.overdue_reminder)
        // =====================================================
        [
            "uid" => "ntpl_payment_overdue_email",
            "name" => "Forfalden betaling (email)",
            "slug" => "payment_overdue_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Vigtig: Din betaling er forfalden",
            "content" => "Hej {{user.full_name}},

Din betaling er nu forfalden med {{days_overdue}} dag(e).

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Forfaldsdato: {{payment.due_date_formatted}}
Dage forsinket: {{days_overdue}}
Ordre: {{order.caption}}
Forretning: {{location.name}}

Betal venligst hurtigst muligt for at undgå yderligere konsekvenser.

Betal her: {{payment_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong style="color: #d32f2f;">Din betaling er nu forfalden med {{days_overdue}} dag(e).</strong></p>

<div style="background: #ffebee; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d32f2f;">
    <h3 style="margin: 0 0 15px 0; color: #c62828; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #d32f2f;">{{payment.due_date_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dage forsinket:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #d32f2f;">{{days_overdue}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordre:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p>Betal venligst hurtigst muligt for at undgå yderligere konsekvenser.</p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{payment_link}}" style="display: inline-block; padding: 12px 30px; background: #d32f2f; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Betal nu</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_overdue_sms",
            "name" => "Forfalden betaling (SMS)",
            "slug" => "payment_overdue_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "VIGTIGT: Din betaling på {{payment.formatted_amount}} er {{days_overdue}} dage forsinket. Betal nu: {{payment_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_overdue_bell",
            "name" => "Forfalden betaling (bell)",
            "slug" => "payment_overdue_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Betaling forfalden!",
            "content" => "Din betaling på {{payment.formatted_amount}} er {{days_overdue}} dage forsinket. Betal venligst hurtigst muligt.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // RYKKER 1 TEMPLATES (payment.rykker_1)
        // =====================================================
        [
            "uid" => "ntpl_rykker_1_email",
            "name" => "1. rykker (email)",
            "slug" => "rykker_1_email",
            "type" => "email",
            "category" => "template",
            "subject" => "1. rykker - Betaling mangler",
            "content" => "Kære {{user.full_name}},

1. RYKKER

Vi har endnu ikke modtaget din betaling, som forfaldt den {{payment.due_date_formatted}}.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Rykkergebyr: {{rykker.formatted_fee}}
Total at betale: {{payment.formatted_total_due}}
Forfaldsdato: {{payment.due_date_formatted}}
Dage forsinket: {{days_overdue}}
Ordrenummer: {{order.uid}}
Forretning: {{location.name}}

Vi beder dig venligst indbetale det skyldige beløb hurtigst muligt.

Betal her: {{payment_link}}

Har du allerede betalt, bedes du se bort fra denne påmindelse.

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Kære {{user.full_name}},</p>

<div style="background: #fff3e0; padding: 15px 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ff9800;">
    <h2 style="margin: 0; color: #e65100; font-size: 18px;">1. RYKKER</h2>
</div>

<p>Vi har endnu ikke modtaget din betaling, som forfaldt den <strong>{{payment.due_date_formatted}}</strong>.</p>

<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rykkergebyr:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #e65100;">{{rykker.formatted_fee}}</strong></td></tr>
        <tr style="border-top: 2px solid #ddd;"><td style="padding: 10px 0 5px 0; color: #333; font-weight: bold;">Total at betale:</td><td style="padding: 10px 0 5px 0; text-align: right; font-size: 16px;"><strong style="color: #e65100;">{{payment.formatted_total_due}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;">{{payment.due_date_formatted}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dage forsinket:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #e65100;">{{days_overdue}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;">{{order.uid}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p>Vi beder dig venligst indbetale det skyldige beløb hurtigst muligt.</p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{payment_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Betal nu</a>
</p>

<p style="font-size: 13px; color: #666;">Har du allerede betalt, bedes du se bort fra denne påmindelse.</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_rykker_1_sms",
            "name" => "1. rykker (SMS)",
            "slug" => "rykker_1_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "1. RYKKER: Din betaling mangler. Total at betale: {{payment.formatted_total_due}} (inkl. gebyr {{rykker.formatted_fee}}). Betal nu: {{payment_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_rykker_1_bell",
            "name" => "1. rykker (bell)",
            "slug" => "rykker_1_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "1. rykker",
            "content" => "Din betaling på {{payment.formatted_amount}} mangler. Betal venligst hurtigst muligt.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // RYKKER 2 TEMPLATES (payment.rykker_2)
        // =====================================================
        [
            "uid" => "ntpl_rykker_2_email",
            "name" => "2. rykker (email)",
            "slug" => "rykker_2_email",
            "type" => "email",
            "category" => "template",
            "subject" => "2. rykker - Handling påkrævet",
            "content" => "Kære {{user.full_name}},

2. RYKKER - HANDLING PÅKRÆVET

Trods tidligere påmindelse har vi stadig ikke modtaget din betaling.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Rykkergebyr: {{rykker.formatted_fee}}
Samlede rykkergebyrer: {{rykker.formatted_total_fees}}
Total at betale: {{payment.formatted_total_due}}
Forfaldsdato: {{payment.due_date_formatted}}
Dage forsinket: {{days_overdue}}
Ordrenummer: {{order.uid}}
Forretning: {{location.name}}

Vi gør opmærksom på, at manglende betaling kan medføre yderligere omkostninger og eventuel overdragelse til inkasso.

Betal omgående her: {{payment_link}}

Kontakt os hurtigst muligt, hvis du har spørgsmål eller ønsker at aftale en betalingsordning.

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Kære {{user.full_name}},</p>

<div style="background: #ffebee; padding: 15px 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f44336;">
    <h2 style="margin: 0; color: #c62828; font-size: 18px;">2. RYKKER - HANDLING PÅKRÆVET</h2>
</div>

<p>Trods tidligere påmindelse har vi stadig ikke modtaget din betaling.</p>

<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rykkergebyr:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #c62828;">{{rykker.formatted_fee}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Samlede rykkergebyrer:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #c62828;">{{rykker.formatted_total_fees}}</strong></td></tr>
        <tr style="border-top: 2px solid #ddd;"><td style="padding: 10px 0 5px 0; color: #333; font-weight: bold;">Total at betale:</td><td style="padding: 10px 0 5px 0; text-align: right; font-size: 16px;"><strong style="color: #c62828;">{{payment.formatted_total_due}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;">{{payment.due_date_formatted}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dage forsinket:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #c62828;">{{days_overdue}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;">{{order.uid}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p style="color: #c62828;"><strong>Vi gør opmærksom på, at manglende betaling kan medføre yderligere omkostninger og eventuel overdragelse til inkasso.</strong></p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{payment_link}}" style="display: inline-block; padding: 12px 30px; background: #d32f2f; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Betal omgående</a>
</p>

<p style="font-size: 13px; color: #666;">Kontakt os hurtigst muligt, hvis du har spørgsmål eller ønsker at aftale en betalingsordning.</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_rykker_2_sms",
            "name" => "2. rykker (SMS)",
            "slug" => "rykker_2_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "2. RYKKER: Total at betale: {{payment.formatted_total_due}} (inkl. gebyrer {{rykker.formatted_total_fees}}). Betal omgående: {{payment_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_rykker_2_bell",
            "name" => "2. rykker (bell)",
            "slug" => "rykker_2_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "2. rykker - Handling påkrævet",
            "content" => "Din betaling på {{payment.formatted_amount}} mangler stadig. Betal omgående for at undgå yderligere konsekvenser.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // RYKKER 3 / FINAL TEMPLATES (payment.rykker_final)
        // =====================================================
        [
            "uid" => "ntpl_rykker_3_email",
            "name" => "Sidste rykker / Inkassovarsel (email)",
            "slug" => "rykker_3_email",
            "type" => "email",
            "category" => "template",
            "subject" => "SIDSTE ADVARSEL - Inkassovarsel",
            "content" => "Kære {{user.full_name}},

SIDSTE ADVARSEL - INKASSOVARSEL

Dette er din sidste mulighed for at betale din udestående gæld, før sagen overdrages til inkasso.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Rykkergebyr: {{rykker.formatted_fee}}
Samlede rykkergebyrer: {{rykker.formatted_total_fees}}
TOTAL AT BETALE: {{payment.formatted_total_due}}
Forfaldsdato: {{payment.due_date_formatted}}
Dage forsinket: {{days_overdue}}
Ordrenummer: {{order.uid}}
Forretning: {{location.name}}

Hvis betalingen ikke modtages inden 7 dage, vil sagen uden yderligere varsel blive overdraget til inkasso. Dette vil medføre betydelige ekstraomkostninger for dig.

Betal omgående her: {{payment_link}}

Ønsker du at undgå inkasso, skal du betale det fulde beløb eller kontakte os for at aftale en betalingsordning.

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Kære {{user.full_name}},</p>

<div style="background: #b71c1c; padding: 15px 20px; border-radius: 8px; margin: 20px 0;">
    <h2 style="margin: 0; color: #fff; font-size: 18px;">SIDSTE ADVARSEL - INKASSOVARSEL</h2>
</div>

<p><strong>Dette er din sidste mulighed for at betale din udestående gæld, før sagen overdrages til inkasso.</strong></p>

<div style="background: #ffebee; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px solid #b71c1c;">
    <h3 style="margin: 0 0 15px 0; color: #b71c1c; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rykkergebyr:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #b71c1c;">{{rykker.formatted_fee}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Samlede rykkergebyrer:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #b71c1c;">{{rykker.formatted_total_fees}}</strong></td></tr>
        <tr style="border-top: 2px solid #b71c1c; background: #ffcdd2;"><td style="padding: 10px 0 5px 0; color: #b71c1c; font-weight: bold;">TOTAL AT BETALE:</td><td style="padding: 10px 0 5px 0; text-align: right; font-size: 18px;"><strong style="color: #b71c1c;">{{payment.formatted_total_due}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;">{{payment.due_date_formatted}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dage forsinket:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #b71c1c;">{{days_overdue}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;">{{order.uid}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p style="background: #fff8e1; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
    <strong>Hvis betalingen ikke modtages inden 7 dage, vil sagen uden yderligere varsel blive overdraget til inkasso.</strong> Dette vil medføre betydelige ekstraomkostninger for dig.
</p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{payment_link}}" style="display: inline-block; padding: 15px 40px; background: #b71c1c; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">BETAL NU</a>
</p>

<p style="font-size: 13px; color: #666;">Ønsker du at undgå inkasso, skal du betale det fulde beløb eller kontakte os for at aftale en betalingsordning.</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_rykker_3_sms",
            "name" => "Sidste rykker / Inkassovarsel (SMS)",
            "slug" => "rykker_3_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "INKASSOVARSEL: Total {{payment.formatted_total_due}} (inkl. gebyrer {{rykker.formatted_total_fees}}) skal betales NU. Sidste chance: {{payment_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_rykker_3_bell",
            "name" => "Sidste rykker / Inkassovarsel (bell)",
            "slug" => "rykker_3_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "INKASSOVARSEL",
            "content" => "Sidste chance: Betal {{payment.formatted_amount}} nu for at undgå inkasso.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // RYKKER CANCELLED
        // =====================================================
        [
            "uid" => "ntpl_rykker_cancelled_email",
            "name" => "Rykker annulleret (email)",
            "slug" => "rykker_cancelled_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din rykker er blevet annulleret",
            "content" => "Kære {{user.full_name}},

RYKKER ANNULLERET

Vi skriver for at bekræfte, at rykkeren på din betaling er blevet annulleret.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Forfaldsdato: {{payment.due_date_formatted}}
Ordrenummer: {{order.uid}}
Forretning: {{location.name}}

Hvis du har spørgsmål, er du velkommen til at kontakte forretningen.

Med venlig hilsen,
{{location.name}}",
            "html_content" => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%); padding: 30px; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Rykker Annulleret</h1>
    </div>
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none;">
        <p style="font-size: 16px; color: #333; margin-bottom: 20px;">Kære {{user.full_name}},</p>
        <div style="background: #e8f5e9; border-left: 4px solid #43a047; padding: 15px; margin-bottom: 20px;">
            <p style="margin: 0; color: #2e7d32; font-weight: bold;">Din rykker er blevet annulleret</p>
        </div>
        <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #333;">Betalingsdetaljer</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 8px 0; color: #666;">Beløb:</td><td style="padding: 8px 0; color: #333; font-weight: bold;">{{payment.formatted_amount}}</td></tr>
                <tr><td style="padding: 8px 0; color: #666;">Forfaldsdato:</td><td style="padding: 8px 0; color: #333;">{{payment.due_date_formatted}}</td></tr>
                <tr><td style="padding: 8px 0; color: #666;">Ordrenummer:</td><td style="padding: 8px 0; color: #333;">{{order.uid}}</td></tr>
                <tr><td style="padding: 8px 0; color: #666;">Forretning:</td><td style="padding: 8px 0; color: #333;">{{location.name}}</td></tr>
            </table>
        </div>
        <p style="font-size: 14px; color: #666;">Hvis du har spørgsmål, er du velkommen til at kontakte forretningen.</p>
        <p style="font-size: 14px; color: #333; margin-top: 30px;">Med venlig hilsen,<br><strong>{{location.name}}</strong></p>
    </div>
    <div style="background: #f5f5f5; padding: 20px; border-radius: 0 0 10px 10px; text-align: center;">
        <p style="margin: 0; font-size: 12px; color: #999;">{{viva_note}}</p>
    </div>
</div>',
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // CONSUMER ORDER CONFIRMATION (order.created)
        // =====================================================
        [
            "uid" => "ntpl_consumer_order_confirm_email",
            "name" => "Ordrebekræftelse forbruger (email)",
            "slug" => "consumer_order_confirm_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Ordrebekræftelse - {{order.uid}}",
            "content" => "Hej {{user.full_name}},

Tak for din ordre hos {{location.name}}!

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Beskrivelse: {{order.caption}}
Samlet beløb: {{payment_plan.total_amount_formatted}}
Lokation: {{location.name}}
Dato: {{order.created_datetime}}

BETALINGSPLAN
-------------
Første betaling: {{payment_plan.first_amount_formatted}}
{{payment_plan.schedule_summary}}

Se din ordre her: {{order_link}}

{{viva_note}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Tak for din ordre hos <strong>{{location.name}}</strong>!</p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Samlet beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment_plan.total_amount_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Lokation:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dato:</td><td style="padding: 5px 0; text-align: right;">{{order.created_datetime}}</td></tr>
    </table>
</div>

<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Betalingsplan</h3>
    <p style="margin: 0 0 10px 0;"><strong>Første betaling:</strong> {{payment_plan.first_amount_formatted}}</p>
    <pre style="margin: 0; font-family: inherit; white-space: pre-wrap; font-size: 14px; color: #555;">{{payment_plan.schedule_summary}}</pre>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{order_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se din ordre</a>
</p>

<p style="font-size: 12px; color: #999; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">{{viva_note}}</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_consumer_order_confirm_sms",
            "name" => "Ordrebekræftelse forbruger (SMS)",
            "slug" => "consumer_order_confirm_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Ordre {{order.uid}} oprettet hos {{location.name}}. Beløb: {{payment_plan.total_amount_formatted}}. Detaljer: {{order_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_consumer_order_confirm_bell",
            "name" => "Ordrebekræftelse forbruger (bell)",
            "slug" => "consumer_order_confirm_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Ny ordre oprettet",
            "content" => "Din ordre {{order.uid}} på {{payment_plan.total_amount_formatted}} hos {{location.name}} er oprettet.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // MERCHANT JOINED ORG (organisation.member_joined)
        // =====================================================
        [
            "uid" => "ntpl_merchant_joined_email",
            "name" => "Forhandler tilsluttet organisation (email)",
            "slug" => "merchant_joined_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Velkommen til {{organisation.name}}!",
            "content" => "Hej {{user.full_name}},

Du er nu en del af teamet hos {{organisation.name}}!

Du har nu adgang til:
- Dashboard og overblik
- Ordrehåndtering
- Betalingsoversigt
- Teamindstillinger

Log ind på dit dashboard her: {{dashboard_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong>Du er nu en del af teamet hos {{organisation.name}}!</strong></p>

<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #1976d2; font-size: 16px;">Du har nu adgang til:</h3>
    <ul style="margin: 0; padding-left: 20px; color: #555;">
        <li style="margin-bottom: 8px;">Dashboard og overblik</li>
        <li style="margin-bottom: 8px;">Ordrehåndtering</li>
        <li style="margin-bottom: 8px;">Betalingsoversigt</li>
        <li style="margin-bottom: 8px;">Teamindstillinger</li>
    </ul>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{dashboard_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Gå til dashboard</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_joined_bell",
            "name" => "Forhandler tilsluttet organisation (bell)",
            "slug" => "merchant_joined_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Velkommen til teamet!",
            "content" => "Du er nu en del af {{organisation.name}}. Gå til dashboardet for at komme i gang.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // MERCHANT ORDER RECEIVED (merchant.order_received)
        // =====================================================
        [
            "uid" => "ntpl_merchant_order_email",
            "name" => "Ny ordre til forhandler (email)",
            "slug" => "merchant_order_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Ny ordre modtaget - {{order.uid}}",
            "content" => "Hej,

I har modtaget en ny ordre!

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Kunde: {{user.full_name}}
Beløb: {{payment_plan.total_amount_formatted}}
Beskrivelse: {{order.caption}}
Lokation: {{location.name}}
Dato: {{order.created_datetime}}

BETALINGSPLAN
-------------
Antal rater: {{payment_plan.total_installments}}
Første betaling: {{payment_plan.first_amount_formatted}}

Se ordren i dashboardet: {{dashboard_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej,</p>
<p><strong style="color: #4caf50;">I har modtaget en ny ordre!</strong></p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Kunde:</td><td style="padding: 5px 0; text-align: right;">{{user.full_name}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong style="font-size: 16px;">{{payment_plan.total_amount_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Lokation:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Dato:</td><td style="padding: 5px 0; text-align: right;">{{order.created_datetime}}</td></tr>
    </table>
</div>

<div style="background: #f5f5f5; padding: 15px 20px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0;"><strong>Betalingsplan:</strong> {{payment_plan.total_installments}} rate(r) - Første betaling: {{payment_plan.first_amount_formatted}}</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{dashboard_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se ordren</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_order_sms",
            "name" => "Ny ordre til forhandler (SMS)",
            "slug" => "merchant_order_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Ny ordre! {{order.uid}} fra {{user.full_name}} på {{payment_plan.total_amount_formatted}}. Se: {{dashboard_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_order_bell",
            "name" => "Ny ordre til forhandler (bell)",
            "slug" => "merchant_order_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Ny ordre modtaget",
            "content" => "Ny ordre fra {{user.full_name}} på {{payment_plan.total_amount_formatted}}.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // POLICY UPDATE (system.policy_updated)
        // =====================================================
        [
            "uid" => "ntpl_policy_update_email",
            "name" => "Politikopdatering (email)",
            "slug" => "policy_update_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Opdatering af {{policy_name}}",
            "content" => "Hej {{user.full_name}},

Vi har opdateret vores {{policy_name}}.

ÆNDRINGSOVERSIGT
----------------
{{update_summary}}

Du kan læse den fulde version her: {{policy_link}}

Har du spørgsmål, er du velkommen til at kontakte os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Vi har opdateret vores <strong>{{policy_name}}</strong>.</p>

<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #1976d2; font-size: 16px;">Ændringsoversigt</h3>
    <p style="margin: 0; color: #555; white-space: pre-wrap;">{{update_summary}}</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{policy_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Læs den fulde version</a>
</p>

<p>Har du spørgsmål, er du velkommen til at kontakte os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // WEEKLY REPORT - ORGANISATION (report.weekly_organisation)
        // =====================================================
        [
            "uid" => "ntpl_weekly_report_org_email",
            "name" => "Ugentlig rapport organisation (email)",
            "slug" => "weekly_report_org_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Ugentlig rapport - {{organisation.name}}",
            "content" => "Hej {{user.full_name}},

Her er din ugentlige rapport for {{organisation.name}}.

PERIODE: {{report_period_start}} - {{report_period_end}}

OVERBLIK
--------
Antal ordrer: {{total_orders}}
Samlet omsætning: {{total_revenue_formatted}}
Gennemførte betalinger: {{completed_payments}}
Afventende betalinger: {{pending_payments}}

Din detaljerede rapport er vedhæftet som PDF.

Se mere i dashboardet: {{dashboard_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Her er din ugentlige rapport for <strong>{{organisation.name}}</strong>.</p>

<div style="background: #f5f5f5; padding: 15px 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <p style="margin: 0; color: #666; font-size: 14px;">Periode: <strong>{{report_period_start}} - {{report_period_end}}</strong></p>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 15px; margin: 20px 0;">
    <div style="flex: 1; min-width: 140px; background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 5px 0; color: #1976d2; font-size: 24px; font-weight: bold;">{{total_orders}}</p>
        <p style="margin: 0; color: #666; font-size: 12px;">Ordrer</p>
    </div>
    <div style="flex: 1; min-width: 140px; background: #e8f5e9; padding: 20px; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 5px 0; color: #2e7d32; font-size: 24px; font-weight: bold;">{{total_revenue_formatted}}</p>
        <p style="margin: 0; color: #666; font-size: 12px;">Omsætning</p>
    </div>
</div>

<table style="width: 100%; font-size: 14px; margin: 20px 0;">
    <tr><td style="padding: 10px 0; color: #666; border-bottom: 1px solid #eee;">Gennemførte betalinger:</td><td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #eee;"><strong>{{completed_payments}}</strong></td></tr>
    <tr><td style="padding: 10px 0; color: #666;">Afventende betalinger:</td><td style="padding: 10px 0; text-align: right;"><strong>{{pending_payments}}</strong></td></tr>
</table>

<p><strong>Din detaljerede rapport er vedhæftet som PDF.</strong></p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{dashboard_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Gå til dashboard</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_weekly_report_org_bell",
            "name" => "Ugentlig rapport organisation (bell)",
            "slug" => "weekly_report_org_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Ugentlig rapport klar",
            "content" => "Din ugentlige rapport for {{organisation.name}} er klar. Omsætning: {{total_revenue_formatted}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // WEEKLY REPORT - LOCATION (report.weekly_location)
        // =====================================================
        [
            "uid" => "ntpl_weekly_report_location_email",
            "name" => "Ugentlig rapport lokation (email)",
            "slug" => "weekly_report_location_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Ugentlig rapport - {{location.name}}",
            "content" => "Hej {{user.full_name}},

Her er din ugentlige rapport for {{location.name}} ({{organisation.name}}).

PERIODE: {{report_period_start}} - {{report_period_end}}

OVERBLIK
--------
Antal ordrer: {{total_orders}}
Samlet omsætning: {{total_revenue_formatted}}
Gennemførte betalinger: {{completed_payments}}
Afventende betalinger: {{pending_payments}}

Din detaljerede rapport er vedhæftet som PDF.

Se mere i dashboardet: {{dashboard_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Her er din ugentlige rapport for <strong>{{location.name}}</strong> ({{organisation.name}}).</p>

<div style="background: #f5f5f5; padding: 15px 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <p style="margin: 0; color: #666; font-size: 14px;">Periode: <strong>{{report_period_start}} - {{report_period_end}}</strong></p>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 15px; margin: 20px 0;">
    <div style="flex: 1; min-width: 140px; background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 5px 0; color: #1976d2; font-size: 24px; font-weight: bold;">{{total_orders}}</p>
        <p style="margin: 0; color: #666; font-size: 12px;">Ordrer</p>
    </div>
    <div style="flex: 1; min-width: 140px; background: #e8f5e9; padding: 20px; border-radius: 8px; text-align: center;">
        <p style="margin: 0 0 5px 0; color: #2e7d32; font-size: 24px; font-weight: bold;">{{total_revenue_formatted}}</p>
        <p style="margin: 0; color: #666; font-size: 12px;">Omsætning</p>
    </div>
</div>

<table style="width: 100%; font-size: 14px; margin: 20px 0;">
    <tr><td style="padding: 10px 0; color: #666; border-bottom: 1px solid #eee;">Gennemførte betalinger:</td><td style="padding: 10px 0; text-align: right; border-bottom: 1px solid #eee;"><strong>{{completed_payments}}</strong></td></tr>
    <tr><td style="padding: 10px 0; color: #666;">Afventende betalinger:</td><td style="padding: 10px 0; text-align: right;"><strong>{{pending_payments}}</strong></td></tr>
</table>

<p><strong>Din detaljerede rapport er vedhæftet som PDF.</strong></p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{dashboard_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Gå til dashboard</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_weekly_report_location_bell",
            "name" => "Ugentlig rapport lokation (bell)",
            "slug" => "weekly_report_location_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Ugentlig rapport klar",
            "content" => "Din ugentlige rapport for {{location.name}} er klar. Omsætning: {{total_revenue_formatted}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // ORG INVITE (organisation.member_invited)
        // =====================================================
        [
            "uid" => "ntpl_org_invite_email",
            "name" => "Invitation til organisation (email)",
            "slug" => "org_invite_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Du er inviteret til {{organisation.name}}",
            "content" => "Hej,

{{inviter.full_name}} har inviteret dig til at blive en del af {{organisation.name}} på {{brand.name}}.

Log ind eller opret en konto for at se og acceptere invitationen: {{invite_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej,</p>
<p><strong>{{inviter.full_name}}</strong> har inviteret dig til at blive en del af <strong>{{organisation.name}}</strong> på {{brand.name}}.</p>

<div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
    <p style="margin: 0 0 15px 0; font-size: 18px; color: #1976d2;">Du er inviteret!</p>
    <p style="margin: 0; color: #666;">Log ind eller opret en konto for at se og acceptere invitationen.</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{invite_link}}" style="display: inline-block; padding: 15px 40px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">Log ind</a>
</p>

<p style="font-size: 13px; color: #666;">Hvis du ikke kender afsenderen, kan du ignorere denne email.</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_org_invite_sms",
            "name" => "Invitation til organisation (SMS)",
            "slug" => "org_invite_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "{{inviter.full_name}} har inviteret dig til {{organisation.name}}. Log ind for at acceptere: {{invite_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // MERCHANT ORG READY (merchant.org_ready)
        // =====================================================
        [
            "uid" => "ntpl_merchant_org_ready_email",
            "name" => "Forretningskonto klar (email)",
            "slug" => "merchant_org_ready_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din {{brand.name}}-konto er klar!",
            "content" => "Hej {{user.full_name}},

Tillykke! Din forretningskonto for {{organisation.name}} er nu fuldt opsat og klar til brug.

Du kan nu:
- Oprette ordrer og betalingsaftaler
- Modtage betalinger fra kunder
- Se overblik over salg og betalinger
- Administrere dit team

Kom i gang nu: {{dashboard_link}}

Har du spørgsmål? Kontakt os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong style="color: #4caf50;">Tillykke! Din forretningskonto for {{organisation.name}} er nu fuldt opsat og klar til brug.</strong></p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Du kan nu:</h3>
    <ul style="margin: 0; padding-left: 20px; color: #555;">
        <li style="margin-bottom: 8px;">Oprette ordrer og betalingsaftaler</li>
        <li style="margin-bottom: 8px;">Modtage betalinger fra kunder</li>
        <li style="margin-bottom: 8px;">Se overblik over salg og betalinger</li>
        <li style="margin-bottom: 8px;">Administrere dit team</li>
    </ul>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{dashboard_link}}" style="display: inline-block; padding: 15px 40px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">Kom i gang nu</a>
</p>

<p>Har du spørgsmål? Kontakt os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_org_ready_sms",
            "name" => "Forretningskonto klar (SMS)",
            "slug" => "merchant_org_ready_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Din {{brand.name}}-konto for {{organisation.name}} er klar! Start nu: {{dashboard_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_org_ready_bell",
            "name" => "Forretningskonto klar (bell)",
            "slug" => "merchant_org_ready_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Konto klar!",
            "content" => "Din forretningskonto for {{organisation.name}} er nu klar til brug.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // VIVA APPROVED (merchant.viva_approved)
        // =====================================================
        [
            "uid" => "ntpl_merchant_viva_approved_email",
            "name" => "Viva godkendelse (email)",
            "slug" => "merchant_viva_approved_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Viva har godkendt din forretning!",
            "content" => "Hej {{user.full_name}},

Gode nyheder! Viva har nu godkendt {{organisation.name}} til betalingsbehandling.

Dette betyder at du nu kan:
- Modtage kortbetalinger fra kunder
- Behandle betalinger via alle understøttede betalingsmetoder
- Se transaktioner i realtid

Din forretning er nu fuldt aktiveret og klar til at modtage betalinger.

Gå til dashboard: {{dashboard_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong style="color: #4caf50;">Gode nyheder! Viva har nu godkendt {{organisation.name}} til betalingsbehandling.</strong></p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Dette betyder at du nu kan:</h3>
    <ul style="margin: 0; padding-left: 20px; color: #555;">
        <li style="margin-bottom: 8px;">Modtage kortbetalinger fra kunder</li>
        <li style="margin-bottom: 8px;">Behandle betalinger via alle understøttede betalingsmetoder</li>
        <li style="margin-bottom: 8px;">Se transaktioner i realtid</li>
    </ul>
</div>

<p><strong>Din forretning er nu fuldt aktiveret og klar til at modtage betalinger.</strong></p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{dashboard_link}}" style="display: inline-block; padding: 15px 40px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">Gå til dashboard</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_viva_approved_sms",
            "name" => "Viva godkendelse (SMS)",
            "slug" => "merchant_viva_approved_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Tillykke! Viva har godkendt {{organisation.name}}. Du kan nu modtage betalinger. {{dashboard_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_merchant_viva_approved_bell",
            "name" => "Viva godkendelse (bell)",
            "slug" => "merchant_viva_approved_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Viva godkendt!",
            "content" => "Viva har godkendt {{organisation.name}}. Du kan nu modtage betalinger.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // PAYMENT SUCCESSFUL (payment.successful)
        // =====================================================
        [
            "uid" => "ntpl_payment_success_email",
            "name" => "Betaling gennemført (email)",
            "slug" => "payment_success_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Betaling modtaget - {{payment.formatted_amount}}",
            "content" => "Hej {{user.full_name}},

Vi har modtaget din betaling.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Betalt: {{payment.paid_date}} kl. {{payment.paid_time}}
Rate: {{payment.installment_number}} af {{payment_plan.total_installments}}
Ordre: {{order.caption}}
Forretning: {{location.name}}

Resterende beløb: {{payment_plan.remaining_amount_formatted}}

Se din kvittering her: {{receipt_link}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong style="color: #4caf50;">Vi har modtaget din betaling.</strong></p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #2e7d32; font-size: 18px;">{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Betalt:</td><td style="padding: 5px 0; text-align: right;">{{payment.paid_date}} kl. {{payment.paid_time}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rate:</td><td style="padding: 5px 0; text-align: right;">{{payment.installment_number}} af {{payment_plan.total_installments}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordre:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<p><strong>Resterende beløb:</strong> {{payment_plan.remaining_amount_formatted}}</p>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{receipt_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se kvittering</a>
</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_success_sms",
            "name" => "Betaling gennemført (SMS)",
            "slug" => "payment_success_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Betaling på {{payment.formatted_amount}} modtaget. Rest: {{payment_plan.remaining_amount_formatted}}. Kvittering: {{receipt_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_success_bell",
            "name" => "Betaling gennemført (bell)",
            "slug" => "payment_success_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Betaling modtaget",
            "content" => "Din betaling på {{payment.formatted_amount}} er gennemført. Resterende: {{payment_plan.remaining_amount_formatted}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // PAYMENT FAILED (payment.failed)
        // =====================================================
        [
            "uid" => "ntpl_payment_failed_email",
            "name" => "Betaling fejlet (email)",
            "slug" => "payment_failed_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Betaling kunne ikke gennemføres",
            "content" => "Hej {{user.full_name}},

Din betaling kunne desværre ikke gennemføres.

BETALINGSDETALJER
-----------------
Beløb: {{payment.formatted_amount}}
Forfaldsdato: {{payment.due_date_formatted}}
Ordre: {{order.uid}}
Forretning: {{location.name}}

Årsag: {{failure_reason}}

Prøv venligst igen her: {{retry_link}}

Kontakt os på {{brand.email}} hvis du har spørgsmål.

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p><strong style="color: #d32f2f;">Din betaling kunne desværre ikke gennemføres.</strong></p>

<div style="background: #ffebee; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d32f2f;">
    <h3 style="margin: 0 0 15px 0; color: #c62828; font-size: 16px;">Betalingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Beløb:</td><td style="padding: 5px 0; text-align: right;"><strong>{{payment.formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;">{{payment.due_date_formatted}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordre:</td><td style="padding: 5px 0; text-align: right;">{{order.uid}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
    <p style="margin: 15px 0 0 0; padding: 10px; background: #fff; border-radius: 4px; color: #c62828;"><strong>Årsag:</strong> {{failure_reason}}</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{retry_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Prøv igen</a>
</p>

<p>Kontakt os på <a href="mailto:{{brand.email}}">{{brand.email}}</a> hvis du har spørgsmål.</p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_failed_sms",
            "name" => "Betaling fejlet (SMS)",
            "slug" => "payment_failed_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Betaling på {{payment.formatted_amount}} fejlede. Prøv igen: {{retry_link}}",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_failed_bell",
            "name" => "Betaling fejlet (bell)",
            "slug" => "payment_failed_bell",
            "type" => "bell",
            "category" => "template",
            "subject" => "Betaling fejlet",
            "content" => "Din betaling på {{payment.formatted_amount}} kunne ikke gennemføres. Prøv venligst igen.",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // PASSWORD RESET (user.password_reset)
        // =====================================================
        [
            "uid" => "ntpl_password_reset_email",
            "name" => "Nulstil adgangskode (email)",
            "slug" => "password_reset_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Nulstil din adgangskode",
            "content" => "Hej {{user.full_name}},

Du har anmodet om at nulstille din adgangskode.

Klik her for at oprette en ny adgangskode: {{reset_link}}

Linket er gyldigt i 24 timer.

Hvis du ikke har anmodet om dette, kan du ignorere denne email. Din adgangskode forbliver uændret.

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Du har anmodet om at nulstille din adgangskode.</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="{{reset_link}}" style="display: inline-block; padding: 15px 40px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">Nulstil adgangskode</a>
</p>

<p style="text-align: center; color: #666; font-size: 13px;">Linket er gyldigt i 24 timer.</p>

<div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin-top: 25px; border-left: 4px solid #ff9800;">
    <p style="margin: 0; color: #666; font-size: 13px;"><strong>Sikkerhedsnotits:</strong> Hvis du ikke har anmodet om dette, kan du ignorere denne email. Din adgangskode forbliver uændret.</p>
</div>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_password_reset_sms",
            "name" => "Nulstil adgangskode (SMS)",
            "slug" => "password_reset_sms",
            "type" => "sms",
            "category" => "template",
            "subject" => null,
            "content" => "Nulstil din {{brand.name}} adgangskode her: {{reset_link}} (gyldig i 24 timer)",
            "html_content" => null,
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // PAYMENT REFUND TEMPLATES (payment.refunded)
        // =====================================================
        [
            "uid" => "ntpl_payment_refund_bnpl_email",
            "name" => "Betaling refunderet - BNPL (email)",
            "slug" => "payment_refund_bnpl_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din rate er refunderet - Ordre {{order.uid}}",
            "content" => "Hej {{user.full_name}},

Vi bekræfter hermed, at din rate er blevet refunderet.

REFUNDERINGSDETALJER
--------------------
Refunderet beløb: {{refund_formatted_amount}}
Original rate: {{payment.formatted_amount}}
Rate nummer: {{payment.installment_number}}
Forfaldsdato: {{payment.due_date_formatted}}
Refunderet dato: {{refund_datetime}}

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Beskrivelse: {{order.caption}}
Forretning: {{location.name}}

Beløbet vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.

Se din ordre her: {{order_link}}

Har du spørgsmål? Kontakt {{location.name}} eller os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Vi bekræfter hermed, at din rate er blevet <strong>refunderet</strong>.</p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Refunderingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Refunderet beløb:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #2e7d32;">{{refund_formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Original rate:</td><td style="padding: 5px 0; text-align: right;">{{payment.formatted_amount}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rate nummer:</td><td style="padding: 5px 0; text-align: right;">{{payment.installment_number}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forfaldsdato:</td><td style="padding: 5px 0; text-align: right;">{{payment.due_date_formatted}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Refunderet dato:</td><td style="padding: 5px 0; text-align: right;">{{refund_datetime}}</td></tr>
    </table>
</div>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800;">
    <p style="margin: 0; color: #666; font-size: 13px;"><strong>Bemærk:</strong> Beløbet vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{order_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se din ordre</a>
</p>

<p style="font-size: 13px; color: #666;">Har du spørgsmål? Kontakt {{location.name}} eller os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_payment_refund_direct_email",
            "name" => "Betaling refunderet - Direkte (email)",
            "slug" => "payment_refund_direct_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din betaling er refunderet - Ordre {{order.uid}}",
            "content" => "Hej {{user.full_name}},

Vi bekræfter hermed, at din betaling er blevet refunderet.

REFUNDERINGSDETALJER
--------------------
Refunderet beløb: {{refund_formatted_amount}}
Original betaling: {{payment.formatted_amount}}
Betalingsdato: {{payment.due_date_formatted}}
Refunderet dato: {{refund_datetime}}

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Beskrivelse: {{order.caption}}
Forretning: {{location.name}}

Beløbet vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.

Se din ordre her: {{order_link}}

Har du spørgsmål? Kontakt {{location.name}} eller os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Vi bekræfter hermed, at din betaling er blevet <strong>refunderet</strong>.</p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Refunderingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Refunderet beløb:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #2e7d32;">{{refund_formatted_amount}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Original betaling:</td><td style="padding: 5px 0; text-align: right;">{{payment.formatted_amount}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Betalingsdato:</td><td style="padding: 5px 0; text-align: right;">{{payment.due_date_formatted}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Refunderet dato:</td><td style="padding: 5px 0; text-align: right;">{{refund_datetime}}</td></tr>
    </table>
</div>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
    </table>
</div>

<div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800;">
    <p style="margin: 0; color: #666; font-size: 13px;"><strong>Bemærk:</strong> Beløbet vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{order_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se din ordre</a>
</p>

<p style="font-size: 13px; color: #666;">Har du spørgsmål? Kontakt {{location.name}} eller os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],

        // =====================================================
        // ORDER REFUND TEMPLATES (order.refunded)
        // =====================================================
        [
            "uid" => "ntpl_order_refund_bnpl_email",
            "name" => "Ordre refunderet - BNPL (email)",
            "slug" => "order_refund_bnpl_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din ordre er refunderet - {{order.uid}}",
            "content" => "Hej {{user.full_name}},

Vi bekræfter hermed, at din ordre er blevet refunderet.

REFUNDERINGSDETALJER
--------------------
Samlet refunderet: {{total_refunded_formatted}}
Rater refunderet: {{payments_refunded_count}}
Rater ophævet: {{payments_voided_count}}
Refunderet dato: {{refund_datetime}}

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Beskrivelse: {{order.caption}}
Samlet ordrebeløb: {{order.formatted_amount}}
Forretning: {{location.name}}
Ordredato: {{order.created_date}}

RATEOVERSIGT
------------
{{payments_list_text}}

Det refunderede beløb vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.

Se din ordre her: {{order_link}}

Har du spørgsmål? Kontakt {{location.name}} eller os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Vi bekræfter hermed, at din ordre er blevet <strong>refunderet</strong>.</p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Refunderingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Samlet refunderet:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #2e7d32;">{{total_refunded_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rater refunderet:</td><td style="padding: 5px 0; text-align: right;">{{payments_refunded_count}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Rater ophævet:</td><td style="padding: 5px 0; text-align: right;">{{payments_voided_count}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Refunderet dato:</td><td style="padding: 5px 0; text-align: right;">{{refund_datetime}}</td></tr>
    </table>
</div>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Samlet ordrebeløb:</td><td style="padding: 5px 0; text-align: right;">{{order.formatted_amount}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordredato:</td><td style="padding: 5px 0; text-align: right;">{{order.created_date}}</td></tr>
    </table>
</div>

<div style="background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #e0e0e0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Rateoversigt</h3>
    {{payments_list_html}}
</div>

<div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800;">
    <p style="margin: 0; color: #666; font-size: 13px;"><strong>Bemærk:</strong> Det refunderede beløb vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{order_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se din ordre</a>
</p>

<p style="font-size: 13px; color: #666;">Har du spørgsmål? Kontakt {{location.name}} eller os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
        [
            "uid" => "ntpl_order_refund_direct_email",
            "name" => "Ordre refunderet - Direkte (email)",
            "slug" => "order_refund_direct_email",
            "type" => "email",
            "category" => "template",
            "subject" => "Din ordre er refunderet - {{order.uid}}",
            "content" => "Hej {{user.full_name}},

Vi bekræfter hermed, at din ordre er blevet refunderet.

REFUNDERINGSDETALJER
--------------------
Refunderet beløb: {{total_refunded_formatted}}
Refunderet dato: {{refund_datetime}}

ORDREDETALJER
-------------
Ordrenummer: {{order.uid}}
Beskrivelse: {{order.caption}}
Ordrebeløb: {{order.formatted_amount}}
Forretning: {{location.name}}
Ordredato: {{order.created_date}}

Beløbet vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.

Se din ordre her: {{order_link}}

Har du spørgsmål? Kontakt {{location.name}} eller os på {{brand.email}}

Med venlig hilsen,
{{brand.name}}",
            "html_content" => '{{template.email_header_location}}
{{template.email_content_start}}
<p>Hej {{user.full_name}},</p>
<p>Vi bekræfter hermed, at din ordre er blevet <strong>refunderet</strong>.</p>

<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">Refunderingsdetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Refunderet beløb:</td><td style="padding: 5px 0; text-align: right;"><strong style="color: #2e7d32;">{{total_refunded_formatted}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Refunderet dato:</td><td style="padding: 5px 0; text-align: right;">{{refund_datetime}}</td></tr>
    </table>
</div>

<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Ordredetaljer</h3>
    <table style="width: 100%; font-size: 14px;">
        <tr><td style="padding: 5px 0; color: #666;">Ordrenummer:</td><td style="padding: 5px 0; text-align: right;"><strong>{{order.uid}}</strong></td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Beskrivelse:</td><td style="padding: 5px 0; text-align: right;">{{order.caption}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordrebeløb:</td><td style="padding: 5px 0; text-align: right;">{{order.formatted_amount}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Forretning:</td><td style="padding: 5px 0; text-align: right;">{{location.name}}</td></tr>
        <tr><td style="padding: 5px 0; color: #666;">Ordredato:</td><td style="padding: 5px 0; text-align: right;">{{order.created_date}}</td></tr>
    </table>
</div>

<div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800;">
    <p style="margin: 0; color: #666; font-size: 13px;"><strong>Bemærk:</strong> Beløbet vil blive tilbageført til dit betalingskort inden for 5-10 hverdage.</p>
</div>

<p style="text-align: center; margin: 25px 0;">
    <a href="{{order_link}}" style="display: inline-block; padding: 12px 30px; background: #FE5722; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">Se din ordre</a>
</p>

<p style="font-size: 13px; color: #666;">Har du spørgsmål? Kontakt {{location.name}} eller os på <a href="mailto:{{brand.email}}">{{brand.email}}</a></p>
{{template.email_content_end}}
{{template.email_footer}}',
            "status" => "active",
            "created_by" => null,
        ],
    ];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["placeholders"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "created_by" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }
}
