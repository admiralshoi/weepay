<?php
/**
 * Admin Panel - Notification Logs
 * View history of sent notifications
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Notifikationslogs";
$sentLast24h = $args->sentLast24h ?? 0;
$sentLast7d = $args->sentLast7d ?? 0;
$failedLast24h = $args->failedLast24h ?? 0;
$failedLast7d = $args->failedLast7d ?? 0;
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
                <span class="font-13 color-dark">Logs</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Notifikationslogs</h1>
                    <p class="mb-0 font-14 color-gray">Historik over sendte notifikationer</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row" style="row-gap: 1rem;">
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Sendt (24t)</p>
                            <p class="mb-0 font-24 font-weight-bold color-green"><?=$sentLast24h?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Sendt (7d)</p>
                            <p class="mb-0 font-24 font-weight-bold color-green"><?=$sentLast7d?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Fejlet (24t)</p>
                            <p class="mb-0 font-24 font-weight-bold color-red"><?=$failedLast24h?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Fejlet (7d)</p>
                            <p class="mb-0 font-24 font-weight-bold color-red"><?=$failedLast7d?></p>
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
                                <option value="sent">Sendt</option>
                                <option value="failed">Fejlet</option>
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
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <label class="font-12 color-gray mb-0">Modtager:</label>
                            <input type="text" id="recipient-filter" class="form-control form-control-sm" placeholder="Søg..." style="width: 150px;">
                        </div>
                        <button type="button" class="btn-v2 action-btn btn-sm" onclick="loadLogs()">
                            <i class="mdi mdi-magnify mr-1"></i> Søg
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div id="logs-loading" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm color-blue" role="status"></div>
                        <p class="mb-0 font-13 color-gray mt-2">Indlæser logs...</p>
                    </div>
                    <div id="logs-empty" class="text-center py-5 d-none">
                        <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3 mx-auto">
                            <i class="mdi mdi-history font-28 color-gray"></i>
                        </div>
                        <p class="mb-0 font-16 font-weight-bold color-dark">Ingen logs</p>
                        <p class="mb-0 font-13 color-gray mt-1">Ingen notifikationer fundet med de valgte filtre</p>
                    </div>
                    <div id="logs-table" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="font-12 color-gray font-weight-normal border-0 ps-3">Modtager</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Kanal</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Emne</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Breakpoint</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                        <th class="font-12 color-gray font-weight-normal border-0 pe-3">Tidspunkt</th>
                                    </tr>
                                </thead>
                                <tbody id="logs-body"></tbody>
                            </table>
                        </div>
                        <div id="logs-pagination" class="p-3 border-top"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    var currentPage = 1;

    async function loadLogs() {
        var status = document.getElementById('status-filter').value;
        var channel = document.getElementById('channel-filter').value;
        var recipient = document.getElementById('recipient-filter').value;

        document.getElementById('logs-loading').classList.remove('d-none');
        document.getElementById('logs-empty').classList.add('d-none');
        document.getElementById('logs-table').classList.add('d-none');

        try {
            var data = await post('api/admin/notifications/logs/list', {page: currentPage, status: status, channel: channel, recipient: recipient});
            document.getElementById('logs-loading').classList.add('d-none');
            if (data.status === 'success' && data.data.logs.length > 0) {
                renderLogs(data.data.logs, data.data.pagination);
            } else {
                document.getElementById('logs-empty').classList.remove('d-none');
            }
        } catch(e) {
            document.getElementById('logs-loading').classList.add('d-none');
            document.getElementById('logs-empty').classList.remove('d-none');
        }
    }

    function renderLogs(logs, pagination) {
        var tbody = document.getElementById('logs-body');
        tbody.innerHTML = '';

        var channelLabels = {email: 'E-mail', sms: 'SMS', bell: 'Push'};
        var statusLabels = {sent: 'Sendt', failed: 'Fejlet'};
        var statusClasses = {sent: 'success-box', failed: 'danger-box'};

        logs.forEach(function(log) {
            var recipient = log.recipient_identifier || log.recipient || '-';
            var created = new Date(log.created_at * 1000).toLocaleString('da-DK');
            var row = document.createElement('tr');
            row.innerHTML = '<td class="ps-3 py-3"><span class="font-13">' + recipient + '</span></td>' +
                '<td class="py-3"><span class="font-13">' + (channelLabels[log.channel] || log.channel) + '</span></td>' +
                '<td class="py-3"><span class="font-13 color-gray">' + (log.subject || '-') + '</span></td>' +
                '<td class="py-3"><code class="font-11 bg-light-gray px-2 py-1 border-radius-4px">' + (log.breakpoint_key || '-') + '</code></td>' +
                '<td class="py-3"><span class="' + (statusClasses[log.status] || 'mute-box') + ' font-11">' + (statusLabels[log.status] || log.status) + '</span></td>' +
                '<td class="pe-3 py-3"><span class="font-12 color-gray">' + created + '</span></td>';
            tbody.appendChild(row);
        });

        document.getElementById('logs-table').classList.remove('d-none');

        // Render pagination
        var paginationDiv = document.getElementById('logs-pagination');
        if (pagination.totalPages > 1) {
            var html = '<div class="flex-row-center-center" style="gap: .5rem;">';
            html += '<button class="btn-v2 secondary-btn btn-sm" ' + (pagination.page <= 1 ? 'disabled' : 'onclick="goToPage(' + (pagination.page - 1) + ')"') + '><i class="mdi mdi-chevron-left"></i></button>';
            html += '<span class="font-13 color-gray">Side ' + pagination.page + ' af ' + pagination.totalPages + '</span>';
            html += '<button class="btn-v2 secondary-btn btn-sm" ' + (pagination.page >= pagination.totalPages ? 'disabled' : 'onclick="goToPage(' + (pagination.page + 1) + ')"') + '><i class="mdi mdi-chevron-right"></i></button>';
            html += '</div>';
            paginationDiv.innerHTML = html;
            paginationDiv.style.display = 'block';
        } else {
            paginationDiv.style.display = 'none';
        }
    }

    function goToPage(page) {
        currentPage = page;
        loadLogs();
    }

    document.getElementById('status-filter').addEventListener('change', function() { currentPage = 1; loadLogs(); });
    document.getElementById('channel-filter').addEventListener('change', function() { currentPage = 1; loadLogs(); });

    loadLogs();
</script>
