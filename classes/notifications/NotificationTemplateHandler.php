<?php

namespace classes\notifications;

use classes\utility\Crud;
use Database\Collection;
use Database\model\NotificationTemplates;

class NotificationTemplateHandler extends Crud {

    function __construct() {
        parent::__construct(NotificationTemplates::newStatic(), "notification_templates");
    }

    public function getActive(?string $type = null): Collection {
        $params = ['status' => 'active'];
        if ($type) {
            $params['type'] = $type;
        }
        return $this->getByX($params);
    }

    public function getByType(string $type): Collection {
        return $this->getByX(['type' => $type]);
    }

    public function setActive(string $uid): bool {
        return $this->update(['status' => 'active'], ['uid' => $uid]);
    }

    public function setInactive(string $uid): bool {
        return $this->update(['status' => 'inactive'], ['uid' => $uid]);
    }

    public function insert(
        string $name,
        string $type,
        string $content,
        ?string $subject = null,
        ?string $htmlContent = null,
        ?array $placeholders = null,
        string $status = 'draft',
        ?string $createdBy = null,
        string $category = 'template',
        ?string $slug = null
    ): bool {
        return $this->create([
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'category' => $category,
            'subject' => $subject,
            'content' => $content,
            'html_content' => $htmlContent,
            'placeholders' => $placeholders,
            'status' => $status,
            'created_by' => $createdBy ?? __uuid(),
        ]);
    }

    public function getComponents(): Collection {
        return $this->getByX(['category' => 'component', 'status' => 'active']);
    }

    public function getBySlug(string $slug): ?object {
        return $this->getFirst(['slug' => $slug]);
    }
}
