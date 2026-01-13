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
        "status" => ["type" => "enum", "values" => ["active", "inactive", "draft"], "default" => "draft"],
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
            "status" => "active",
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
            "status" => "active",
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
            "status" => "active",
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
            "status" => "active",
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
