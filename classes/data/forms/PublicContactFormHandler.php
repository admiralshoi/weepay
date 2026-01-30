<?php

namespace classes\data\forms;

use classes\utility\Crud;
use Database\model\ContactFormSubmissions;

class PublicContactFormHandler extends Crud {

    function __construct() {
        parent::__construct(ContactFormSubmissions::newStatic(), "form_submissions");
    }


    public function qualifySubmission($email): bool {
        return !$this->queryBuilder()
            ->startGroup("OR")
                ->startGroup("AND")
                    ->where("email", $email)
                    ->whereTimeAfter("created_at", strtotime("-3 months"))
                ->endGroup()
                ->startGroup("AND")
                    ->whereColumnIsNotNull("_csrf")
                    ->where("_csrf", __csrf())
                ->endGroup()
            ->endGroup()
            ->exists();
    }

    public function insert(
        string $name,
        string $email,
        string $subject,
        string $content,
        bool $newsletterConsent = false,
    ): bool {
        return $this->create([
            "name" => $name,
            "email" => $email,
            "subject" => $subject,
            "content" => $content,
            "newsletter_consent" => (int)$newsletterConsent,
            "uuid" => isLoggedIn() ? __uuid() : null,
            "_csrf" => __csrf()
        ]);
    }

    /**
     * Get paginated list of submissions with optional search
     */
    public function getList(int $page = 1, int $perPage = 25, string $search = '', string $sortColumn = 'created_at', string $sortDirection = 'DESC'): array {
        $allowedSortColumns = ['created_at', 'name', 'email'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = $this->queryBuilder()
            ->select(['uid', 'name', 'email', 'subject', 'content', 'newsletter_consent', 'created_at']);

        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('email', $search)
                ->whereLike('subject', $search)
                ->endGroup();
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $submissions = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        return [
            'submissions' => $submissions,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ];
    }

    /**
     * Delete a submission by UID
     */
    public function deleteByUid(string $uid): bool {
        return $this->delete(['uid' => $uid]);
    }

}