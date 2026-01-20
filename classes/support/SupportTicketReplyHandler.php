<?php

namespace classes\support;

use classes\utility\Crud;
use Database\Collection;
use Database\model\SupportTicketReplies;

class SupportTicketReplyHandler extends Crud {

    function __construct() {
        parent::__construct(SupportTicketReplies::newStatic(), "support_ticket_replies");
    }

    /**
     * Add a reply to a ticket
     */
    public function addReply(string $ticketUid, string $userUid, string $message, bool $isAdmin = false): ?string {
        $data = [
            'ticket' => $ticketUid,
            'user' => $userUid,
            'message' => $message,
            'is_admin' => $isAdmin ? 1 : 0
        ];

        if ($this->create($data)) {
            return $this->recentUid;
        }
        return null;
    }

    /**
     * Get all replies for a ticket
     */
    public function getByTicket(string $ticketUid): Collection {
        return $this->getByXOrderBy('created_at', 'ASC', ['ticket' => $ticketUid]);
    }

    /**
     * Get reply count for a ticket
     */
    public function countByTicket(string $ticketUid): int {
        return $this->count(['ticket' => $ticketUid]);
    }

    /**
     * Delete all replies for a ticket (used when deleting ticket)
     */
    public function deleteByTicket(string $ticketUid): bool {
        debugLog("deleteByTicket called with ticketUid: $ticketUid", "SUPPORT_DELETE_REPLIES");

        // First count how many replies exist
        $count = $this->count(['ticket' => $ticketUid]);
        debugLog("Found $count replies to delete", "SUPPORT_DELETE_REPLIES");

        if ($count === 0) {
            debugLog("No replies to delete, returning true", "SUPPORT_DELETE_REPLIES");
            return true;
        }

        try {
            $result = $this->delete(['ticket' => $ticketUid]);
            debugLog("Delete replies result: " . json_encode($result), "SUPPORT_DELETE_REPLIES");
            return $result;
        } catch (\Exception $e) {
            debugLog("Error deleting replies: " . $e->getMessage(), "SUPPORT_DELETE_REPLIES");
            return false;
        }
    }

}
