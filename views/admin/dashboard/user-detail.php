<?php
/**
 * Admin Dashboard - User Detail
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;
use classes\utility\Misc;

$user = $args->user ?? null;
$organisationMemberships = $args->organisationMemberships ?? new \Database\Collection();
$orders = $args->orders ?? new \Database\Collection();
$stats = $args->stats ?? (object)['totalOrders' => 0, 'totalSpent' => 0];
$topLocations = $args->topLocations ?? [];

$pageTitle = $user ? ($user->full_name ?? 'Bruger detaljer') : 'Bruger detaljer';

$accessLevelLabels = [
    1 => ['label' => 'Forbruger', 'class' => 'info-box'],
    2 => ['label' => 'Forhandler', 'class' => 'success-box'],
    8 => ['label' => 'Admin', 'class' => 'warning-box'],
    9 => ['label' => 'Superadmin', 'class' => 'danger-box'],
];
$roleInfo = $accessLevelLabels[$user->access_level ?? 0] ?? ['label' => 'Ukendt', 'class' => 'mute-box'];
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "users";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->dashboardUsers)?>" class="font-13 color-gray hover-color-blue">Alle brugere</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=htmlspecialchars($user->full_name ?? 'Bruger')?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-start w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                    <div class="square-70 bg-light-gray border-radius-50 flex-row-center-center">
                        <i class="mdi mdi-account font-40 color-gray"></i>
                    </div>
                    <div class="flex-col-start">
                        <h1 class="mb-0 font-24 font-weight-bold"><?=htmlspecialchars($user->full_name ?? 'Unavngivet')?></h1>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="<?=$roleInfo['class']?> font-11"><?=$roleInfo['label']?></span>
                            <?php if($user->deactivated): ?>
                                <span class="danger-box font-11">Deaktiveret</span>
                            <?php else: ?>
                                <span class="success-box font-11">Aktiv</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                    <?php if(in_array((int)$user->access_level, [1, 2]) && !$user->deactivated): ?>
                        <button class="btn-v2 action-btn" onclick="startImpersonation()">
                            <i class="mdi mdi-account-switch mr-1"></i> Se som bruger
                        </button>
                    <?php endif; ?>
                    <?php if($user->deactivated): ?>
                        <button class="btn-v2 action-btn" onclick="toggleUserStatus('activate')">
                            <i class="mdi mdi-account-check mr-1"></i> Aktiver
                        </button>
                    <?php else: ?>
                        <button class="btn-v2 danger-btn" onclick="toggleUserStatus('deactivate')">
                            <i class="mdi mdi-account-off mr-1"></i> Deaktiver
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row rg-15">
                <!-- User Info Card -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="font-16 font-weight-bold mb-3">Bruger information</p>

                            <div class="flex-col-start" style="gap: 1rem;">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">UID</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=$user->uid?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Email</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($user->email ?? '-')?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Telefon</p>
                                    <p class="mb-0 font-14 font-weight-medium">
                                        <?php if(!empty($user->phone)): ?>
                                            <?=!empty($user->phone_country_code) ? '+' . Misc::callerCode($user->phone_country_code) . ' ' : ''?><?=htmlspecialchars($user->phone)?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Fødselsdato</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($user->birthdate ?? '-')?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Sprog</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=$user->lang === 'da' ? 'Dansk' : ($user->lang === 'en' ? 'English' : $user->lang)?></p>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Oprettet</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=date('d/m/Y H:i', strtotime($user->created_at))?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats & Activity -->
                <div class="col-12 col-lg-8">
                    <div class="row rg-15">
                        <!-- Stats Cards -->
                        <div class="col-6">
                            <div class="card border-radius-10px">
                                <div class="card-body">
                                    <div class="flex-row-between-center flex-nowrap g-075">
                                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                                            <p class="mb-0 font-12 color-gray text-wrap">Total ordrer</p>
                                            <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->totalOrders)?></p>
                                        </div>
                                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                            <i class="mdi mdi-cart-outline color-white font-22"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border-radius-10px">
                                <div class="card-body">
                                    <div class="flex-row-between-center flex-nowrap g-075">
                                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                                            <p class="mb-0 font-12 color-gray text-wrap">Total købt</p>
                                            <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->totalSpent, 2, ',', '.')?> kr</p>
                                        </div>
                                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                            <i class="mdi mdi-cash color-white font-22"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if((int)$user->access_level === 1): ?>
                        <!-- Top Locations for Consumers -->
                        <div class="col-12">
                            <div class="card border-radius-10px">
                                <div class="card-body">
                                    <p class="font-16 font-weight-bold mb-3">Top lokationer</p>
                                    <?php if(empty($topLocations)): ?>
                                        <p class="mb-0 font-14 color-gray">Ingen køb endnu</p>
                                    <?php else: ?>
                                        <div class="flex-col-start" style="gap: .75rem;">
                                            <?php foreach ($topLocations as $item): ?>
                                            <div class="flex-row-between flex-align-center p-3 bg-light-gray border-radius-8px">
                                                <div class="flex-col-start">
                                                    <a href="<?=__url(Links::$admin->dashboardLocationDetail($item->location->uid ?? ''))?>" class="mb-0 font-14 font-weight-medium color-dark hover-color-blue"><?=htmlspecialchars($item->location->name ?? 'Lokation')?></a>
                                                    <p class="mb-0 font-12 color-gray"><?=$item->orderCount?> <?=(int)$item->orderCount === 1 ? 'ordre' : 'ordrer'?></p>
                                                </div>
                                                <p class="mb-0 font-14 font-weight-bold"><?=number_format($item->totalAmount, 2, ',', '.')?> kr</p>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Organisations for Merchants -->
                        <div class="col-12">
                            <div class="card border-radius-10px">
                                <div class="card-body">
                                    <p class="font-16 font-weight-bold mb-3"><?=ucfirst(Translate::word("Organisationer"))?></p>
                                    <?php if($organisationMemberships->empty()): ?>
                                        <p class="mb-0 font-14 color-gray">Ingen organisationer</p>
                                    <?php else: ?>
                                        <div class="flex-col-start" style="gap: .75rem;">
                                            <?php foreach ($organisationMemberships->list() as $membership): ?>
                                            <div class="flex-row-between flex-align-center p-3 bg-light-gray border-radius-8px">
                                                <div class="flex-col-start">
                                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($membership->organisation->name ?? 'Organisation')?></p>
                                                    <p class="mb-0 font-12 color-gray">Rolle: <?=ucfirst(Translate::word($membership->role))?></p>
                                                </div>
                                                <a href="<?=__url(Links::$admin->dashboardOrganisationDetail($membership->organisation->uid ?? ''))?>" class="btn-v2 trans-btn font-12">
                                                    Se organisation
                                                </a>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if($user->access_level !== 2): // Hide orders for merchant users ?>
            <!-- Recent Orders -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-3">
                        <p class="mb-0 font-16 font-weight-bold">Seneste ordrer</p>
                        <a href="<?=__url(Links::$admin->dashboardOrders)?>?user=<?=$user->uid?>" class="btn-v2 trans-btn font-12">
                            Se alle ordrer
                        </a>
                    </div>

                    <?php if($orders->empty()): ?>
                        <div class="flex-col-center flex-align-center py-4">
                            <i class="mdi mdi-cart-off font-40 color-gray"></i>
                            <p class="mb-0 font-14 color-gray mt-2">Ingen ordrer endnu</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 plainDataTable" data-page-length="10">
                                <thead>
                                    <tr>
                                        <th class="font-12 font-weight-medium color-gray">Ordre ID</th>
                                        <th class="font-12 font-weight-medium color-gray">Beløb</th>
                                        <th class="font-12 font-weight-medium color-gray">Status</th>
                                        <th class="font-12 font-weight-medium color-gray">Dato</th>
                                        <th class="font-12 font-weight-medium color-gray text-right">Handling</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders->list() as $order): ?>
                                    <tr>
                                        <td class="font-13"><?=$order->uid?></td>
                                        <td class="font-13"><?=number_format($order->total_amount ?? 0, 2, ',', '.')?> kr</td>
                                        <td>
                                            <?php
                                            $statusClass = match($order->status ?? '') {
                                                'completed' => 'success-box',
                                                'pending' => 'warning-box',
                                                'cancelled' => 'danger-box',
                                                default => 'mute-box'
                                            };
                                            ?>
                                            <span class="<?=$statusClass?> font-11"><?=ucfirst($order->status ?? 'Unknown')?></span>
                                        </td>
                                        <td class="font-13"><?=date('d/m/Y H:i', strtotime($order->created_at))?></td>
                                        <td class="text-right">
                                            <a href="<?=__url(Links::$admin->dashboardOrderDetail($order->uid))?>" class="btn-v2 trans-btn font-12">
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
            <?php endif; ?>

        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    function toggleUserStatus(action) {
        const userId = '<?=$user->uid?>';
        const confirmMsg = action === 'activate'
            ? 'Er du sikker på at du vil aktivere denne bruger?'
            : 'Er du sikker på at du vil deaktivere denne bruger?';

        if (confirm(confirmMsg)) {
            // TODO: Implement API call to toggle user status
            console.log('Toggle user status:', action, userId);
        }
    }

    function startImpersonation() {
        const userId = '<?=$user->uid?>';
        const userName = '<?=htmlspecialchars($user->full_name ?? 'denne bruger')?>';

        SweetPrompt.confirm('Se som bruger?', `Du vil nu logge ind som ${userName}. Du kan til enhver tid afslutte via banneret øverst på siden.`, {
            confirmButtonText: 'Fortsæt',
            onConfirm: async () => {
                const result = await post('<?=__url(Links::$api->admin->impersonate->start)?>', { user: userId });
                if (result.data && result.data.redirect) {
                    result.redirect = result.data.redirect;
                }
                return result;
            },
            success: {
                title: 'Skifter...',
                text: 'Du omdirigeres nu.'
            },
            error: {
                title: 'Fejl',
                text: '<_ERROR_MSG_>'
            },
            refireAfter: false
        });
    }
</script>
<?php scriptEnd(); ?>
