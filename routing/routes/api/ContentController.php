<?php

namespace routing\routes\api;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\NoReturn;

class ContentController {



    #[NoReturn] public static function getTemplateModal(array $args): void  {
        $name = array_key_exists("name", $args) ? $args["name"] : "";
        $file = __view("templates.modals.$name.html", "html");
        if(!file_exists($file)) Response()->html(null, 400);
        Response()->html(file_get_contents($file));
    }


    #[NoReturn] public static function getTemplateElement(array $args): void  {
        $name = array_key_exists("name", $args) ? $args["name"] : "";
        $file = __view("templates.elements.$name.html", "html");
        if(!file_exists($file)) Response()->html(null, 400);
        Response()->html(file_get_contents($file));
    }



}