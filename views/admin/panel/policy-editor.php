<?php
/**
 * Admin Panel - Policy Editor (shared template)
 * @var object $args - Contains policyType
 */

use classes\enumerations\Links;
use classes\Methods;

$policyType = $args->policyType ?? '';
$typeName = Methods::policyTypes()->getDisplayName($policyType);
$pageTitle = "Rediger " . $typeName;
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var policyType = <?=json_encode($policyType)?>;
    activePage = "policies";
</script>

<link rel="stylesheet" href="<?=__url('public/css/admin-policy-editor.css')?>">

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-col-start w-100" style="gap: 1rem;">
                <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                    <div class="flex-col-start">
                        <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                            <a href="<?=__url(Links::$admin->panelPolicies)?>" class="color-gray hover-color-dark">
                                <i class="mdi mdi-arrow-left font-20"></i>
                            </a>
                            <h1 class="mb-0 font-24 font-weight-bold" id="page-title"><?=$typeName?></h1>
                            <span class="mute-box font-10 ml-2" id="policy-status-badge">Indlæser...</span>
                        </div>
                        <p class="mb-0 font-14 color-gray ml-4" id="policy-version-info">Version info indlæses...</p>
                    </div>
                </div>
                <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 0.75rem;">
                    <!-- View/Info buttons -->
                    <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                        <button type="button" class="btn-v2 mute-btn" onclick="previewPolicy()">
                            <i class="mdi mdi-eye mr-1"></i> Forhåndsvisning
                        </button>
                        <button type="button" class="btn-v2 mute-btn" onclick="loadVersionHistory()">
                            <i class="mdi mdi-history mr-1"></i> Historik
                        </button>
                        <a href="#" id="view-live-btn" class="btn-v2 mute-btn" target="_blank" style="display: none;">
                            <i class="mdi mdi-open-in-new mr-1"></i> Se live
                        </a>
                    </div>
                    <!-- Action buttons -->
                    <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                        <button type="button" class="btn-v2 danger-btn-outline" id="delete-btn" style="display: none;" onclick="deletePolicy()">
                            <i class="mdi mdi-delete-outline mr-1"></i> Slet kladde
                        </button>
                        <button type="button" class="btn-v2 gray-btn" id="save-btn" style="display: none;" onclick="saveDraft()">
                            <i class="mdi mdi-content-save-outline mr-1"></i> Gem kladde
                        </button>
                        <button type="button" class="btn-v2 action-btn" id="publish-btn" onclick="openPublishModal()">
                            <i class="mdi mdi-publish mr-1"></i> Publicer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scheduled Warning -->
            <div id="scheduled-warning" class="warning-box-outline w-100 px-3 py-2 font-14" style="display: none; border-radius: 6px;"></div>

            <!-- Editor Card -->
            <div class="card">
                <div class="card-body">
                    <!-- Title Field -->
                    <div class="mb-3">
                        <label class="form-label font-14 font-weight-bold">Titel</label>
                        <input type="text" class="form-field-v2" id="policy-title" placeholder="Indtast titel">
                    </div>

                    <!-- Editor Toolbar -->
                    <div class="policy-editor-toolbar mb-2">
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-btn" onclick="execCmd('bold')" title="Fed (Ctrl+B)">
                                <i class="mdi mdi-format-bold"></i>
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('italic')" title="Kursiv (Ctrl+I)">
                                <i class="mdi mdi-format-italic"></i>
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('underline')" title="Understreget (Ctrl+U)">
                                <i class="mdi mdi-format-underline"></i>
                            </button>
                        </div>
                        <div class="toolbar-divider"></div>
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'H1')" title="Overskrift 1">
                                H1
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'H2')" title="Overskrift 2">
                                H2
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'H3')" title="Overskrift 3">
                                H3
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('formatBlock', 'P')" title="Afsnit">
                                <i class="mdi mdi-format-paragraph"></i>
                            </button>
                        </div>
                        <div class="toolbar-divider"></div>
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-btn" onclick="execCmd('insertUnorderedList')" title="Punktliste">
                                <i class="mdi mdi-format-list-bulleted"></i>
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('insertOrderedList')" title="Nummereret liste">
                                <i class="mdi mdi-format-list-numbered"></i>
                            </button>
                        </div>
                        <div class="toolbar-divider"></div>
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-btn" onclick="insertLink()" title="Indsæt link">
                                <i class="mdi mdi-link"></i>
                            </button>
                            <button type="button" class="toolbar-btn" onclick="execCmd('unlink')" title="Fjern link">
                                <i class="mdi mdi-link-off"></i>
                            </button>
                        </div>
                        <div class="toolbar-divider"></div>
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-btn" onclick="execCmd('insertHorizontalRule')" title="Horisontal linje">
                                <i class="mdi mdi-minus"></i>
                            </button>
                        </div>
                        <div class="toolbar-divider"></div>
                        <div class="toolbar-group">
                            <button type="button" class="toolbar-btn" onclick="insertTable()" title="Indsæt tabel">
                                <i class="mdi mdi-table"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Content Editor -->
                    <div class="policy-editor-container">
                        <div id="policy-content" class="policy-editor" contenteditable="true" placeholder="Skriv politikindhold her..."></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Publish Modal -->
