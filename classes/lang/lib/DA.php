<?php

namespace classes\lang\lib;

class DA {

    const CONTEXT = [
        "frontpage" => [
            "title" => "Titel"
        ],
        "footer_info" => "Sidefod info",
        "payment_start" => [
            "now" => "Nu",
            "first day of next month" => "1. i næste måned",
        ],
        "checkout" => [
            "status" => [
                "void" => "Annulleret",
                "pending" => "Afventer",
                "active" => "Afventer",
                "draft" => "Udkast",
                "completed" => "Fuldført",
            ],
        ],
        "order" => [
            "installments" => "betalingsrater",
            "pushed" => "Udskudt",
            "direct" => "Direkte"
        ],
        // Location-specific terminology (employees instead of members)
        "location" => [
            "team" => "Medarbejdere",
            "members" => "Medarbejdere",
            "member" => "Medarbejder",
            "team_members" => "Medarbejdere",
            "team_invitations" => "Invitationer",
            "invite_member" => "Inviter Medarbejder",
            "add_member" => "Tilføj Medarbejder",
            "no_members" => "Ingen medarbejdere",
            "loading_members" => "Indlæser medarbejdere...",
            "adding_member" => "Tilføjer medarbejder...",
            "updating_member" => "Opdaterer medarbejder...",
            "member_added" => "Medarbejderen er blevet tilføjet til lokationen.",
            "member_reactivated" => "Medarbejderen er blevet genaktiveret på lokationen.",
            "member_suspended" => "Medarbejderen er blevet suspenderet fra lokationen.",
            "member_removed" => "Medarbejderen er blevet fjernet fra lokationen.",
            "member_role_updated" => "Medarbejderens rolle er blevet opdateret.",
            "member_already_added" => "Denne medarbejder er allerede tilføjet til lokationen.",
            "member_not_found" => "Medarbejderen blev ikke fundet.",
            "select_member" => "Vælg venligst en medarbejder.",
            "no_permission_view" => "Du har ikke tilladelse til at se medarbejdere på denne lokation.",
            "no_permission_invite" => "Du har ikke tilladelse til at invitere medarbejdere til denne lokation.",
            "role_has_members" => "Rollen kan ikke slettes, da der er medarbejdere med denne rolle. Tildel dem en ny rolle først.",
            "manage_members" => "Administrer lokationsmedarbejdere og deres tilladelser",
            "configure_roles" => "Konfigurer tilladelser for medarbejderroller hos lokationen",
        ],
        // Organisation team terminology (members)
        "team" => [
            "general" => "Generel",
            "team" => "Medlemmer",
            "members" => "Medlemmer",
            "invitations" => "Invitationer",
            "roles" => "Roller",
            "permissions" => "Tilladelser",
            "team members" => "Medlemmer",
            "team invitations" => "Invitationer",
            "team roles" => "Roller",
            "role permissions" => "Rolletilladelser",
            "locations" => "Lokationer",
            "checkout" => "Point of sale",
            "metrics" => "Statistik",
            "orders" => "Ordrer",
            "customers" => "Kunder",
            "settings" => "Indstillinger",
            "terminals" => "Terminaler",
            "pages" => "Sider",
            "payments" => "Betalinger",
            "organisation" => "Virksomhed",
            "reports" => "Rapporter",
            "billing" => "Fakturering",
            "wallet" => "Tegnebog",
            "advertisement" => "Markedsføring",
        ],
        "permissions" => [
            "std_modify" => 'Du mangler tilladelse til at redigere indholdet',
            "std_read" => 'Du mangler tilladelse til at læse indholdet',
            "std_delete" => 'Du mangler tilladelse til at slette indholdet',
        ]
    ];
    const WORD = [
        "january" => "januar",
        "february" => "februar",
        "march" => "marts",
        "april" => "april",
        "may" => "maj",
        "june" => "juni",
        "july" => "juli",
        "august" => "august",
        "september" => "september",
        "october" => "oktober",
        "november" => "november",
        "december" => "december",

        // Organisation translations
        "organisation" => "virksomhed",
        "organisations" => "virksomheder",
        "organisationer" => "virksomheder",
        "organisationen" => "virksomheden",
        "organisationerne" => "virksomhederne",
        "organisationens" => "virksomhedens",
        "organisationsnavn" => "virksomhedsnavn",
        "organisationsmedlem" => "virksomhedsmedlem",
        "organisationsmedlemmer" => "virksomhedsmedlemmer",
        "organisationsindstillinger" => "virksomhedsindstillinger",
        "organisationsinfo" => "virksomhedsinfo",
        "organisationsoversigt" => "virksomhedsoversigt",

        // Capital variations
        "Organisation" => "Virksomhed",
        "Organisationer" => "Virksomheder",
        "Organisations" => "Virksomheder",
        "Organisationen" => "Virksomheden",
        "Organisationerne" => "Virksomhederne",
        "Organisationens" => "Virksomhedens",
        "Organisationsnavn" => "Virksomhedsnavn",
        "Organisationsmedlem" => "Virksomhedsmedlem",
        "Organisationsmedlemmer" => "Virksomhedsmedlemmer",
        "Organisationsindstillinger" => "Virksomhedsindstillinger",
        "Organisationsinfo" => "Virksomhedsinfo",
        "Organisationsoversigt" => "Virksomhedsoversigt",
        "store manager" => "butiksbestyrer",
        "team manager" => "teamleder",
        "team_manager" => "teamleder",
        "cashier" => "kasseassistent",

        // Role names (lowercase)
        "owner" => "ejer",
        "admin" => "administrator",
        "manager" => "leder",
        "employee" => "medarbejder",
        "location_employee" => "butiksmedarbejder",

        // Role names (capitalized)
        "Owner" => "Ejer",
        "Admin" => "Administrator",
        "Manager" => "Leder",
        "Employee" => "Medarbejder",
        "Location_employee" => "Butiksmedarbejder",
        "Location Employee" => "Butiksmedarbejder",

        // Permission category names
        "team" => "medlemmer",
        "Team" => "Medlemmer",
        "locations" => "lokationer",
        "Locations" => "Lokationer",
        "terminals" => "terminaler",
        "Terminals" => "Terminaler",
        "orders" => "ordrer",
        "Orders" => "Ordrer",
        "payments" => "betalinger",
        "Payments" => "Betalinger",
        "settings" => "indstillinger",
        "Settings" => "Indstillinger",
        "roles" => "roller",
        "Roles" => "Roller",
        "pages" => "sider",
        "Pages" => "Sider",

        // Permission sub-items
        "members" => "medlemmer",
        "Members" => "Medlemmer",
        "invitations" => "invitationer",
        "Invitations" => "Invitationer",
        "permissions" => "tilladelser",
        "Permissions" => "Tilladelser",
        "team_members" => "medlemmer",
        "Team_members" => "Medlemmer",
        "team_invitations" => "invitationer",
        "Team_invitations" => "Invitationer",
        "team_roles" => "roller",
        "Team_roles" => "Roller",
        "role_permissions" => "rolletilladelser",
        "Role_permissions" => "Rolletilladelser",
        "advertisement" => "markedsføring",
        "Advertisement" => "Markedsføring",



        "pushed" => "udskudt",
        "direct" => "direkte",
        "Pushed" => "Udskudt",
        "Direct" => "Direkte",
    ];


}