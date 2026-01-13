<?php
/**
 * Admin Panel - Notification Queue
 * Monitor and manage the notification queue
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Notifikationskø";
$pendingCount = $args->pendingCount ?? 0;
$processingCount = $args->processingCount ?? 0;
$failedCount = $args->failedCount ?? 0;
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
                <span class="font-13 color-dark">Kø</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Notifikationskø</h1>
                    <p class="mb-0 font-14 color-gray">Overvåg planlagte og afventende notifikationer</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row" style="row-gap: 1rem;">
                <div class="col-12 col-md-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                <div class="square-40 bg-pee-yellow border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-clock-outline color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-24 font-weight-bold"><?=$pendingCount?></p>
                                    <p class="mb-0 font-12 color-gray">Afventer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-sync color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-24 font-weight-bold"><?=$processingCount?></p>
                                    <p class="mb-0 font-12 color-gray">Behandles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                <div class="square-40 bg-red border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-alert-outline color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-24 font-weight-bold"><?=$failedCount?></p>
                                    <p class="mb-0 font-12 color-gray">Fejlet</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-radius-10px">
                <div class="card-body py-2">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <label class="font-12 color-gray mb-0">Status:</label>
                            <select id="status-filter" class="form-control form-control-sm" style="width: auto;">
                                <option value="">Alle</option>
                                <option value="pending">Afventer</option>
                                <option value="processing">Behandles</option>
                                <option value="failed">Fejlet</option>
                                <option value="sent">Sendt</option>
                                <option value="cancelled">Annulleret</option>
                            </select>
                        </div>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <label class="font-12 color-gray mb-0">Kanal:</label>
                            <select id="channel-filter" class="form-control form-control-sm" style="width: auto;">
                                <option value="">Alle</option>
                                <option value="email">E-mail</option>
                                <option value="sms">SMS</option>
                                <option value="bell">Push</option>
                            </select>
                        </div>
                        <button type="button" class="btn-v2 action-btn btn-sm" onclick="loadQueue()">
                            <i class="mdi mdi-refresh mr-1"></i> Opdater
                        </button>
                    </div>
                </div>
            </div>

            <!-- Queue Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div id="queue-loading" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm color-blue" role="status"></div>
                        <p class="mb-0 font-13 color-gray mt-2">Indlæser kø...</p>
                    </div>
                    <div id="queue-empty" class="text-center py-5 d-none">
                        <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3 mx-auto">
                            <i class="mdi mdi-tray font-28 color-gray"></i>
                        </div>
                        <p class="mb-0 font-16 font-weight-bold color-dark">Køen er tom</p>
                        <p class="mb-0 font-13 color-gray mt-1">Ingen notifikationer i køen med de valgte filtre</p>
                    </div>
                    <div id="queue-table" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="font-12 color-gray font-weight-normal border-0 ps-3">Modtager</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Kanal</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Emne</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Planlagt</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                        <th class="font-12 color-gray font-weight-normal border-0 pe-3"></th>
                                    </tr>
                                </thead>
                                <tbody id="queue-body"></tbody>
                            </table>
                        </div>
                        <div id="queue-pagination" class="p-3 border-top"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    var currentPage = 1;

    async function loadQueue() {
        var status = document.getElementById('status-filter').value;
        var channel = document.getElementById('channel-filter').value;

        document.getElementById('queue-loading').classList.remove('d-none');
        document.getElementById('queue-empty').classList.add('d-none');
        document.getElementById('queue-table').classList.add('d-none');

        try {
            var data = await post('api/admin/notifications/queue/list', {page: currentPage, status: status, channel: channel});
            document.getElementById('queue-loading').classList.add('d-none');
            if (data.status === 'success' && data.data.items.length > 0) {
                renderQueue(data.data.items, data.data.pagination);
            } else {
                document.getElementById('queue-empty').classList.remove('d-none');
            }
        } catch(e) {
            document.getElementById('queue-loading').classList.add('d-none');
            document.getElementById('queue-empty').classList.remove('d-none');
        }
    }

    function renderQueue(items, pagination) {
        var tbody = document.getElementById('queue-body');
        tbody.innerHTML = '';

        var channelLabels = {email: 'E-mail', sms: 'SMS', bell: 'Push'};
        var statusLabels = {pending: 'Afventer', processing: 'Behandles', sent: 'Sendt', failed: 'Fejlet', cancelled: 'Annulleret'};
        var statusClasses = {pending: 'warning-box', processing: 'action-box', sent: 'success-box', failed: 'danger-box', cancelled: 'mute-box'};

        items.forEach(function(item) {
            var recipient = item.recipient_email || item.recipient_phone || item.recipient || '-';
            var scheduled = new Date(item.scheduled_at * 1000).toLocaleString('da-DK');
            var row = document.createElement('tr');
            row.innerHTML = '<td class="ps-3 py-3"><span class="font-13">' + recipient + '</span></td>' +
                '<td class="py-3"><span class="font-13">' + (channelLabels[item.channel] || item.channel) + '</span></td>' +
                '<td class="py-3"><span class="font-13 color-gray">' + (item.subject || '-') + '</span></td>' +
                '<td class="py-3"><span class="font-12 color-gray">' + scheduled + '</span></td>' +
                '<td class="py-3"><span class="' + (statusClasses[item.status] || 'mute-box') + ' font-11">' + (statusLabels[item.status] || item.status) + '</span></td>' +
                '<td class="pe-3 py-3 text-end">' + (item.status === 'pending' ? '<button type="button" class="btn-v2 danger-btn btn-sm" onclick="cancelItem(\'' + item.uid + '\')"><i class="mdi mdi-close"></i></button>' : '') + '</td>';
            tbody.appendChild(row);
        });

        document.getElementById('queue-table').classList.remove('d-none');
    }

    async function cancelItem(uid) {
        if (!confirm('Vil du annullere denne notifikation?')) return;
        var data = await post('api/admin/notifications/queue/cancel', {uid: uid});
        if (data.status === 'success') {
            loadQueue();
        } else {
            alert(data.message || 'Fejl ved annullering');
        }
    }

    document.getElementById('status-filter').addEventListener('change', function() { currentPage = 1; loadQueue(); });
    document.getElementById('channel-filter').addEventListener('change', function() { currentPage = 1; loadQueue(); });

    loadQueue();
</script>