<div class="modal fade" id="publishModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Publicer politik</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Schedule Option -->
                <div class="mb-3">
                    <label class="form-label font-14 font-weight-bold">Publiceringsdato</label>
                    <input type="datetime-local" class="form-field-v2 w-100" id="publish-starts-at">
                    <small class="text-muted d-block mt-1">Lad være tom for øjeblikkelig publicering</small>
                </div>

                <!-- Notification Option -->
                <div class="mb-3">
                    <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="notify-users" onchange="toggleRecipientTypes()">
                        <span>Notificer brugere om ændringen</span>
                    </label>
                </div>

                <!-- Recipient Types (hidden by default) -->
                <div id="recipient-types-container" style="display: none;">
                    <label class="form-label font-14 font-weight-bold">Modtagergrupper</label>
                    <div class="flex-col-start" style="gap: 0.5rem;">
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="all" id="rt-all">
                            <span>Alle brugere</span>
                        </label>
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="consumers" id="rt-consumers">
                            <span>Forbrugere</span>
                        </label>
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="merchants" id="rt-merchants">
                            <span>Forhandlere</span>
                        </label>
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="org_owners" id="rt-org-owners">
                            <span>Virksomhedsejere</span>
                        </label>
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="org_admins" id="rt-org-admins">
                            <span>Virksomhedsadministratorer</span>
                        </label>
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="location_managers" id="rt-location-managers">
                            <span>Lokationsledere</span>
                        </label>
                        <label class="flex-row-start flex-align-center" style="gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" class="recipient-type" value="weepay_admins" id="rt-weepay-admins">
                            <span>WeePay administratorer</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" id="modal-publish-btn" onclick="publishPolicy()">Publicer</button>
            </div>
        </div>
    </div>
</div>

<!-- Version History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Versionshistorik</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="history-content">
                <div class="flex-col-center flex-align-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 mb-0 color-gray">Indlæser historik...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Version Modal -->
<div class="modal fade" id="viewVersionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="view-version-title">Version</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="policy-content-preview" id="view-version-content"></div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Forhåndsvisning</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="policy-content-preview" id="preview-content"></div>
            </div>
        </div>
    </div>
</div>

<script>
    var policyLiveUrls = {
        'consumer_privacy': '<?=__url(Links::$policies->consumer->privacy)?>',
        'consumer_terms': '<?=__url(Links::$policies->consumer->termsOfUse)?>',
        'merchant_privacy': '<?=__url(Links::$policies->merchant->privacy)?>',
        'merchant_terms': '<?=__url(Links::$policies->merchant->termsOfUse)?>',
        'cookies': '<?=__url(Links::$policies->cookies)?>'
    };
    var policyPlaceholders = {
        '{{BRAND_NAME}}': '<?=BRAND_NAME?>',
        '{{COMPANY_NAME}}': '<?=COMPANY_NAME?>',
        '{{COMPANY_CVR}}': '<?=COMPANY_CVR?>',
        '{{COMPANY_ADDRESS_STRING}}': '<?=COMPANY_ADDRESS_STRING?>',
        '{{CONTACT_EMAIL}}': '<?=CONTACT_EMAIL?>',
        '{{CONTACT_PHONE}}': '<?=CONTACT_PHONE?>'
    };
</script>
<script src="<?=__url('public/js/admin-policy-editor.js')?>"></script>
