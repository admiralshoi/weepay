<?php

namespace classes\support;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\SupportTickets;

class SupportTicketHandler extends Crud {

    function __construct() {
        parent::__construct(SupportTickets::newStatic(), "support_tickets");
    }

    /**
     * Create a new support ticket
     */
    public function createTicket(array $data): ?string {
        if ($this->create($data)) {
            return $this->recentUid;
        }
        return null;
    }

    /**
     * Get all tickets for a specific user
     */
    public function getByUser(string $userUid): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['user' => $userUid]);
    }

    /**
     * Get all tickets by type (consumer/merchant)
     */
    public function getByType(string $type): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['type' => $type]);
    }

    /**
     * Get all open tickets (for admin)
     */
    public function getOpen(): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', ['status' => 'open']);
    }

    /**
     * Get all tickets (for admin)
     */
    public function getAll(): Collection {
        return $this->getByXOrderBy('created_at', 'DESC', []);
    }

    /**
     * Get tickets with filters and pagination (for admin)
     */
    public function getFiltered(array $filters = [], int $page = 1, int $perPage = 20, string $search = ''): array {
        $query = $this->queryBuilder();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Search in subject and message
        if (!empty($search)) {
            $query->startGroup('AND');
            $query->where('subject', 'LIKE', "%{$search}%");
            $query->orWhere('message', 'LIKE', "%{$search}%");
            $query->endGroup();
        }

        // Get total count before pagination
        $countQuery = clone $query;
        $total = $countQuery->count();

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $query->order('created_at', 'DESC');
        $query->limit($perPage);
        $query->offset($offset);

        $tickets = $this->queryGetAll($query);

        return [
            'tickets' => $tickets,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ]
        ];
    }

    /**
     * Close a ticket
     */
    public function closeTicket(string $uid, string $closedBy): bool {
        return $this->update([
            'status' => 'closed',
            'closed_at' => date('Y-m-d H:i:s'),
            'closed_by' => $closedBy
        ], ['uid' => $uid]);
    }

    /**
     * Reopen a ticket
     */
    public function reopenTicket(string $uid): bool {
        return $this->update([
            'status' => 'open',
            'closed_at' => null,
            'closed_by' => null
        ], ['uid' => $uid]);
    }

    /**
     * Delete a ticket and its replies
     */
    public function deleteTicket(string $uid): bool {
        debugLog("deleteTicket called with uid: $uid", "SUPPORT_DELETE");

        // First delete all replies for this ticket
        try {
            $repliesHandler = Methods::supportTicketReplies();
            debugLog("Got replies handler", "SUPPORT_DELETE");

            $deleteRepliesResult = $repliesHandler->deleteByTicket($uid);
            debugLog("Delete replies result: " . json_encode($deleteRepliesResult), "SUPPORT_DELETE");
        } catch (\Exception $e) {
            debugLog("Error deleting replies: " . $e->getMessage(), "SUPPORT_DELETE");
            return false;
        }

        // Then delete the ticket
        try {
            $deleteTicketResult = $this->delete(['uid' => $uid]);
            debugLog("Delete ticket result: " . json_encode($deleteTicketResult), "SUPPORT_DELETE");
            return $deleteTicketResult;
        } catch (\Exception $e) {
            debugLog("Error deleting ticket: " . $e->getMessage(), "SUPPORT_DELETE");
            return false;
        }
    }

    /**
     * Get ticket counts by status (for admin dashboard)
     */
    public function getCounts(): array {
        return [
            'open' => $this->count(['status' => 'open']),
            'closed' => $this->count(['status' => 'closed']),
            'total' => $this->count([]),
            'consumer' => $this->count(['type' => 'consumer']),
            'merchant' => $this->count(['type' => 'merchant']),
        ];
    }

}
