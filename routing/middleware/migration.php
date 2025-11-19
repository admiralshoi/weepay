<?php


function migrating(): bool {
    return isset($_SESSION["migrating"]) && $_SESSION["migrating"] === MIGRATION_TOKEN;
}