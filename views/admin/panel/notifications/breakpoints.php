<?php
/**
 * Admin Panel - Notification Breakpoints
 * List all available notification breakpoints (triggers)
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Breakpoints";
$breakpoints = $args->breakpoints ?? new \Database\Collection();

$categoryLabels = [
    'user' => 'Bruger',
    'order' => 'Ordre',
    'payment' => 'Betaling',
    'organisation' => 'Organisation',
    'system' => 'System'
];

$categoryIcons = [
    'user' => 'mdi-account-outline',
    'order' => 'mdi-cart-outline',
    'payment' => 'mdi-credit-card-outline',
    'organisation' => 'mdi-domain',
    'system' => 'mdi-cog-outline'
];

$categoryColors = [
    'user' => 'blue',
    'order' => 'green',
    'payment' => 'purple',
    'organisation' => 'pee-yellow',
    'system' => 'gray'
];

$groupedBreakpoints = [];
foreach ($breakpoints->list() as $bp) {
    $category = $bp->category ?? 'system';
    if (!isset($groupedBreakpoints[$category])) {
        $groupedBreakpoints[$category] = [];
    }
    $groupedBreakpoints[$category][] = $bp;
}
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
                <span class="font-13 color-dark">Breakpoints</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Breakpoints</h1>
                    <p class="mb-0 font-14 color-gray">Triggerpunkter der kan aktivere notifikationsflows</p>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card border-radius-10px bg-blue-light">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <i class="mdi mdi-information-outline font-20 color-blue"></i>
                        <p class="mb-0 font-13 color-dark">
                            Breakpoints er foruddefinerede punkter i systemet hvor notifikationer kan sendes.
                            Brug disse breakpoints når du opretter flows til at trigge automatiske notifikationer.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Breakpoints by Category -->
            <?php foreach ($groupedBreakpoints as $category => $bps): ?>
                <?php
                $catLabel = $categoryLabels[$category] ?? ucfirst($category);
                $catIcon = $categoryIcons[$category] ?? 'mdi-tag-outline';
                $catColor = $categoryColors[$category] ?? 'gray';
                ?>
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                            <div class="square-40 bg-<?=$catColor?> border-radius-8px flex-row-center-center">
                                <i class="mdi <?=$catIcon?> color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold"><?=$catLabel?></p>
                                <p class="mb-0 font-12 color-gray"><?=count($bps)?> breakpoints</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="font-12 color-gray font-weight-normal border-0">Key</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Navn</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Beskrivelse</th>
                                        <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bps as $bp): ?>
                                        <tr>
                                            <td class="py-3">
                                                <code class="font-12 bg-light-gray px-2 py-1 border-radius-4px"><?=htmlspecialchars($bp->key)?></code>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-14 font-weight-medium"><?=htmlspecialchars($bp->name)?></span>
                                            </td>
                                            <td class="py-3">
                                                <span class="font-13 color-gray"><?=htmlspecialchars($bp->description ?? '-')?></span>
                                            </td>
                                            <td class="py-3">
                                                <?php if ($bp->status === 'active'): ?>
                                                    <span class="success-box font-11">Aktiv</span>
                                                <?php else: ?>
                                                    <span class="mute-box font-11">Inaktiv</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($groupedBreakpoints)): ?>
                <div class="card border-radius-10px">
                    <div class="card-body flex-col-center flex-align-center py-5">
                        <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                            <i class="mdi mdi-map-marker-path font-28 color-gray"></i>
                        </div>
                        <p class="mb-0 font-16 font-weight-bold color-dark">Ingen breakpoints</p>
                        <p class="mb-0 font-13 color-gray mt-1">Kør database migreringen for at oprette system breakpoints</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
