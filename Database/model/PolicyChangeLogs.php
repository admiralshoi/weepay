<?php

namespace Database\model;

class PolicyChangeLogs extends \Database\Model {

    public static ?string $uidPrefix = "pcl";
    protected static array $schema = [
        "uid" => "string",
        "policy_version" => "string",
        "policy_type" => "string",
        "change_type" => ["type" => "enum", "values" => ["created", "updated", "published", "archived", "scheduled", "unscheduled"]],
        "changed_by" => ["type" => "string", "nullable" => true],
        "title_snapshot" => "string",
        "content_snapshot" => "text",
        "version_snapshot" => "integer",
    ];

    public static array $indexes = ["policy_version", "policy_type", "change_type"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "policy_type" => [PolicyTypes::tableColumn("uid"), PolicyTypes::newStatic()],
            "changed_by" => [Users::tableColumn("uid"), Users::newStatic()],
            // Note: policy_version FK to PolicyVersions added after that model is defined
        ];
    }
}
