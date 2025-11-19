<?php

namespace routing\routes\admin;

use classes\Methods;
use classes\payments\stripe\StripeHandler;
use classes\payments\stripe\StripeMethods;
use JetBrains\PhpStorm\NoReturn;

class ApiController {



    #[NoReturn] public static function couponActions(array $args): void {
        switch ($args["action"]) {
            default: Response()->jsonError("Unknown action.");
            case "create": self::createCoupon($args);
            case "toggle": self::toggleCoupon($args);
            case "delete": self::deleteCoupon($args);
        }
    }

    #[NoReturn] public static function deleteCoupon(array $args): void {
        Response()->json(Methods::coupons()->deleteCoupon($args["id"]));
    }

    #[NoReturn] public static function toggleCoupon(array $args): void {
        $rowId = $args["id"];
        $row = Methods::coupons()->get($rowId);
        if(isEmpty($row)) Response()->jsonError("Invalid id $rowId");
        $row->enabled = (int)(!($row->enabled === 1));
        $status = Methods::coupons()->update(["enabled" => $row->enabled], ["id" => $rowId]);
        if(!$status) Response()->jsonError("Failed up update row. Try again later.");
        Response()->jsonSuccess("Row updated.", toArray($row));
    }


    #[NoReturn] public static function createCoupon(array $args): void {
//        Response()->jsonSuccess("", $args);
        $requiredKeys = ["amount", "cycles", "duration", "is_fixed", "promotional_code", "date_range", "usages", "product_ids"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Missing required key: $key");
        $amount = (float)$args["amount"];
        $isFixed = (int)$args["is_fixed"] === 1;
        $cycles = (int)$args["cycles"];
        $duration = $args["duration"];
        $code = $args["promotional_code"];
        $dateRange = $args["date_range"];
        $usages = (int)$args["usages"];
        $productIds = empty($args["product_ids"]) ? null : explode(",", $args["product_ids"]);
        if($duration !== "repeating") $cycles = 0;
        $dates = explode(" - ", $dateRange);
        $start = strtotime($dates[0]);
        $end = strtotime($dates[1]);

        if(empty($code)) Response()->jsonError("Please put a Promotional code");
        if(!in_array($duration, ["repeating", "once", "forever"])) Response()->jsonError("Invalid duration $duration.");
        if($cycles < 0) Response()->jsonError("The cycles cannot be less than 0.");
        if($usages < 0) Response()->jsonError("The allowed usages cannot be less than 0");
        if($amount <= 0) Response()->jsonError("The amount should at least be larger than 0.");
        if(!$isFixed && $amount > 100) Response()->jsonError("You cannot set a 'percent off' greater than 100.");

        if(!$isFixed) $amount = (int)$amount;
        else $amount = round($amount, 2);
        $result = Methods::coupons()->createCoupon($code, $amount, $isFixed, $duration, $usages, $start, $end, $cycles, $productIds);
        if($result["status"] === "error") Response()->jsonError($result["error"]["message"]);
        $result["data"]['refresh'] = true;
        Response()->jsonSuccess($result["message"], $result["data"]);
    }





    #[NoReturn] public static function updatePaymentSettings(array $args): void {
        $action = $args["action"];
        unset($args["action"]);

//        Response()->jsonSuccess("Data updated.", $args);

        $productHandler = Methods::products();
        $featureHandler = Methods::features();
        $priceHandler = Methods::pricing();
