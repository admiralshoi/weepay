<?php
/**
 * Admin Panel - Notification Templates
 * List and manage notification templates
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Notifikationsskabeloner";
$templates = $args->templates ?? new \Database\Collection();
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "notifications";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <a href="<?=__url(Links::$admin->panelNotifications)?>" class="font-13 color-gray hover-color-blue">Notifikationer</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Skabeloner</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Skabeloner</h1>
                    <p class="mb-0 font-14 color-gray">Opret og rediger notifikationsskabeloner</p>
                </div>
                <a href="<?=__url(Links::$admin->panelNotificationTemplates)?>/new" class="btn-v2 action-btn">
                    <i class="mdi mdi-plus mr-1"></i> Ny skabelon
                </a>
            </div>

            <!-- Templates Table -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <?php if ($templates->empty()): ?>
                        <div class="flex-col-center flex-align-center py-5">
                            <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-file-document-outline font-28 color-gray"></i>
                            </div>
                            <p class="mb-0 font-16 font-weight-bold color-dark">Ingen skabeloner</p>
                            <p class="mb-0 font-13 color-gray mt-1">Opret din første notifikationsskabelon</p>
                            <a href="<?=__url(Links::$admin->panelNotificationTemplates)?>/new" class="btn-v2 action-btn mt-3">
                                <i class="mdi mdi-plus mr-1"></i> Opret skabelon
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 plainDataTable" data-pagination-limit="15" data-sorting-col="4" data-sorting-order="desc">
                                <thead>
                                    <tr>
                                        <th class="font-12 color-gray font-weight-normal border-0 ps-3">Navn</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Type</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Emne</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Oprettet</th>
                                        <th class="font-12 color-gray font-weight-normal border-0 pe-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($templates->list() as $template): ?>
                                        <?php
                                        $typeLabels = ['email' => 'E-mail', 'sms' => 'SMS', 'bell' => 'Push'];
                                        $typeClasses = ['email' => 'action-box', 'sms' => 'success-box', 'bell' => 'info-box'];
                                        $typeLabel = $typeLabels[$template->type] ?? $template->type;
                                        $typeClass = $typeClasses[$template->type] ?? 'mute-box';

                                        $statusLabels = ['active' => 'Aktiv', 'inactive' => 'Inaktiv', 'draft' => 'Kladde', 'template' => 'Skabelon'];
                                        $statusClasses = ['active' => 'success-box', 'inactive' => 'danger-box', 'draft' => 'mute-box', 'template' => 'info-box'];
                                        $statusLabel = $statusLabels[$template->status] ?? $template->status;
                                        $statusClass = $statusClasses[$template->status] ?? 'mute-box';

                                        // Handle date - could be timestamp or string
                                        $createdAt = $template->created_at ?? null;
                                        if ($createdAt) {
                                            $timestamp = is_numeric($createdAt) ? (int)$createdAt : strtotime($createdAt);
                                            $createdDate = date('d/m/Y', $timestamp);
                                        } else {
                                            $createdDate = '-';
                                        }
                                        ?>
                                        <tr>
                                            <td class="ps-3 py-3">
                                                <a href="<?=__url(Links::$admin->panelNotificationTemplates)?>/<?=$template->uid?>" class="font-14 font-weight-medium color-dark hover-color-blue">
                                                    <?=htmlspecialchars($template->name)?>
                                                </a>
                                            </td>
                                            <td class="py-3">
                                                <span class="<?=$typeClass?> font-11"><?=$typeLabel?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-13 color-gray"><?=htmlspecialchars($template->subject ?? '-')?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="<?=$statusClass?> font-11"><?=$statusLabel?></span>
                                            </td>
                                            <td class="py-3" data-sort="<?=$timestamp ?? 0?>">
                                                <span class="font-12 color-gray"><?=$createdDate?></span>
                                            </td>
                                            <td class="pe-3 py-3 text-end">
                                                <a href="<?=__url(Links::$admin->panelNotificationTemplates)?>/<?=$template->uid?>" class="btn-v2 action-btn btn-sm">
                                                    <i class="mdi mdi-pencil-outline"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
