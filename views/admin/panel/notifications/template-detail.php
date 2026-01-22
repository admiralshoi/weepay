<?php
/**
 * Admin Panel - Notification Template Detail
 * Create or edit a notification template
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = $args->isNew ? "Ny skabelon" : "Rediger skabelon";
$template = $args->template ?? null;
$isNew = $args->isNew ?? true;
$breakpoints = $args->breakpoints ?? new \Database\Collection();

// Get component templates for insertion (those with status 'template' are base components)
$componentTemplates = \classes\Methods::notificationTemplates()->getByX(['category' => 'component', 'status' => 'template']);

// Build component templates group dynamically
$componentPlaceholders = [];
foreach ($componentTemplates->list() as $comp) {
    if (!empty($comp->slug)) {
        $componentPlaceholders['{{template.' . $comp->slug . '}}'] = $comp->name;
    }
}

// Define all available placeholders grouped by category
$placeholderGroups = [
    'components' => [
        'label' => 'Komponenter',
        'icon' => 'mdi-puzzle',
        'placeholders' => $componentPlaceholders,
        'htmlOnly' => true,
    ],
    'brand' => [
        'label' => 'Brand & Logos',
        'icon' => 'mdi-palette',
        'placeholders' => [
            '{{brand.logo}}' => 'Logo (bred)',
            '{{brand.logo_icon}}' => 'Logo (ikon)',
            '{{brand.name}}' => 'Brand navn',
            '{{brand.site}}' => 'Site navn',
            '{{brand.url}}' => 'Website URL',
            '{{brand.company_name}}' => 'Firma navn',
            '{{brand.company_address}}' => 'Firma adresse',
            '{{brand.cvr}}' => 'CVR nummer',
            '{{brand.email}}' => 'Kontakt e-mail',
            '{{brand.phone}}' => 'Kontakt telefon',
        ]
    ],
    'user' => [
        'label' => 'Bruger/Kunde',
        'icon' => 'mdi-account',
        'placeholders' => [
            '{{user.full_name}}' => 'Fulde navn',
            '{{user.first_name}}' => 'Fornavn',
            '{{user.last_name}}' => 'Efternavn',
            '{{user.email}}' => 'E-mail',
            '{{user.phone}}' => 'Telefon',
            '{{user.uid}}' => 'Bruger ID',
        ]
    ],
    'organisation' => [
        'label' => 'Forretning/Organisation',
        'icon' => 'mdi-domain',
        'placeholders' => [
            '{{organisation.name}}' => 'Forretningsnavn',
            '{{organisation.email}}' => 'Forretnings e-mail',
            '{{organisation.phone}}' => 'Forretnings telefon',
            '{{organisation.address}}' => 'Forretnings adresse',
            '{{organisation.city}}' => 'Forretnings by',
            '{{organisation.zip}}' => 'Forretnings postnummer',
            '{{organisation.cvr}}' => 'Forretnings CVR',
            '{{organisation.uid}}' => 'Organisation ID',
        ]
    ],
    'location' => [
        'label' => 'Lokation/Butik',
        'icon' => 'mdi-map-marker',
        'placeholders' => [
            '{{location.name}}' => 'Lokationsnavn',
            '{{location.address}}' => 'Lokations adresse',
            '{{location.city}}' => 'Lokations by',
            '{{location.zip}}' => 'Lokations postnummer',
            '{{location.phone}}' => 'Lokations telefon',
            '{{location.email}}' => 'Lokations e-mail',
            '{{location.uid}}' => 'Lokation ID',
        ]
    ],
    'order' => [
        'label' => 'Ordre/Køb',
        'icon' => 'mdi-cart',
        'placeholders' => [
            '{{order.uid}}' => 'Ordre ID',
            '{{order.amount}}' => 'Beløb (øre)',
            '{{order.formatted_amount}}' => 'Købsbeløb (formateret)',
            '{{order.currency}}' => 'Valuta',
            '{{order.status}}' => 'Status',
            '{{order.caption}}' => 'Ordrebeskrivelse',
            '{{order.created_at}}' => 'Oprettelsesdato (rå)',
            '{{order.created_date}}' => 'Oprettelsesdato (DD.MM.ÅÅÅÅ)',
            '{{order.created_time}}' => 'Oprettelsestidspunkt (HH:MM)',
            '{{order.created_datetime}}' => 'Oprettet dato+tid',
        ]
    ],
    'payment' => [
        'label' => 'Betaling',
        'icon' => 'mdi-credit-card',
        'placeholders' => [
            '{{payment.uid}}' => 'Betalings ID',
            '{{payment.amount}}' => 'Beløb (øre)',
            '{{payment.formatted_amount}}' => 'Beløb (formateret)',
            '{{payment.due_date}}' => 'Forfaldsdato (rå)',
            '{{payment.due_date_formatted}}' => 'Forfaldsdato (DD.MM.ÅÅÅÅ)',
            '{{payment.paid_at}}' => 'Betalingsdato (rå)',
            '{{payment.paid_date}}' => 'Betalingsdato (DD.MM.ÅÅÅÅ)',
            '{{payment.paid_time}}' => 'Betalingstidspunkt (HH:MM)',
            '{{payment.installment_number}}' => 'Rate nummer',
            '{{payment.status}}' => 'Status',
            '{{payment.status_label}}' => 'Status (dansk)',
        ]
    ],
    'payment_plan' => [
        'label' => 'Betalingsaftale',
        'icon' => 'mdi-calendar-clock',
        'placeholders' => [
            '{{payment_plan.total_installments}}' => 'Antal rater i alt',
            '{{payment_plan.remaining_installments}}' => 'Resterende rater',
            '{{payment_plan.completed_installments}}' => 'Gennemførte rater',
            '{{payment_plan.first_amount}}' => 'Første rate beløb (øre)',
            '{{payment_plan.first_amount_formatted}}' => 'Første rate (formateret)',
            '{{payment_plan.installment_amount}}' => 'Rate beløb (øre)',
            '{{payment_plan.installment_amount_formatted}}' => 'Rate beløb (formateret)',
            '{{payment_plan.remaining_amount}}' => 'Resterende beløb (øre)',
            '{{payment_plan.remaining_amount_formatted}}' => 'Resterende beløb (formateret)',
            '{{payment_plan.total_amount}}' => 'Samlet beløb (øre)',
            '{{payment_plan.total_amount_formatted}}' => 'Samlet beløb (formateret)',
            '{{payment_plan.next_due_date}}' => 'Næste forfaldsdato',
            '{{payment_plan.first_due_date}}' => 'Første forfaldsdato',
            '{{payment_plan.last_due_date}}' => 'Sidste forfaldsdato',
            '{{payment_plan.schedule_summary}}' => 'Oversigt over betalingsplan',
        ]
    ],
    'card' => [
        'label' => 'Betalingskort',
        'icon' => 'mdi-credit-card-outline',
        'placeholders' => [
            '{{card.last4}}' => 'Sidste 4 cifre',
            '{{card.brand}}' => 'Korttype (Visa, Mastercard, etc.)',
            '{{card.expiry}}' => 'Udløbsdato (MM/ÅÅ)',
            '{{card.holder_name}}' => 'Kortholders navn',
        ]
    ],
    'fees' => [
        'label' => 'Gebyrer',
        'icon' => 'mdi-currency-usd',
        'placeholders' => [
            '{{fees.reminder_fee}}' => 'Rykkergebyr (formateret)',
            '{{fees.reminder_fee_amount}}' => 'Rykkergebyr (øre)',
            '{{fees.total_fees}}' => 'Samlede gebyrer (formateret)',
            '{{fees.total_fees_amount}}' => 'Samlede gebyrer (øre)',
            '{{fees.reminder_count}}' => 'Antal rykkere sendt',
            '{{fees.total_outstanding}}' => 'Samlet udestående inkl. gebyrer',
            '{{fees.total_outstanding_formatted}}' => 'Samlet udestående (formateret)',
        ]
    ],
    'inviter' => [
        'label' => 'Inviterende',
        'icon' => 'mdi-account-plus',
        'placeholders' => [
            '{{inviter.full_name}}' => 'Fulde navn',
            '{{inviter.email}}' => 'E-mail',
        ]
    ],
    'app' => [
        'label' => 'System',
        'icon' => 'mdi-cog',
        'placeholders' => [
            '{{app.name}}' => 'App navn',
            '{{app.url}}' => 'App URL',
            '{{app.support_email}}' => 'Support e-mail',
            '{{app.login_url}}' => 'Login side URL',
        ]
    ],
    'links' => [
        'label' => 'Links',
        'icon' => 'mdi-link-variant',
        'placeholders' => [
            '{{reset_link}}' => 'Nulstillingslink',
            '{{invite_link}}' => 'Invitationslink',
            '{{payment_link}}' => 'Betalingslink',
            '{{receipt_link}}' => 'Kvitteringslink',
            '{{order_link}}' => 'Ordrelink',
            '{{agreement_link}}' => 'Betalingsaftale link',
            '{{retry_link}}' => 'Prøv igen link',
            '{{dashboard_link}}' => 'Overblik/dashboard link',
            '{{history_link}}' => 'Betalingshistorik link',
        ]
    ],
    'dates' => [
        'label' => 'Datoer & Tid',
        'icon' => 'mdi-calendar',
        'placeholders' => [
            '{{today}}' => 'Dags dato (DD.MM.ÅÅÅÅ)',
            '{{today_full}}' => 'Dags dato (d. DD. måned ÅÅÅÅ)',
            '{{current_time}}' => 'Nuværende tid (HH:MM)',
            '{{current_year}}' => 'Nuværende år',
            '{{days_until_due}}' => 'Dage til forfald',
            '{{days_overdue}}' => 'Dage overskredet',
        ]
    ],
    'policy' => [
        'label' => 'Politik/Vilkår',
        'icon' => 'mdi-file-document-outline',
        'placeholders' => [
            '{{policy_type}}' => 'Politik type',
            '{{policy_name}}' => 'Politik navn',
            '{{policy_link}}' => 'Link til politik',
            '{{update_summary}}' => 'Opdateringssammendrag',
            '{{effective_date}}' => 'Gældende fra dato',
        ]
    ],
    'misc' => [
        'label' => 'Andet',
        'icon' => 'mdi-dots-horizontal',
        'placeholders' => [
            '{{failure_reason}}' => 'Fejlårsag',
            '{{refund_amount}}' => 'Refunderet beløb (øre)',
            '{{refund_formatted_amount}}' => 'Refunderet beløb (formateret)',
            '{{refund_reason}}' => 'Refunderingsårsag',
            '{{suspension_reason}}' => 'Suspendering årsag',
            '{{viva_note}}' => 'VIVA håndterings note',
        ]
    ],
    'attachments' => [
        'label' => 'Vedhæftede Filer',
        'icon' => 'mdi-attachment',
        'placeholders' => [
            '{{attach:order_contract}}' => 'Vedhæft ordrekontrakt (PDF)',
            '{{attach:rykker_pdf}}' => 'Vedhæft rykker (PDF)',
        ],
        'htmlOnly' => false,
    ],
];
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "notifications";
    var templateUid = <?=json_encode($template->uid ?? null)?>;
    var isNewTemplate = <?=json_encode($isNew)?>;
    var placeholderGroups = <?=json_encode($placeholderGroups)?>;
</script>

<style>
    /* Placeholder inserter styles */
    .placeholder-inserter {
        position: relative;
        display: inline-block;
    }
    .placeholder-menu {
        position: fixed;
        z-index: 1050;
        width: 320px;
        max-width: calc(100vw - 40px);
        max-height: 350px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        display: none;
        padding: 0;
        margin: 0;
        box-sizing: border-box;
    }
    .placeholder-menu.show {
        display: block;
    }
    .placeholder-menu-header {
        padding: 10px 12px;
        border-bottom: 1px solid #e0e0e0;
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 1;
    }
    .placeholder-search {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 13px;
    }
    .placeholder-search:focus {
        outline: none;
        border-color: var(--color-blue);
    }
    .placeholder-group {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .placeholder-group:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .placeholder-group-label {
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 600;
        color: #888;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .placeholder-item {
        padding: 8px 12px;
        cursor: pointer;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        transition: background 0.15s;
    }
    .placeholder-item:hover {
        background: #f5f7ff;
    }
    .placeholder-item-code {
        font-family: monospace;
        font-size: 11px;
        color: #5c6bc0;
        background: #e8eaf6;
        padding: 2px 6px;
        border-radius: 4px;
        word-break: break-all;
        max-width: 100%;
    }
    .placeholder-item-label {
        font-size: 13px;
        color: #333;
        flex: 1;
        min-width: 80px;
    }
    .placeholder-item.hidden {
        display: none;
    }

    /* HTML Editor styles */
    .html-editor-container {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
    }
    .html-editor-toolbar {
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
        border-radius: 8px 8px 0 0;
        padding: 8px 12px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .html-editor-toolbar button {
        padding: 6px 10px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s;
    }
    .html-editor-toolbar button:hover {
        background: #e8eaf6;
        border-color: #5c6bc0;
    }
    .html-editor-textarea {
        width: 100%;
        min-height: 300px;
        padding: 12px;
        border: none;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-size: 13px;
        line-height: 1.5;
        resize: vertical;
        background: #fafafa;
    }
    .html-editor-textarea:focus {
        outline: none;
        background: #fff;
    }

    /* Insert button for textareas */
    .field-with-inserter {
        position: relative;
    }
    .field-with-inserter .insert-btn {
        position: absolute;
        top: 8px;
        right: 20px;
        z-index: 3;
    }
    .placeholder-inserter.menu-open {
        z-index: 5;
    }
</style>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <a href="<?=__url(Links::$admin->panelNotifications)?>" class="font-13 color-gray hover-color-blue">Notifikationer</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <a href="<?=__url(Links::$admin->panelNotificationTemplates)?>" class="font-13 color-gray hover-color-blue">Skabeloner</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=$isNew ? 'Ny' : htmlspecialchars($template->name ?? '')?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold"><?=$pageTitle?></h1>
                    <p class="mb-0 font-14 color-gray"><?=$isNew ? 'Opret en ny notifikationsskabelon' : 'Rediger skabelonens indhold og indstillinger'?></p>
                </div>
            </div>

            <!-- Template Form -->
            <form id="template-form" class="w-100">
                <div class="row" style="row-gap: 1.5rem;">
                    <!-- Main Content -->
                    <div class="col-12 col-lg-8">
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <!-- Name -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Navn <span class="color-red">*</span></label>
                                    <input type="text" name="name" class="form-field-v2 w-100" value="<?=htmlspecialchars($template->name ?? '')?>" placeholder="F.eks. Velkomsmail" required>
                                </div>

                                <!-- Subject (for email) -->
                                <div class="mb-3" id="subject-field">
                                    <label class="form-label font-13 font-weight-medium">Emne</label>
                                    <div class="field-with-inserter">
                                        <input type="text" name="subject" id="subject-input" class="form-field-v2 w-100" value="<?=htmlspecialchars($template->subject ?? '')?>" placeholder="E-mail emne">
                                        <div class="placeholder-inserter insert-btn">
                                            <button type="button" class="btn-v2 mute-btn btn-sm" onclick="togglePlaceholderMenu(this, 'subject-input', false)">
                                                <i class="mdi mdi-code-braces"></i> Indsæt
                                            </button>
                                            <div class="placeholder-menu" id="menu-subject-input"></div>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Kun relevant for e-mail skabeloner</small>
                                </div>

                                <!-- Content -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Indhold <span class="color-red">*</span></label>
                                    <div class="field-with-inserter">
                                        <textarea name="content" id="content-input" class="form-field-v2 w-100" rows="6" placeholder="Notifikationsindhold..." required><?=htmlspecialchars($template->content ?? '')?></textarea>
                                        <div class="placeholder-inserter insert-btn">
                                            <button type="button" class="btn-v2 mute-btn btn-sm" onclick="togglePlaceholderMenu(this, 'content-input', false)">
                                                <i class="mdi mdi-code-braces"></i> Indsæt
                                            </button>
                                            <div class="placeholder-menu" id="menu-content-input"></div>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Brug placeholders som {{user.full_name}}, {{order.amount}}, osv.</small>
                                </div>

                                <!-- HTML Content (for email) -->
                                <div class="mb-0" id="html-content-field">
                                    <label class="form-label font-13 font-weight-medium">HTML indhold (valgfrit)</label>
                                    <div class="html-editor-container">
                                        <div class="html-editor-toolbar">
                                            <button type="button" onclick="insertHtmlTag('h1')"><i class="mdi mdi-format-header-1"></i></button>
                                            <button type="button" onclick="insertHtmlTag('h2')"><i class="mdi mdi-format-header-2"></i></button>
                                            <button type="button" onclick="insertHtmlTag('p')"><i class="mdi mdi-format-paragraph"></i></button>
                                            <button type="button" onclick="insertHtmlTag('strong')"><i class="mdi mdi-format-bold"></i></button>
                                            <button type="button" onclick="insertHtmlTag('em')"><i class="mdi mdi-format-italic"></i></button>
                                            <button type="button" onclick="insertHtmlTag('a', true)"><i class="mdi mdi-link"></i></button>
                                            <button type="button" onclick="insertHtmlTag('ul')"><i class="mdi mdi-format-list-bulleted"></i></button>
                                            <button type="button" onclick="insertHtmlTag('br', false, true)"><i class="mdi mdi-keyboard-return"></i></button>
                                            <div style="flex: 1;"></div>
                                            <button type="button" class="btn-v2 secondary-btn btn-sm" onclick="previewTemplate()">
                                                <i class="mdi mdi-eye"></i> Forhåndsvis
                                            </button>
                                            <div class="placeholder-inserter">
                                                <button type="button" class="btn-v2 mute-btn btn-sm" onclick="togglePlaceholderMenu(this, 'html-content-input', true)">
                                                    <i class="mdi mdi-code-braces"></i> Indsæt placeholder
                                                </button>
                                                <div class="placeholder-menu" id="menu-html-content-input"></div>
                                            </div>
                                        </div>
                                        <textarea name="html_content" id="html-content-input" class="html-editor-textarea" placeholder="<h1>Overskrift</h1>&#10;<p>Hej {{user.full_name}},</p>&#10;<p>Indhold her...</p>"><?=htmlspecialchars($template->html_content ?? '')?></textarea>
                                    </div>
                                    <small class="form-text text-muted">Valgfrit HTML layout til e-mail. Hvis tom bruges plain text.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-12 col-lg-4">
                        <!-- Settings Card -->
                        <div class="card border-radius-10px mb-3">
                            <div class="card-body">
                                <p class="font-14 font-weight-bold mb-3">Indstillinger</p>

                                <!-- Type -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Type</label>
                                    <select name="type" class="form-select-v2 w-100" id="template-type">
                                        <option value="email" <?=($template->type ?? 'email') === 'email' ? 'selected' : ''?>>E-mail</option>
                                        <option value="sms" <?=($template->type ?? '') === 'sms' ? 'selected' : ''?>>SMS</option>
                                        <option value="bell" <?=($template->type ?? '') === 'bell' ? 'selected' : ''?>>Push (klokke)</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Status</label>
                                    <select name="status" class="form-select-v2 w-100">
                                        <option value="draft" <?=($template->status ?? 'draft') === 'draft' ? 'selected' : ''?>>Kladde</option>
                                        <option value="active" <?=($template->status ?? '') === 'active' ? 'selected' : ''?>>Aktiv</option>
                                        <option value="inactive" <?=($template->status ?? '') === 'inactive' ? 'selected' : ''?>>Inaktiv</option>
                                        <option value="template" <?=($template->status ?? '') === 'template' ? 'selected' : ''?>>Skabelon</option>
                                    </select>
                                    <small class="form-text text-muted">Skabelon-status bruges til base komponenter (header, footer) og kan ikke vælges i flow handlinger</small>
                                </div>

                                <!-- Submit -->
                                <button type="submit" class="btn-v2 action-btn w-100">
                                    <i class="mdi mdi-content-save-outline mr-1"></i>
                                    <?=$isNew ? 'Opret skabelon' : 'Gem ændringer'?>
                                </button>

                                <?php if (!$isNew): ?>
                                    <button type="button" class="btn-v2 danger-btn w-100 mt-2" onclick="deleteTemplate()">
                                        <i class="mdi mdi-delete-outline mr-1"></i> Slet skabelon
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Reference Card -->
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <p class="font-14 font-weight-bold mb-3">Hurtig reference</p>
                                <p class="font-12 color-gray mb-2">Klik på "Indsæt" knappen ved hvert felt for at tilføje placeholders.</p>
                                <div class="flex-col-start" style="gap: .5rem;">
                                    <?php foreach ($placeholderGroups as $groupKey => $group): ?>
                                    <div class="p-2 bg-light-gray border-radius-6px">
                                        <p class="mb-1 font-12 font-weight-medium flex-row-start flex-align-center" style="gap: 4px;">
                                            <i class="mdi <?=$group['icon']?> font-14"></i>
                                            <?=$group['label']?>
                                        </p>
                                        <?php $count = 0; foreach ($group['placeholders'] as $code => $label): ?>
                                            <?php if ($count++ < 2): ?>
                                            <code class="font-10 d-block"><?=$code?></code>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (count($group['placeholders']) > 2): ?>
                                        <span class="font-10 color-gray">+<?=count($group['placeholders']) - 2?> mere...</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-18 font-weight-bold">Forhåndsvisning</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Luk">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light-gray border-bottom">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="font-12 color-gray">Visning:</span>
                            <button type="button" class="btn-v2 btn-sm active" id="preview-desktop-btn" onclick="setPreviewDevice('desktop')">
                                <i class="mdi mdi-monitor"></i> Desktop
                            </button>
                            <button type="button" class="btn-v2 mute-btn btn-sm" id="preview-mobile-btn" onclick="setPreviewDevice('mobile')">
                                <i class="mdi mdi-cellphone"></i> Mobil
                            </button>
                        </div>
                    </div>
                </div>
                <div id="preview-container" style="background: #f0f0f0; padding: 20px; min-height: 400px; display: flex; justify-content: center; overflow: auto;">
                    <div id="preview-loading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 font-13 color-gray">Genererer forhåndsvisning...</p>
                    </div>
                    <iframe id="preview-frame" style="background: #fff; border: 1px solid #ddd; border-radius: 4px; width: 100%; max-width: 650px; height: 600px; transition: max-width 0.3s;"></iframe>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Luk</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle type-specific fields
    document.getElementById('template-type').addEventListener('change', function() {
        var isEmail = this.value === 'email';
        document.getElementById('subject-field').style.display = isEmail ? 'block' : 'none';
        document.getElementById('html-content-field').style.display = isEmail ? 'block' : 'none';
    });
    document.getElementById('template-type').dispatchEvent(new Event('change'));

    // Build placeholder menu HTML
    function buildPlaceholderMenuHtml() {
        var html = '<div class="placeholder-menu-header">';
        html += '<input type="text" class="placeholder-search" placeholder="Søg placeholders..." oninput="filterPlaceholders(this)">';
        html += '</div>';

        for (var groupKey in placeholderGroups) {
            var group = placeholderGroups[groupKey];
            var groupHtmlOnly = group.htmlOnly ? ' data-html-only="true"' : '';
            html += '<div class="placeholder-group" data-group="' + groupKey + '"' + groupHtmlOnly + '>';
            html += '<div class="placeholder-group-label"><i class="mdi ' + group.icon + '"></i> ' + group.label + '</div>';

            for (var code in group.placeholders) {
                var label = group.placeholders[code];
                var isLogoPlaceholder = (code === '{{brand.logo}}' || code === '{{brand.logo_icon}}');
                var itemHtmlOnly = (group.htmlOnly || isLogoPlaceholder) ? ' data-html-only="true"' : '';
                html += '<div class="placeholder-item" data-code="' + code + '" data-label="' + label.toLowerCase() + '"' + itemHtmlOnly + ' onclick="insertPlaceholder(this, \'' + code.replace(/'/g, "\\'") + '\')">';
                html += '<span class="placeholder-item-label">' + label + '</span>';
                html += '<span class="placeholder-item-code">' + code + '</span>';
                html += '</div>';
            }

            html += '</div>';
        }

        return html;
    }

    // Initialize all placeholder menus
    var menuHtml = buildPlaceholderMenuHtml();
    document.querySelectorAll('.placeholder-menu').forEach(function(menu) {
        menu.innerHTML = menuHtml;
    });

    // Current active target input
    var currentTargetInput = null;
    var currentMenu = null;

    function togglePlaceholderMenu(btn, targetInputId, isHtmlEditor) {
        var inserter = btn.parentElement;
        var menu = inserter.querySelector('.placeholder-menu');
        var isOpen = menu.classList.contains('show');

        // Close all menus and remove menu-open class first
        document.querySelectorAll('.placeholder-menu.show').forEach(function(m) {
            m.classList.remove('show');
        });
        document.querySelectorAll('.placeholder-inserter.menu-open').forEach(function(i) {
            i.classList.remove('menu-open');
        });

        if (!isOpen) {
            inserter.classList.add('menu-open');
            menu.classList.add('show');
            currentTargetInput = document.getElementById(targetInputId);
            currentMenu = menu;

            // Position menu using fixed positioning relative to viewport
            var btnRect = btn.getBoundingClientRect();
            var menuWidth = 320;
            var viewportWidth = window.innerWidth;
            var viewportHeight = window.innerHeight;

            // Position below button, align right edge with button right edge
            var left = btnRect.right - menuWidth;
            var top = btnRect.bottom + 4;

            // Keep within viewport bounds
            if (left < 10) left = 10;
            if (left + menuWidth > viewportWidth - 10) left = viewportWidth - menuWidth - 10;

            // If menu would go below viewport, position above button
            if (top + 350 > viewportHeight) {
                top = btnRect.top - 350 - 4;
                if (top < 10) top = 10;
            }

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';

            // Show/hide HTML-only options based on whether it's HTML editor
            menu.querySelectorAll('.placeholder-item').forEach(function(item) {
                var code = item.getAttribute('data-code');
                var isHtmlOnly = item.getAttribute('data-html-only') === 'true';
                if (isHtmlOnly) {
                    item.style.display = isHtmlEditor ? 'flex' : 'none';
                }
            });

            // Show/hide HTML-only groups
            menu.querySelectorAll('.placeholder-group').forEach(function(group) {
                var isHtmlOnlyGroup = group.getAttribute('data-html-only') === 'true';
                if (isHtmlOnlyGroup) {
                    group.style.display = isHtmlEditor ? 'block' : 'none';
                }
            });

            // Focus search
            var searchInput = menu.querySelector('.placeholder-search');
            if (searchInput) {
                searchInput.value = '';
                filterPlaceholders(searchInput);
                setTimeout(function() { searchInput.focus(); }, 50);
            }
        } else {
            currentTargetInput = null;
            currentMenu = null;
        }
    }

    function filterPlaceholders(searchInput) {
        var query = searchInput.value.toLowerCase();
        var menu = searchInput.closest('.placeholder-menu');

        menu.querySelectorAll('.placeholder-item').forEach(function(item) {
            var code = item.getAttribute('data-code').toLowerCase();
            var label = item.getAttribute('data-label');
            var matches = code.includes(query) || label.includes(query);
            item.classList.toggle('hidden', !matches);
        });

        // Hide empty groups
        menu.querySelectorAll('.placeholder-group').forEach(function(group) {
            var hasVisible = group.querySelectorAll('.placeholder-item:not(.hidden)').length > 0;
            group.style.display = hasVisible ? 'block' : 'none';
        });
    }

    function insertPlaceholder(item, code) {
        if (!currentTargetInput) return;

        var input = currentTargetInput;
        var start = input.selectionStart;
        var end = input.selectionEnd;
        var text = input.value;

        input.value = text.substring(0, start) + code + text.substring(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + code.length;

        // Close menu
        if (currentMenu) {
            currentMenu.classList.remove('show');
        }
        currentTargetInput = null;
        currentMenu = null;
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.placeholder-inserter')) {
            document.querySelectorAll('.placeholder-menu.show').forEach(function(m) {
                m.classList.remove('show');
            });
            document.querySelectorAll('.placeholder-inserter.menu-open').forEach(function(i) {
                i.classList.remove('menu-open');
            });
            currentTargetInput = null;
            currentMenu = null;
        }
    });

    // Preview functionality
    function previewTemplate() {
        var htmlContent = document.getElementById('html-content-input').value;
        if (!htmlContent || htmlContent.trim() === '') {
            showErrorToast('Ingen HTML indhold at forhåndsvise');
            return;
        }

        $('#previewModal').modal('show');
        document.getElementById('preview-loading').style.display = 'block';
        document.getElementById('preview-frame').style.display = 'none';

        // Send to server for placeholder replacement (brand + template placeholders only)
        post('api/admin/notifications/templates/preview', {
            html_content: wrapHtmlContent(htmlContent)
        }).then(function(response) {
            document.getElementById('preview-loading').style.display = 'none';
            document.getElementById('preview-frame').style.display = 'block';

            var iframe = document.getElementById('preview-frame');
            var doc = iframe.contentDocument || iframe.contentWindow.document;

            if (response.status === 'success') {
                doc.open();
                doc.write(response.data.html);
                doc.close();
            } else {
                doc.open();
                doc.write('<html><body style="font-family: sans-serif; padding: 20px;"><p style="color: red;">Fejl: ' + (response.message || 'Kunne ikke generere forhåndsvisning') + '</p></body></html>');
                doc.close();
            }
        }).catch(function() {
            document.getElementById('preview-loading').style.display = 'none';
            document.getElementById('preview-frame').style.display = 'block';

            var iframe = document.getElementById('preview-frame');
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write('<html><body style="font-family: sans-serif; padding: 20px;"><p style="color: red;">Netværksfejl - kunne ikke generere forhåndsvisning</p></body></html>');
            doc.close();
        });
    }

    function setPreviewDevice(device) {
        var iframe = document.getElementById('preview-frame');
        var desktopBtn = document.getElementById('preview-desktop-btn');
        var mobileBtn = document.getElementById('preview-mobile-btn');

        if (device === 'mobile') {
            iframe.style.maxWidth = '375px';
            mobileBtn.classList.remove('mute-btn');
            mobileBtn.classList.add('active');
            desktopBtn.classList.add('mute-btn');
            desktopBtn.classList.remove('active');
        } else {
            iframe.style.maxWidth = '650px';
            desktopBtn.classList.remove('mute-btn');
            desktopBtn.classList.add('active');
            mobileBtn.classList.add('mute-btn');
            mobileBtn.classList.remove('active');
        }
    }

    // HTML Editor helpers
    function insertHtmlTag(tag, needsHref, selfClosing) {
        var textarea = document.getElementById('html-content-input');
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var selectedText = textarea.value.substring(start, end) || 'tekst';
        var newText;

        if (selfClosing) {
            newText = '<' + tag + ' />';
        } else if (needsHref) {
            newText = '<' + tag + ' href="#">' + selectedText + '</' + tag + '>';
        } else if (tag === 'ul') {
            newText = '<ul>\n  <li>' + selectedText + '</li>\n</ul>';
        } else {
            newText = '<' + tag + '>' + selectedText + '</' + tag + '>';
        }

        textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
        textarea.focus();

        // Position cursor
        var cursorPos = start + newText.length;
        textarea.selectionStart = textarea.selectionEnd = cursorPos;
    }
</script>