//        valid_campaign_currencies

        switch ($action) {
            default: Response()->jsonError("Invalid action: $action");
            case "update":

                $productUpdates = [];
                $featureUpdates = [];
                $pricesToUpdate = [];
                $pricesToCreate = [];

                foreach ($args as $productItemId => $data) {
                    $itemsToUpdate = [];
                    $row = $productHandler->getByItemId($productItemId);
                    if(isEmpty($row)) Response()->jsonError("Invalid product id given.");
                    $enabled = (int)(array_key_exists("enabled", $data) && $data["enabled"] === "on");
                    $name = array_key_exists("name", $data) ? ucfirst($data["name"]) : "";
                    $description = array_key_exists("description", $data) ? ucfirst($data["description"]) : "";
                    if(empty($name)) Response()->jsonError("Please put a name for product $productItemId");
                    if(strlen($name) > 25) Response()->jsonError("Please keep the product name under 25 characters: '$name'");

                    if($description !== $row->description) $itemsToUpdate["description"] = $description;
                    if($name !== $row->name) $itemsToUpdate["name"] = $name;
                    if($enabled !== $row->enabled) $itemsToUpdate["enabled"] = $enabled;


                    if(!array_key_exists("features", $data)) {
                        $featureHandler->update(["value" => 0], ["product_id" => $productItemId]);
                    }
                    else {
                        $existingFeatures = $featureHandler->getByProductId($productItemId);
                        foreach ($data["features"] as $rowId => $val) {
                            $feature = $existingFeatures->filter(function ($row) use ($rowId) { return (int)$rowId === (int)$row["id"]; })->first();
                            if(isEmpty($feature)) Response()->jsonError("Invalid feature with id $rowId");

                            $value = is_numeric($val) ? (int)$val : (int)($val === "on");
                            if($value !== $feature->value) $featureUpdates[$rowId] = ["value" => $value];
                        }

                        if($existingFeatures->count() !== count($data["features"])) {
                            foreach ($existingFeatures->list() as $feature) {
                                if(array_key_exists($feature->id, $data["features"])) continue;
                                if($feature->value > 0) $featureUpdates[$feature->id] = ["value" => 0];
                            }
                        }
                    }


                    if(array_key_exists("pricing", $data) && !empty($data["pricing"])) {
                        $toUpdate = $toCreate = [];
                        foreach ($data["pricing"] as $state => $items) {
                            if($state === "existing") {
                                foreach ($items as $rowId => $item) {
                                    $priceRow = $priceHandler->get($rowId);
                                    $amount = (float)max((float)$item["amount"], 0);
                                    if((float)$priceRow->amount !== $amount) $toUpdate[$rowId] = ["amount" => $amount, "currency" => $item["currency"]];
                                    elseif ($amount > 0 && $priceRow->enabled === 0) $toUpdate[$rowId] = ["enabled" => true];
                                }
                            }
                            else {
                                foreach ($items as $interval => $item) {
                                    if(!in_array($interval,  ["monthly", "quarterly", "biannual", "yearly"])) continue;
                                    $amount = max((float)$item["amount"], 0);
                                    if($amount <= 0) continue;
                                    $toCreate[$interval] = [
                                        "amount" => $amount,
                                        "currency" => $item["currency"],
                                        "price_only" => false,
                                    ];
                                }
                            }
                        }
                        if(!empty($toUpdate)) $pricesToUpdate[$productItemId] = $toUpdate;
                        if(!empty($toCreate)) $pricesToCreate[$productItemId] = $toCreate;
                    }
                    if(!empty($itemsToUpdate)) $productUpdates[$productItemId] = $itemsToUpdate;
                }


//                Response()->jsonSuccess("", compact('pricesToCreate', 'pricesToUpdate', 'productUpdates', 'featureUpdates'));

                if(empty($productUpdates) && empty($featureUpdates) && empty($pricesToCreate) && empty($pricesToUpdate)) {
                    Response()->jsonSuccess("No data to update.", ["refresh" => false]);
                }

                foreach ($productUpdates as $productItemKey => $params) {
                    $productHandler->update($params, ["item_id" => $productItemKey]);
                    $priceRow = $priceHandler->getFirst(["product_id" => $productItemKey], ["stripe_product_id"]);
                    $productRow = $productHandler->getFirst(["item_id" => $productItemKey], ["enabled", "name", "type"]);

                    if(array_key_exists("enabled", $params) || array_key_exists("name", $params)) {
                        if(array_key_exists("enabled", $params)) $enabled = $params["enabled"] === 1;
                        else $enabled = $productRow->enabled === 1;
                        if(array_key_exists("name", $params)) $stripeName = $params["name"];
                        else $stripeName = $productRow->name;
                        $stripeName = ($productRow->type === "sub" ? "(Sub) - " : "(Ppu) - ") . $stripeName;
                        if(!isEmpty($priceRow)) StripeMethods::updateProduct($priceRow->stripe_product_id, $enabled, $stripeName);
                    }
                }
                foreach ($featureUpdates as $rowId => $params) {
                    $featureHandler->update($params, ["id" => $rowId]);
                }

                $paymentHandler = Methods::paymentHandler()->handler("stripe");
                $paymentHandler->handlePriceAndProductUpdate($pricesToUpdate, $pricesToCreate, $productUpdates);



                Response()->jsonSuccess("Data updated.",
                    ["refresh" => true, "items" => compact('pricesToCreate', 'pricesToUpdate', 'productUpdates', 'featureUpdates')]);
        }


        Response()->jsonSuccess("", $args);




    }






    #[NoReturn] public static function appMetaUpdate(array $args): void {
        $metaName = array_key_exists("name", $args) ? $args["name"] : null;
        $action = $args["action"];

        $data = array_key_exists("data", $args) ? $args["data"] : [];
        $handler = Methods::appMeta();
        if(empty($data) && !$handler->exists($metaName)) Response()->jsonError("Invalid app setting: $metaName");


        switch ($action) {
            default:  Response()->jsonError("Invalid action: $action");
            case "toggle":
                $row = $handler->getRow($metaName);
                if(isEmpty($row)) Response()->jsonError("The setting does not exist or you do not have sufficient access.");

                $value = Methods::misc()::enforceDataType($row->type, $row->value);
                switch ($row->type) {
                    default: Response()->jsonError("The data type $row->type does not support the 'toggle' action.");
                    case "bool": $value = (!!!$value) ? 'true' : 'false'; break;
                    case "int": $value = (int)(!!!($value > 0)); break;
                }

                $updated = $handler->update($value, $metaName);
                if(!$updated) Response()->jsonError("Failed to toggle and update the setting. Try again later.");
                Response()->jsonSuccess("$metaName updated to $value ($row->type)");

            case "update":
                $listToUpdate = [];
                foreach ($data as $name => $val) {
                    $row = $handler->getRow($name);
                    if(isEmpty($row)) Response()->jsonError("The setting does not exist or you do not have sufficient access.");
                    $value = Methods::misc()::enforceDataType($row->type, $val);
                    if(in_array($name, ["maturing_period"])) { //Convert days to seconds.
                        $value *= (3600 * 24);
                    }
                    if(!Methods::misc()::isValidType($row->type, $value)) Response()->jsonError("Failed to convert the value of $name to $row->type.");
                    if($row->type === "bool") $value = $value ? 'true' : 'false';
                    $listToUpdate[$name] = $value;
                }

                foreach ($listToUpdate as $name => $value) {
                    $handler->update($value, $name);
                }
                Response()->jsonSuccess("App settings updated successfully.");
        }



    }





    #[NoReturn] public static function markAffiliatePaymentsAsPaid(array $args): void {
        $rowIds = $args["ids"];
        $resultsHandler = Methods::affiliatePayoutResults();
        $updates = [];
        foreach ($rowIds as $rowId) {
            $row = $resultsHandler->get($rowId);
            if(isEmpty($row)) Response()->jsonError("Failed to find some or all the associated rows. Try again later.");
            $uid = $row->affiliate_id;
            $user = Methods::affiliates()->get($uid);
            if(isEmpty($row)) Response()->jsonError("Failed to fetch the user row. Try again later.");

            $updates[$rowId] = [
                "payout_status" => $resultsHandler->statusClosed,
                "address_country" => $user->address_country,
                "address_region" => $user->address_region,
                "address_city" => $user->address_city,
                "address_zip" => $user->address_zip,
                "address_street" => $user->address_street,
                "payout_bank_info" => $user->payout_bank_info,
            ];
        }

        foreach ($updates as $rowId => $params) $resultsHandler->update($params, ["id" => $rowId]);
        Response()->jsonSuccess("Rows updated.");
    }




}