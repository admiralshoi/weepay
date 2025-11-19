<?php
/**
 * @var object $args
 */

$pageTitle = "404 Page not found";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "404";
</script>
<style>
    .error-container {
        max-width: 600px;
        text-align: center;
    }
    .error-icon {
        color: #6C48FF;
        font-size: 48px;
        margin-bottom: 16px;
    }
    .error-heading {
        color: #6C48FF;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 16px;
    }
    .error-message {
        color: #333;
        font-size: 16px;
        margin-bottom: 24px;
    }
    .return-btn {
        background: #6C48FF;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        text-decoration: none;
    }
    .return-btn:hover {
        background: #5A3ECC;
        color: #fff;
    }
</style>
<div class="page-content position-relative" data-page="404">

    <div class="" style="flex: 3; padding: 40px; display: flex; justify-content: center; align-items: center">
        <div class="error-container">
            <div class="error-container">
                <span class="error-icon">❓</span>
                <h1 class="error-heading">404 - Page Not Found</h1>
                <p class="error-message">Oops! The page you’re looking for doesn’t exist or has been removed.</p>
                <a href="<?=__url('')?>" class="return-btn">Return to the main page</a>
            </div>
        </div>
    </div>

</div>