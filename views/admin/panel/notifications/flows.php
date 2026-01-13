<?php
/**
 * Admin Panel - Notification Flows
 * List and manage notification flows
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Notifikationsflows";
$flows = $args->flows ?? new \Database\Collection();
$breakpoints = $args->breakpoints ?? new \Database\Collection();
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
                <span class="font-13 color-dark">Flows</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Flows</h1>
                    <p class="mb-0 font-14 color-gray">Automatiserede notifikationsregler</p>
                </div>
                <a href="<?=__url(Links::$admin->panelNotificationFlows)?>/new" class="btn-v2 action-btn">
                    <i class="mdi mdi-plus mr-1"></i> Nyt flow
                </a>
            </div>

            <!-- Flows Table -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <?php if ($flows->empty()): ?>
                        <div class="flex-col-center flex-align-center py-5">
                            <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-source-branch font-28 color-gray"></i>
                            </div>
                            <p class="mb-0 font-16 font-weight-bold color-dark">Ingen flows</p>
                            <p class="mb-0 font-13 color-gray mt-1">Opret dit første notifikationsflow</p>
                            <a href="<?=__url(Links::$admin->panelNotificationFlows)?>/new" class="btn-v2 action-btn mt-3">
                                <i class="mdi mdi-plus mr-1"></i> Opret flow
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 plainDataTable" data-pagination-limit="15" data-sorting-col="4" data-sorting-order="desc">
                                <thead>
                                    <tr>
                                        <th class="font-12 color-gray font-weight-normal border-0 ps-3" style="max-width: 250px;">Navn</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Handlinger</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Periode</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Oprettet</th>
                                        <th class="font-12 color-gray font-weight-normal border-0 pe-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($flows->list() as $flow): ?>
                                        <?php
                                        $actionCount = \classes\Methods::notificationFlowActions()->count(['flow' => $flow->uid]);

                                        $periodText = '-';
                                        if ($flow->starts_at || $flow->ends_at) {
                                            $start = $flow->starts_at ? date('d/m/Y', $flow->starts_at) : '...';
                                            $end = $flow->ends_at ? date('d/m/Y', $flow->ends_at) : '...';
                                            $periodText = $start . ' - ' . $end;
                                        }

                                        // Handle created_at date
                                        $createdAt = $flow->created_at ?? null;
                                        if ($createdAt) {
                                            $timestamp = is_numeric($createdAt) ? (int)$createdAt : strtotime($createdAt);
                                            $createdDate = date('d/m/Y', $timestamp);
                                        } else {
                                            $timestamp = 0;
                                            $createdDate = '-';
                                        }
                                        ?>
                                        <tr>
                                            <td class="ps-3 py-3" style="max-width: 250px;">
                                                <a href="<?=__url(Links::$admin->panelNotificationFlows)?>/<?=$flow->uid?>" class="font-14 font-weight-medium color-dark hover-color-blue" style="word-wrap: break-word;">
                                                    <?=htmlspecialchars($flow->name)?>
                                                </a>
                                                <?php if ($flow->description): ?>
                                                    <p class="mb-0 font-12 color-gray" style="word-wrap: break-word;"><?=htmlspecialchars(substr($flow->description, 0, 50))?><?=strlen($flow->description) > 50 ? '...' : ''?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-13"><?=$actionCount?> handling<?=$actionCount !== 1 ? 'er' : ''?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-12 color-gray"><?=$periodText?></span>
                                            </td>
                                            <td class="py-3">
                                                <?php
                                                $statusLabels = ['active' => 'Aktiv', 'inactive' => 'Inaktiv', 'draft' => 'Kladde'];
                                                $statusClasses = ['active' => 'success-box', 'inactive' => 'danger-box', 'draft' => 'mute-box'];
                                                $statusLabel = $statusLabels[$flow->status] ?? $flow->status;
                                                $statusClass = $statusClasses[$flow->status] ?? 'mute-box';
                                                ?>
                                                <span class="<?=$statusClass?> font-11"><?=$statusLabel?></span>
                                            </td>
                                            <td class="py-3" data-sort="<?=$timestamp?>">
                                                <span class="font-12 color-gray"><?=$createdDate?></span>
                                            </td>
                                            <td class="pe-3 py-3 text-end">
                                                <a href="<?=__url(Links::$admin->panelNotificationFlows)?>/<?=$flow->uid?>" class="btn-v2 action-btn btn-sm">
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
