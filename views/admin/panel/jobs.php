<?php
/**
 * Admin Panel - Cron Jobs
 * Monitor and manage scheduled tasks
 * @var object $args
 */

use classes\enumerations\Links;
$pageTitle = "Cron Jobs";
$cronjobs = $args->cronjobs ?? new \Database\Collection();
$cronConfig = $args->cronConfig ?? [];

// Convert to array if it's an object
if (is_object($cronConfig)) {
    $cronConfig = (array) $cronConfig;
}

// Build a map of config by row_id for easy lookup
$configByRowId = [];
foreach ($cronConfig as $type => $config) {
    // Convert config to array if it's an object
    if (is_object($config)) {
        $config = (array) $config;
    }
    $configByRowId[$config['row_id']] = array_merge($config, ['type' => $type]);
}
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "jobs";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Cron Jobs</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Cron Jobs</h1>
                    <p class="mb-0 font-14 color-gray">Overvåg og kør planlagte opgaver</p>
                </div>
            </div>

            <?php if ($cronjobs->empty()): ?>
                <!-- Empty State -->
                <div class="card border-radius-10px">
                    <div class="card-body flex-col-center flex-align-center py-5">
                        <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                            <i class="mdi mdi-clock-outline font-40 color-gray"></i>
                        </div>
                        <p class="mb-0 font-18 font-weight-bold color-dark">Ingen cronjobs fundet</p>
                        <p class="mb-0 font-14 color-gray mt-2">Kør migration for at oprette cronjob tabellen.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cronjobs Table -->
                <div class="card border-radius-10px">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="font-12 color-gray font-weight-normal border-0 ps-3">Navn</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Interval</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Sidst kørt</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Sidst afsluttet</th>
                                        <th class="font-12 color-gray font-weight-normal border-0 pe-3 text-end">Handlinger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cronjobs->list() as $job): ?>
                                        <?php
                                        $config = $configByRowId[$job->uid] ?? null;
                                        $type = $config['type'] ?? str_replace('crn_', '', $job->uid);
                                        $timeGap = $config['time_gab'] ?? 0;

                                        // Determine status
                                        $isRunning = (int)$job->is_running === 1;
                                        $canRun = (int)$job->can_run === 1;

                                        if ($isRunning) {
                                            $statusClass = 'warning-box';
                                            $statusLabel = 'Kører';
                                        } elseif ($canRun) {
                                            $statusClass = 'success-box';
                                            $statusLabel = 'Klar';
                                        } else {
                                            $statusClass = 'mute-box';
                                            $statusLabel = 'Venter';
                                        }

                                        // Format dates
                                        $startedAt = (int)$job->started_at > 0 ? date('d/m/Y H:i:s', (int)$job->started_at) : '-';
                                        $finishedAt = (int)$job->finished_at > 0 ? date('d/m/Y H:i:s', (int)$job->finished_at) : '-';

                                        // Format interval
                                        if ($timeGap < 60) {
                                            $intervalText = $timeGap . ' sek';
                                        } elseif ($timeGap < 3600) {
                                            $intervalText = floor($timeGap / 60) . ' min';
                                        } elseif ($timeGap < 86400) {
                                            $intervalText = floor($timeGap / 3600) . ' timer';
                                        } else {
                                            $intervalText = floor($timeGap / 86400) . ' dage';
                                        }
                                        ?>
                                        <tr>
                                            <td class="ps-3 py-3">
                                                <div class="flex-col-start">
                                                    <span class="font-14 font-weight-medium color-dark"><?=htmlspecialchars($job->name)?></span>
                                                    <span class="font-11 color-gray"><?=htmlspecialchars($type)?></span>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <span class="<?=$statusClass?> font-11"><?=$statusLabel?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-13 color-gray"><?=$intervalText?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-12 color-gray"><?=$startedAt?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-12 color-gray"><?=$finishedAt?></span>
                                            </td>
                                            <td class="pe-3 py-3 text-end">
                                                <button type="button"
                                                        class="btn-v2 outline-btn btn-sm mr-1"
                                                        onclick="viewLogs('<?=$type?>', '<?=htmlspecialchars($job->name)?>')"
                                                        title="Se logs">
                                                    <i class="mdi mdi-text-box-outline"></i>
                                                </button>
                                                <button type="button"
                                                        class="btn-v2 action-btn btn-sm"
                                                        onclick="forceRunCronjob('<?=$type?>', this)"
                                                        title="Kør nu"
                                                        <?=$isRunning ? 'disabled' : ''?>>
                                                    <i class="mdi mdi-play"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card border-radius-10px">
                    <div class="card-body py-3">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <i class="mdi mdi-information-outline font-18 color-gray"></i>
                            <p class="mb-0 font-13 color-gray">
                                <span class="success-box font-11">Klar</span> klar til næste kørsel &nbsp;
                                <span class="warning-box font-11">Kører</span> kører nu &nbsp;
                                <span class="mute-box font-11">Venter</span> afventer interval
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1" role="dialog" aria-labelledby="logsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logsModalTitle">Cronjob Logs</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="logsContent" class="font-13" style="white-space: pre-wrap; font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 500px; overflow-y: auto;">
                    Henter logs...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Luk</button>
            </div>
        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    $(document).ready(function() {
        initPanelJobs();
    });
</script>
<?php scriptEnd(); ?>
