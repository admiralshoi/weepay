<?php
/**
 * Admin Support Ticket Detail
 * @var object $args
 */

use classes\enumerations\Links;

$ticket = $args->ticket ?? null;
$replies = $args->replies ?? new \Database\Collection();

if (!$ticket) {
    return;
}

$pageTitle = "Support Sag #" . substr($ticket->uid, 0, 8);

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "support";
    var currentTicketUid = <?=json_encode($ticket->uid)?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->support)?>" class="color-gray font-14 hover-color-blue">
                    <i class="mdi mdi-arrow-left"></i> Tilbage til oversigt
                </a>
            </div>

            <?php if($ticket->status === 'closed'): ?>
            <!-- Closed Banner -->
            <div class="alert-box danger-alert flex-row-start flex-align-center" style="gap: .75rem; padding: 1rem 1.25rem;">
                <i class="mdi mdi-lock font-24"></i>
                <div class="flex-col-start">
                    <span class="font-16 font-weight-bold">Denne sag er lukket</span>
                    <?php if(!isEmpty($ticket->closed_at)): ?>
                    <span class="font-13">Lukket d. <?=date('d/m/Y H:i', strtotime($ticket->closed_at))?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ticket Header -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-start flex-wrap" style="gap: 1.5rem;">
                        <div class="flex-col-start" style="gap: .5rem;">
                            <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                <h1 class="mb-0 font-24 font-weight-bold"><?=htmlspecialchars($ticket->subject)?></h1>
                                <span class="<?=$ticket->status === 'open' ? 'warning-box' : 'danger-box'?>">
                                    <?=$ticket->status === 'open' ? 'Åben' : 'Lukket'?>
                                </span>
                            </div>
                            <p class="mb-0 font-14 color-gray">
                                <i class="mdi mdi-tag-outline"></i> <?=htmlspecialchars($ticket->category)?>
                                &nbsp;&middot;&nbsp;
                                <i class="mdi mdi-account-outline"></i> <?=$ticket->type === 'merchant' ? 'Forhandler' : 'Forbruger'?>
                            </p>
                        </div>

                        <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                            <?php if($ticket->status === 'open'): ?>
                                <button type="button" class="btn-v2 trans-btn" onclick="closeTicket('<?=$ticket->uid?>')">
                                    <i class="mdi mdi-check-circle"></i>
                                    <span>Luk sag</span>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-v2 trans-btn" onclick="reopenTicket('<?=$ticket->uid?>')">
                                    <i class="mdi mdi-refresh"></i>
                                    <span>Genåbn sag</span>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn-v2 danger-btn" onclick="deleteTicket('<?=$ticket->uid?>')">
                                <i class="mdi mdi-trash-can"></i>
                                <span>Slet</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Conversation Thread -->
                <div class="col-12 col-lg-8">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                                <i class="mdi mdi-message-text-outline font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Samtale</p>
                            </div>

                            <div class="ticket-messages-admin flex-col-start" style="gap: 1rem;" id="ticketMessages">
                                <!-- Original message -->
                                <div class="ticket-message user-message">
                                    <div class="message-header">
                                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                            <div class="square-30 bg-blue border-radius-50 flex-row-center-center">
                                                <i class="mdi mdi-account color-white font-14"></i>
                                            </div>
                                            <span class="font-14 font-weight-medium"><?=htmlspecialchars($ticket->user->full_name ?? 'Bruger')?></span>
                                            <span class="font-12 color-gray"><?=date('d/m/Y H:i', strtotime($ticket->created_at))?></span>
                                        </div>
                                    </div>
                                    <div class="message-content mt-2">
                                        <?=nl2br(htmlspecialchars($ticket->message))?>
                                    </div>
                                </div>

                                <!-- Replies -->
                                <?php foreach($replies->list() as $reply): $reply = (object)$reply; ?>
                                    <div class="ticket-message <?=$reply->is_admin ? 'admin-message' : 'user-message'?>">
                                        <div class="message-header">
                                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <?php if($reply->is_admin): ?>
                                                    <div class="square-30 bg-green border-radius-50 flex-row-center-center">
                                                        <i class="mdi mdi-shield-account color-white font-14"></i>
                                                    </div>
                                                    <span class="font-14 font-weight-medium"><?=htmlspecialchars($reply->user->full_name ?? 'Admin')?></span>
                                                <?php else: ?>
                                                    <div class="square-30 bg-blue border-radius-50 flex-row-center-center">
                                                        <i class="mdi mdi-account color-white font-14"></i>
                                                    </div>
                                                    <span class="font-14 font-weight-medium"><?=htmlspecialchars($ticket->user->full_name ?? 'Bruger')?></span>
                                                <?php endif; ?>
                                                <span class="font-12 color-gray"><?=date('d/m/Y H:i', strtotime($reply->created_at))?></span>
                                            </div>
                                        </div>
                                        <div class="message-content mt-2">
                                            <?=nl2br(htmlspecialchars($reply->message))?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if($ticket->status === 'open'): ?>
                                <!-- Admin Reply Form -->
                                <div class="ticket-admin-reply-form mt-4 pt-4 border-top">
                                    <p class="mb-2 font-14 font-weight-medium">Svar til bruger</p>
                                    <textarea class="form-field-v2 w-100" id="adminReplyMessage" rows="4" placeholder="Skriv dit svar her..."></textarea>
                                    <div class="flex-row-end mt-2">
                                        <button type="button" class="btn-v2 action-btn" id="sendReplyBtn" onclick="sendAdminReply()">
                                            <i class="mdi mdi-send"></i>
                                            <span>Send svar</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ticket Info Sidebar -->
                <div class="col-12 col-lg-4 mt-4 mt-lg-0">

                    <?php if($ticket->type === 'merchant' && ($ticket->on_behalf_of ?? 'personal') === 'organisation' && $ticket->organisation): ?>
                    <!-- Organisation Info - Prominent -->
                    <div class="card border-radius-10px mb-4" style="border: 2px solid var(--action-color); background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-domain font-18 color-white"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-14 font-weight-bold">På vegne af virksomhed</p>
                                    <p class="mb-0 font-12 color-gray">Denne sag er oprettet for en virksomhed</p>
                                </div>
                            </div>

                            <div class="flex-col-start" style="row-gap: .75rem;">
                                <div class="flex-col-start">
                                    <p class="mb-1 font-12 color-gray">Virksomhed</p>
                                    <p class="mb-0 font-16 font-weight-bold"><?=htmlspecialchars($ticket->organisation->name ?? 'Ukendt')?></p>
                                </div>
                                <?php if(!isEmpty($ticket->organisation->cvr ?? null)): ?>
                                <div class="flex-col-start">
                                    <p class="mb-1 font-12 color-gray">CVR</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($ticket->organisation->cvr)?></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-row-end mt-3 pt-3 border-top">
                                <a href="<?=__url(Links::$admin->organisationDetail($ticket->organisation->uid ?? ''))?>" class="btn-v2 action-btn">
                                    <i class="mdi mdi-domain"></i>
                                    <span>Se virksomhed</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-information-outline font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Sag detaljer</p>
                            </div>

                            <div class="flex-col-start" style="row-gap: 1rem;">
                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Sag ID</p>
                                    <p class="mb-0 font-14 font-monospace"><?=$ticket->uid?></p>
                                </div>

                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Oprettet</p>
                                    <p class="mb-0 font-14"><?=date('d/m/Y H:i', strtotime($ticket->created_at))?></p>
                                </div>

                                <?php if($ticket->status === 'closed' && !isEmpty($ticket->closed_at)): ?>
                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Lukket</p>
                                    <p class="mb-0 font-14"><?=date('d/m/Y H:i', strtotime($ticket->closed_at))?></p>
                                </div>
                                <?php endif; ?>

                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Antal svar</p>
                                    <p class="mb-0 font-14"><?=$replies->count()?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="card border-radius-10px mt-4">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-account-outline font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Bruger info</p>
                            </div>

                            <div class="flex-col-start" style="row-gap: 1rem;">
                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Navn</p>
                                    <p class="mb-0 font-14"><?=htmlspecialchars($ticket->user->full_name ?? 'Ukendt')?></p>
                                </div>

                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Email</p>
                                    <p class="mb-0 font-14">
                                        <a href="mailto:<?=htmlspecialchars($ticket->user->email ?? '')?>" class="color-blue">
                                            <?=htmlspecialchars($ticket->user->email ?? 'Ingen email')?>
                                        </a>
                                    </p>
                                </div>

                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Telefon</p>
                                    <p class="mb-0 font-14"><?=htmlspecialchars($ticket->user->phone ?? 'Ingen telefon')?></p>
                                </div>

                                <div class="flex-col-start">
                                    <p class="mb-1 font-13 color-gray">Type</p>
                                    <span class="<?=$ticket->type === 'merchant' ? 'action-box' : 'info-box'?>">
                                        <?=$ticket->type === 'merchant' ? 'Forhandler' : 'Forbruger'?>
                                    </span>
                                </div>
                            </div>

                            <div class="flex-row-end mt-3 pt-3 border-top">
                                <a href="<?=__url(Links::$admin->userDetail($ticket->user->uid ?? ''))?>" class="btn-v2 trans-btn">
                                    <i class="mdi mdi-account-eye"></i>
                                    <span>Se bruger profil</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
