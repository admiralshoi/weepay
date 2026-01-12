<?php
/**
 * Admin Dashboard - Location Detail
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$location = $args->location ?? null;
$members = $args->members ?? new \Database\Collection();
$stats = $args->stats ?? (object)['totalMembers' => 0, 'orderRevenue' => 0, 'orderIsv' => 0, 'paymentRevenue' => 0, 'paymentIsv' => 0, 'totalOrders' => 0];
$startDate = $args->startDate ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $args->endDate ?? date('Y-m-d');

$pageTitle = $location ? ($location->name ?? 'Lokation detaljer') : 'Lokation detaljer';

$statusLabels = [
    'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
    'ACTIVE' => ['label' => 'Aktiv', 'class' => 'success-box'],
    'INACTIVE' => ['label' => 'Inaktiv', 'class' => 'warning-box'],
    'DELETED' => ['label' => 'Slettet', 'class' => 'danger-box'],
];
$statusInfo = $statusLabels[$location->status ?? 'DRAFT'] ?? ['label' => 'Ukendt', 'class' => 'mute-box'];

// Get organisation info
$orgName = is_object($location->uuid) ? ($location->uuid->name ?? '-') : '-';
$orgUid = is_object($location->uuid) ? $location->uuid->uid : $location->uuid;
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->dashboardLocations)?>" class="font-13 color-gray hover-color-blue">Lokationer</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=htmlspecialchars($location->name ?? 'Lokation')?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-start w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                    <div class="square-70 bg-light-gray border-radius-8px flex-row-center-center">
                        <i class="mdi mdi-map-marker font-40 color-warning"></i>
                    </div>
                    <div class="flex-col-start">
                        <h1 class="mb-0 font-24 font-weight-bold"><?=htmlspecialchars($location->name ?? 'Unavngivet')?></h1>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="<?=$statusInfo['class']?> font-11"><?=$statusInfo['label']?></span>
                            <span class="font-12 color-gray"><?=$location->slug?></span>
                        </div>
                    </div>
                </div>
                <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                    <input type="text" class="form-field-v2" id="date-range-picker" style="min-width: 220px;" readonly>
                    <?php if($location->status === 'ACTIVE'): ?>
                        <button class="btn-v2 danger-btn" onclick="updateLocStatus('INACTIVE')">
                            <i class="mdi mdi-pause mr-1"></i> Deaktiver
                        </button>
                    <?php elseif($location->status === 'INACTIVE' || $location->status === 'DRAFT'): ?>
                        <button class="btn-v2 action-btn" onclick="updateLocStatus('ACTIVE')">
                            <i class="mdi mdi-play mr-1"></i> Aktiver
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Stats Row - Date filtered -->
            <div class="row flex-align-stretch rg-15">
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Ordre omsætning</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->orderRevenue, 2, ',', '.')?> kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cart-outline color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Ordre profit</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->orderIsv, 2, ',', '.')?> kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cash color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Medarbejdere</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->totalMembers)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-account-group color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Stats Row - Date filtered -->
            <div class="row flex-align-stretch rg-15">
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Realiseret omsætning</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->paymentRevenue, 2, ',', '.')?> kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-credit-card color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Realiseret profit</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->paymentIsv, 2, ',', '.')?> kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cash-multiple color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Ordrer</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->totalOrders)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-orange border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-receipt color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row rg-15">
                <!-- Location Info -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="font-16 font-weight-bold mb-3">Lokation information</p>

                            <div class="flex-col-start" style="gap: 1rem;">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray"><?=Translate::word("Organisation")?></p>
                                    <a href="<?=__url(Links::$admin->dashboardOrganisationDetail($orgUid))?>" class="font-14 font-weight-medium color-blue hover-underline">
                                        <?=htmlspecialchars($orgName)?>
                                    </a>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Navn</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->name ?? '-')?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Slug</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->slug ?? '-')?></p>
                                </div>
                                <?php if(!empty($location->description)): ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Beskrivelse</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->description)?></p>
                                </div>
                                <?php endif; ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Branche</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->industry ?? '-')?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">CVR</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->cvr ?? '-')?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Kontakt email</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->contact_email ?? '-')?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Kontakt telefon</p>
                                    <p class="mb-0 font-14 font-weight-medium">
                                        <?=!empty($location->contact_phone_country_code) ? '+' . $location->contact_phone_country_code . ' ' : ''?>
                                        <?=htmlspecialchars($location->contact_phone ?? '-')?>
                                    </p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Oprettet</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=date('d/m/Y H:i', strtotime($location->created_at))?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members -->
                <div class="col-12 col-lg-8">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center mb-3">
                                <p class="mb-0 font-16 font-weight-bold">Medarbejdere</p>
                            </div>

                            <?php if($members->empty()): ?>
                                <div class="flex-col-center flex-align-center py-4">
                                    <i class="mdi mdi-account-off font-40 color-gray"></i>
                                    <p class="mb-0 font-14 color-gray mt-2">Ingen medarbejdere</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 plainDataTable" data-pagination-limit="10">
                                        <thead>
                                            <tr>
                                                <th class="font-12 font-weight-medium color-gray">Bruger</th>
                                                <th class="font-12 font-weight-medium color-gray">Rolle</th>
                                                <th class="font-12 font-weight-medium color-gray">Status</th>
                                                <th class="font-12 font-weight-medium color-gray text-right">Handling</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($members->list() as $member): ?>
                                            <tr>
                                                <td>
                                                    <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                        <div class="square-30 bg-light-gray border-radius-50 flex-row-center-center">
                                                            <i class="mdi mdi-account font-16 color-gray"></i>
                                                        </div>
                                                        <span class="font-13"><?=htmlspecialchars($member->uuid->full_name ?? $member->uuid ?? 'Unavngivet')?></span>
                                                    </div>
                                                </td>
                                                <td><span class="font-13"><?=ucfirst($member->role ?? '-')?></span></td>
                                                <td>
                                                    <?php if($member->status === 'active'): ?>
                                                        <span class="success-box font-10">Aktiv</span>
                                                    <?php else: ?>
                                                        <span class="mute-box font-10"><?=ucfirst($member->status ?? '-')?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <a href="<?=__url(Links::$admin->dashboardUserDetail(is_object($member->uuid) ? $member->uuid->uid : $member->uuid))?>" class="btn-v2 trans-btn font-11">
                                                        <i class="mdi mdi-eye-outline"></i>
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
    </div>
</div>

<?php scriptStart(); ?>
<script>
    // Initialize daterangepicker
    $(document).ready(function() {
        const startDate = moment('<?=$startDate?>', 'YYYY-MM-DD');
        const endDate = moment('<?=$endDate?>', 'YYYY-MM-DD');

        const picker = $('#date-range-picker').daterangepicker({
            startDate: startDate,
            endDate: endDate,
            ranges: {
                'Sidste 7 dage': [moment().subtract(6, 'days'), moment()],
                'Sidste 30 dage': [moment().subtract(29, 'days'), moment()],
                'Denne måned': [moment().startOf('month'), moment().endOf('month')],
                'Sidste måned': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Sidste 3 måneder': [moment().subtract(3, 'months'), moment()],
                'År til dato': [moment().startOf('year'), moment()]
            },
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Anvend',
                cancelLabel: 'Annuller',
                customRangeLabel: 'Vælg periode',
                daysOfWeek: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø'],
                monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December']
            }
        }, function(start, end) {
            // Navigate to same page with new dates
            const url = new URL(window.location.href);
            url.searchParams.set('start', start.format('YYYY-MM-DD'));
            url.searchParams.set('end', end.format('YYYY-MM-DD'));
            window.location.href = url.toString();
        });

        // Set the input value to show current date range
        $('#date-range-picker').val(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format('DD/MM/YYYY'));
    });

    function updateLocStatus(status) {
        const locId = '<?=$location->uid?>';
        const isActivating = status === 'ACTIVE';
        const title = isActivating ? 'Aktiver lokation?' : 'Deaktiver lokation?';
        const message = isActivating
            ? 'Er du sikker på at du vil aktivere denne lokation?'
            : 'Er du sikker på at du vil deaktivere denne lokation? Lokationen vil ikke længere kunne bruges.';

        SweetPrompt.confirm(title, message, {
            confirmButtonText: isActivating ? 'Aktiver' : 'Deaktiver',
            onConfirm: async () => {
                const result = await post(`api/admin/location/${locId}/status`, { status });
                return result;
            },
            success: {
                title: isActivating ? 'Aktiveret!' : 'Deaktiveret!',
                text: isActivating ? 'Lokationen er nu aktiv.' : 'Lokationen er nu deaktiveret.'
            },
            error: {
                title: 'Fejl',
                text: '<_ERROR_MSG_>'
            },
            refreshTimeout: 1500,
            refireAfter: false
        });
    }
</script>
<?php scriptEnd(); ?>
