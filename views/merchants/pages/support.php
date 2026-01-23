<?php
/**
 * Merchant Support Page
 * @var object $args
 */

use classes\enumerations\Links;
use features\Settings;

$pageTitle = "Support";
$tickets = $args->tickets ?? new \Database\Collection();
$openCount = $args->openCount ?? 0;
$closedCount = $args->closedCount ?? 0;
$categories = $args->categories ?? [];
$ticketReplies = $args->ticketReplies ?? new stdClass();
$currentOrg = Settings::$organisation;

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "support";
</script>

<div class="page-content support-page">

    <div class="flex-col-start mb-4">
        <p class="mb-0 font-30 font-weight-bold">Support</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Har du brug for hjælp? Opret en henvendelse eller se dine eksisterende sager.</p>
    </div>

    <!-- Stats Row -->
    <div class="row flex-align-stretch rg-15 mb-4">
        <div class="col-6 col-md-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Åbne sager</p>
                            <p class="font-22 font-weight-700"><?=$openCount?></p>
                        </div>
                        <div class="flex-row-end">
                            <div class="square-50 bg-warning border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-ticket-outline color-acoustic-yellow font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Lukkede sager</p>
                            <p class="font-22 font-weight-700"><?=$closedCount?></p>
                        </div>
                        <div class="flex-row-end">
                            <div class="square-50 bg-green border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-check-circle color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Total</p>
                            <p class="font-22 font-weight-700"><?=$tickets->count()?></p>
                        </div>
                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-ticket-confirmation color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Create Ticket Form -->
        <div class="col-12 col-lg-5">
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <i class="mdi mdi-plus-circle-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Opret ny henvendelse</p>
                    </div>

                    <form id="createTicketForm">
                        <div class="mb-3">
                            <label class="form-label font-14 font-weight-medium">På vegne af</label>
                            <select class="form-select-v2 w-100 h-45px" name="on_behalf_of">
                                <option value="personal">Mig selv (personlig)</option>
                                <?php if($currentOrg): ?>
                                <option value="organisation"><?=htmlspecialchars($currentOrg->organisation->name ?? 'Min virksomhed')?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-14 font-weight-medium">Kategori</label>
                            <select class="form-select-v2 w-100 h-45px" name="category">
                                <option value="">Vælg kategori</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?=htmlspecialchars($category)?>"><?=htmlspecialchars($category)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-14 font-weight-medium">Emne</label>
                            <input type="text" class="form-field-v2 w-100" name="subject" placeholder="Kort beskrivelse af dit problem" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-14 font-weight-medium">Besked</label>
                            <textarea class="form-field-v2 w-100" name="message" rows="5" placeholder="Beskriv dit problem eller spørgsmål i detaljer..." required></textarea>
                        </div>

                        <button type="submit" class="btn-v2 action-btn w-100" id="createTicketBtn">
                            <i class="mdi mdi-send"></i>
                            <span>Send henvendelse</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <p class="font-16 font-weight-bold mb-3">Andre kontaktmuligheder</p>
                    <div class="flex-col-start" style="row-gap: 1rem;">
                        <div class="p-3 bg-light-gray border-radius-8px">
                            <div class="flex-row-start flex-align-start" style="gap: .75rem;">
                                <div class="square-40 bg-blue border-radius-8px flex-row-center-center flex-shrink-0">
                                    <i class="mdi mdi-email-outline color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-14 font-weight-medium">Email</p>
                                    <a href="mailto:support@wee-pay.dk" class="font-13 color-blue">support@wee-pay.dk</a>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 bg-light-gray border-radius-8px">
                            <div class="flex-row-start flex-align-start" style="gap: .75rem;">
                                <div class="square-40 bg-green border-radius-8px flex-row-center-center flex-shrink-0">
                                    <i class="mdi mdi-phone-outline color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-14 font-weight-medium">Telefon</p>
                                    <span class="font-13 color-dark">+45 12 34 56 78</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="col-12 col-lg-7 mt-4 mt-lg-0">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-wrap mb-4" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-ticket-outline font-18 color-blue"></i>
                            <p class="mb-0 font-20 font-weight-bold">Mine henvendelser</p>
                        </div>
                        <div class="support-toggle">
                            <button type="button" class="support-toggle-btn active" onclick="filterTickets('open')" id="toggleOpen">
                                Åbne
                                <span class="count" id="openCountBadge"><?=$openCount?></span>
                            </button>
                            <button type="button" class="support-toggle-btn" onclick="filterTickets('closed')" id="toggleClosed">
                                Lukkede
                                <span class="count" id="closedCountBadge"><?=$closedCount?></span>
                            </button>
                        </div>
                    </div>

                    <?php if($tickets->empty()): ?>
                        <div class="flex-col-center flex-align-center py-5">
                            <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-ticket-outline font-30 color-gray"></i>
                            </div>
                            <p class="mb-0 font-16 color-gray">Ingen henvendelser endnu</p>
                            <p class="mb-0 font-14 color-gray mt-1">Opret din første henvendelse ved at udfylde formularen.</p>
                        </div>
                    <?php else: ?>
                        <!-- Empty states for filtered views -->
                        <div class="flex-col-center flex-align-center py-5" id="emptyOpenState" style="<?=$openCount > 0 ? 'display: none;' : ''?>">
                            <div class="square-60 bg-green border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-check font-30 color-white"></i>
                            </div>
                            <p class="mb-0 font-16 color-gray">Ingen åbne sager</p>
                            <p class="mb-0 font-14 color-gray mt-1">Du har ingen aktive henvendelser.</p>
                        </div>
                        <div class="flex-col-center flex-align-center py-5" id="emptyClosedState" style="display: none;">
                            <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-ticket-outline font-30 color-gray"></i>
                            </div>
                            <p class="mb-0 font-16 color-gray">Ingen lukkede sager</p>
                            <p class="mb-0 font-14 color-gray mt-1">Du har ingen afsluttede henvendelser.</p>
                        </div>

                        <div class="tickets-list flex-col-start" style="row-gap: 1rem;" id="ticketsList">
                            <?php foreach($tickets->list() as $ticket): $ticket = (object)$ticket; ?>
                                <div class="ticket-card" data-ticket-uid="<?=$ticket->uid?>" data-status="<?=$ticket->status?>" style="<?=$ticket->status === 'closed' ? 'display: none;' : ''?>">
                                    <div class="ticket-header" onclick="toggleTicket('<?=$ticket->uid?>')">
                                        <div class="flex-row-between flex-align-center flex-wrap" style="gap: 1rem;">
                                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 1rem;">
                                                <?php if($ticket->status === 'open'): ?>
                                                    <div class="square-40 bg-warning border-radius-8px flex-row-center-center flex-shrink-0">
                                                        <i class="mdi mdi-ticket-outline color-acoustic-yellow font-20"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="square-40 bg-green border-radius-8px flex-row-center-center flex-shrink-0">
                                                        <i class="mdi mdi-check-circle color-white font-20"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex-col-start">
                                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($ticket->subject)?></p>
                                                    <p class="mb-0 font-12 color-gray">
                                                        <?=htmlspecialchars($ticket->category)?>
                                                        &middot;
                                                        <?php if(($ticket->on_behalf_of ?? 'personal') === 'organisation' && $ticket->organisation): ?>
                                                            <i class="mdi mdi-domain"></i> <?=htmlspecialchars($ticket->organisation->name ?? 'Virksomhed')?>
                                                        <?php else: ?>
                                                            <i class="mdi mdi-account"></i> Personlig
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex-row-end flex-align-center" style="gap: .75rem;">
                                                <span class="<?=$ticket->status === 'open' ? 'warning-box' : 'success-box'?>">
                                                    <?=$ticket->status === 'open' ? 'Åben' : 'Lukket'?>
                                                </span>
                                                <p class="mb-0 font-12 color-gray"><?=date('d/m/Y', strtotime($ticket->created_at))?></p>
                                                <i class="mdi mdi-chevron-down font-20 color-gray ticket-chevron"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ticket-body" style="display: none;">
                                        <div class="ticket-messages" id="ticketMessages_<?=$ticket->uid?>">
                                            <!-- Original message -->
                                            <div class="ticket-message user-message">
                                                <div class="message-header">
                                                    <span class="font-12 font-weight-medium">Dig</span>
                                                    <span class="font-11 color-gray"><?=date('d/m/Y H:i', strtotime($ticket->created_at))?></span>
                                                </div>
                                                <div class="message-content">
                                                    <?=nl2br(htmlspecialchars($ticket->message))?>
                                                </div>
                                            </div>
                                            <!-- Replies -->
                                            <?php $ticketUid = $ticket->uid; if(isset($ticketReplies->$ticketUid) && !$ticketReplies->$ticketUid->empty()): ?>
                                                <?php foreach($ticketReplies->$ticketUid->list() as $reply): $reply = (object)$reply; ?>
                                                    <div class="ticket-message <?=$reply->is_admin ? 'admin-message' : 'user-message'?>">
                                                        <div class="message-header">
                                                            <span class="font-12 font-weight-medium"><?=$reply->is_admin ? 'WeePay Support' : 'Dig'?></span>
                                                            <span class="font-11 color-gray"><?=date('d/m/Y H:i', strtotime($reply->created_at))?></span>
                                                        </div>
                                                        <div class="message-content">
                                                            <?=nl2br(htmlspecialchars($reply->message))?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <?php if($ticket->status === 'open'): ?>
                                            <!-- Reply Form -->
                                            <div class="ticket-reply-form mt-3">
                                                <textarea class="form-field-v2 w-100" id="replyMessage_<?=$ticket->uid?>" rows="3" placeholder="Skriv dit svar..."></textarea>
                                                <div class="flex-row-between flex-align-center mt-2" style="gap: .5rem;">
                                                    <button type="button" class="btn-v2 action-btn" onclick="addReply('<?=$ticket->uid?>')">
                                                        <i class="mdi mdi-send"></i>
                                                        <span>Send svar</span>
                                                    </button>
                                                    <button type="button" class="btn-v2 danger-btn" onclick="closeTicket('<?=$ticket->uid?>')">
                                                        <i class="mdi mdi-close-circle"></i>
                                                        <span>Luk sag</span>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex-row-between flex-align-center mt-3 pt-3 border-top">
                                                <p class="mb-0 font-12 color-gray">
                                                    <i class="mdi mdi-check-circle color-green"></i>
                                                    Lukket <?=!isEmpty($ticket->closed_at) ? date('d/m/Y H:i', strtotime($ticket->closed_at)) : ''?>
                                                </p>
                                                <button type="button" class="btn-v2 trans-btn" onclick="reopenTicket('<?=$ticket->uid?>')">
                                                    <i class="mdi mdi-refresh"></i>
                                                    <span>Genåbn sag</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
