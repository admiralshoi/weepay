<?php
/**
 * Shared template for public policy pages
 * Variables from $args:
 * @var object|null $args->policy - Current published policy
 * @var object|null $args->previousPolicy - Previous archived policy
 * @var string $args->fallbackTitle - Title to show if no policy exists
 */


use classes\Methods;

// Extract from $args (the only variable available in views)
$policy = $args->policy ?? null;
$previousPolicy = $args->previousPolicy ?? null;
$fallbackTitle = $args->fallbackTitle ?? 'Politik';

$hasPolicy = !empty($policy);
$hasPrevious = !empty($previousPolicy);
$pageTitle = $hasPolicy ? $policy->title : $fallbackTitle;

// Check if this is a scheduled (not yet published) version
$isScheduled = $hasPolicy && $policy->status === 'draft';

// updated_at is when the content was last saved, active_from is when it was published, active_until is when it was archived
$updatedDate = $hasPolicy && $policy->updated_at ? date('d. F Y', strtotime($policy->updated_at)) : null;
$publishedDate = $hasPolicy && $policy->active_from ? date('d. F Y', strtotime($policy->active_from)) : null;
$archivedDate = $hasPolicy && $policy->active_until ? date('d. F Y', strtotime($policy->active_until)) : null;
$previousDate = $hasPrevious && $previousPolicy->active_from ? date('d. F Y', strtotime($previousPolicy->active_from)) : null;

// Get policy type to check for scheduled_at
$policyTypeUid = $hasPolicy ? (is_object($policy->policy_type) ? $policy->policy_type->uid : $policy->policy_type) : null;
$policyType = $policyTypeUid ? Methods::policyTypes()->excludeForeignKeys()->get($policyTypeUid) : null;

// For scheduled versions, show when it will become active
$scheduledDate = null;
if ($isScheduled && $policyType && $policyType->scheduled_at) {
    $scheduledDate = date('d. F Y', strtotime($policyType->scheduled_at));
}

// For current published version, if there's a scheduled replacement, show when current expires
if (!$archivedDate && $hasPolicy && $policy->status === 'published' && $policyType && $policyType->scheduled_at) {
    $archivedDate = date('d. F Y', strtotime($policyType->scheduled_at));
}

// Map Danish month names
$danishMonths = [
    'January' => 'januar', 'February' => 'februar', 'March' => 'marts',
    'April' => 'april', 'May' => 'maj', 'June' => 'juni',
    'July' => 'juli', 'August' => 'august', 'September' => 'september',
    'October' => 'oktober', 'November' => 'november', 'December' => 'december'
];
if ($updatedDate) {
    $updatedDate = str_replace(array_keys($danishMonths), array_values($danishMonths), $updatedDate);
}
if ($publishedDate) {
    $publishedDate = str_replace(array_keys($danishMonths), array_values($danishMonths), $publishedDate);
}
if ($previousDate) {
    $previousDate = str_replace(array_keys($danishMonths), array_values($danishMonths), $previousDate);
}
if ($scheduledDate) {
    $scheduledDate = str_replace(array_keys($danishMonths), array_values($danishMonths), $scheduledDate);
}
if ($archivedDate) {
    $archivedDate = str_replace(array_keys($danishMonths), array_values($danishMonths), $archivedDate);
}
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<style>
    .policy-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }
    .policy-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    .policy-header h1 {
        font-size: 2rem;
        font-weight: bold;
        margin: 0;
        color: #1a1a1a;
    }
    .policy-header .policy-updated {
        text-align: right;
        font-size: 0.85rem;
        color: #6c757d;
        white-space: nowrap;
        margin-left: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .policy-header .policy-updated span {
        display: block;
    }
    .policy-header .policy-updated strong {
        color: #495057;
    }
    .policy-container h1 {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #1a1a1a;
    }
    .policy-container h2 {
        font-size: 1.75rem;
        font-weight: bold;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        color: #2c3e50;
    }
    .policy-container h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #34495e;
    }
    .policy-container p {
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 1rem;
        color: #4a4a4a;
    }
    .policy-container ul, .policy-container ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }
    .policy-container li {
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 0.5rem;
        color: #4a4a4a;
    }
    .policy-container table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
    }
    .policy-container th, .policy-container td {
        border: 1px solid #ddd;
        padding: 0.75rem;
        text-align: left;
    }
    .policy-container th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .policy-container hr {
        border: none;
        border-top: 2px solid #e9ecef;
        margin: 2rem 0;
    }
    .policy-meta {
        font-size: 0.95rem;
        color: #6c757d;
        margin-bottom: 2rem;
    }
    .policy-meta strong {
        font-weight: 600;
    }
    .policy-version-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        background: #e9ecef;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #495057;
    }
    .previous-version-section {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 2px solid #e9ecef;
    }
    .previous-version-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        cursor: pointer;
        width: 100%;
        text-align: left;
        transition: background-color 0.2s;
    }
    .previous-version-toggle:hover {
        background: #e9ecef;
    }
    .previous-version-toggle i {
        font-size: 1.25rem;
        color: #6c757d;
        transition: transform 0.2s;
    }
    .previous-version-toggle.expanded i {
        transform: rotate(180deg);
    }
    .previous-version-content {
        display: none;
        padding: 1.5rem;
        background: #fafafa;
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 8px 8px;
    }
    .previous-version-content.show {
        display: block;
    }
    .no-policy-message {
        text-align: center;
        padding: 4rem 2rem;
    }
    .no-policy-message i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
</style>

<div class="page-content">
    <div class="policy-container my-5">
        <?php if ($hasPolicy): ?>
            <div class="policy-header">
                <h1><?=$pageTitle?></h1>
                <?php if ($updatedDate || $publishedDate || $scheduledDate): ?>
                    <div class="policy-updated">
                        <?php if ($updatedDate): ?>
                            <span>Senest opdateret: <strong><?=$updatedDate?></strong></span>
                        <?php endif; ?>
                        <?php if ($scheduledDate): ?>
                            <span>Gældende fra: <strong><?=$scheduledDate?></strong></span>
                        <?php elseif ($publishedDate): ?>
                            <span>Gældende fra: <strong><?=$publishedDate?></strong></span>
                        <?php endif; ?>
                        <?php if ($archivedDate): ?>
                            <span>Gældende til: <strong><?=$archivedDate?></strong></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="policy-content">
                <?=Methods::policyVersions()->renderContent($policy->content)?>
            </div>

            <?php if ($hasPrevious): ?>
                <div class="previous-version-section">
                    <button type="button" class="previous-version-toggle" onclick="togglePreviousVersion(this)">
                        <i class="mdi mdi-chevron-down"></i>
                        <div>
                            <strong>Tidligere version</strong>
                            <?php if ($previousDate): ?>
                                <span class="color-gray font-14"> - Publiceret <?=$previousDate?></span>
                            <?php endif; ?>
                        </div>
                    </button>
                    <div class="previous-version-content policy-container" style="max-width: 100%; padding-top: 1rem;">
                        <?=Methods::policyVersions()->renderContent($previousPolicy->content)?>
                    </div>
                </div>

                <script>
                    function togglePreviousVersion(btn) {
                        btn.classList.toggle('expanded');
                        var content = btn.nextElementSibling;
                        content.classList.toggle('show');
                    }
                </script>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-policy-message">
                <i class="mdi mdi-file-document-outline"></i>
                <h2><?=$fallbackTitle ?? 'Politik'?></h2>
                <p class="color-gray">Denne politik er ikke tilgængelig endnu.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
