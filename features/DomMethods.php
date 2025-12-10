<?php

namespace features;


use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Titles;
use Database\Collection;
use stdClass;

class DomMethods {

    public static function organisationSelect(array|object $options = [],null|string|int $selectedValue = null): string {
        $element = '
            <div>
            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
            <i class="fa-regular fa-building font-16 color-blue"></i>
            <p class="mb-0 font-18">Mine organisationer:</p>
            <select class="form-select-v2 w-200px" id="organisation-selection">';

        if(empty($options)) $element .= '<option value="" disabled>Intet at vise</option>';
        else {
            if(empty($selectedValue)) $element .= '<option value="" selected>Vælg organisation</option>';
            foreach ($options as $key => $option) {
                $selected = $selectedValue === $key;
                $element .= '<option value="'.$key.'" '.($selected ? 'selected' : '') ;
                if(!$selected) $element .= 'data-href="' . __url(Links::$merchant->organisation->switchPath($key)) . '"';
                $element .= '>'.$option.'</option>';
            }
        }


        $element .= '</select>
        </div>
        </div>
        ';
        return $element;
    }
    public static function locationSelect(array|object $options = [],null|string|int $selectedValue = null): string {
        if($options instanceof Collection) $options = $options->toArray();
        $element = '
            <div>
            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
            <i class="mdi mdi-store-outline font-16 color-blue"></i>
            <p class="mb-0 font-18">Vis data for:</p>
            <select class="form-select-v2 w-200px" id="location-selection">';
        $element .= '<option value="all" data-href="' . __url(Links::$merchant->locations->main) . '">Alle butikker</option>';

        foreach ($options as $key => $option) {
            $selected = $selectedValue === $key;
            $element .= '<option value="'.$key.'" '.($selected ? 'selected' : '') ;
            if(!$selected) $element .= 'data-href="' . __url(Links::$merchant->locations->setSingleLocation($key)) . '"';
            $element .= '>'.$option.'</option>';
        }

        $element .= '</select>
        </div>
        </div>
        ';
        return $element;
    }

    public static function customTableSearch(string|int $targetId, string $placeholder = ''): string {
        $element = '<div class="position-relative">';
        $element .= '<input type="text" data-target="'.$targetId.'" class="custom-table-search form-field-v2" style="padding-left: calc(1.5rem + 12px)" placeholder="' . $placeholder . '" />';
        $element .= '<i class="mdi mdi-magnify font-16 position-absolute color-gray" style="top: 11px; left: .75rem; "></i>';
        $element .= '</div>';
        return $element;
    }





    public static function buildPaginationSearchElement(object $paginationObject): string {
        if(!array_key_exists('search', $paginationObject->utilities)) return "";
        $searchObj = $paginationObject->utilities['items']['search'];
        $element = '<div class="position-relative">';
        $element .= '<input type="text" name="' . $searchObj['id'] . '" data-id="' . $searchObj['id'] . '" class="form-field-v2" style="padding-left: calc(1.5rem + 12px)" placeholder="' . $searchObj['placeholder'] . '" />';
        $element .= '<i class="mdi mdi-magnify font-16 position-absolute color-gray" style="top: 11px; left: .75rem; "></i>';
        $element .= '</div>';
        return $element;
    }








    public static function buildPaginationContainerElement(object $paginationObject): string {
        $element = '<div class="pagination-container" id="' . $paginationObject->id . '" ';
            $element .= 'data-template="' . $paginationObject->template . '" ';
            $element .= 'data-endpoint="' . $paginationObject->endpoint . '" ';
            $element .= 'data-sentinel="' . $paginationObject->sentinel . '" ';
            $element .= 'data-current-view-target="' . $paginationObject->currentViewId . '" ';
            $element .= 'data-total-view-target="' . $paginationObject->totalViewId . '" ';
            if(array_key_exists('sort', $paginationObject->utilities)) $element .= 'data-sort-target="[data-id=' . $paginationObject->utilities['sort'] . ']" ';
            if(array_key_exists('filter', $paginationObject->utilities)) $element .= 'data-filter-target="[data-id=' . $paginationObject->utilities['filter'] . ']" ';
            if(array_key_exists('search', $paginationObject->utilities)) $element .= 'data-search-target="[data-id=' . $paginationObject->utilities['search'] . ']" ';
            $element .= '>';

            $element .= '<div class="' . implode(' ', $paginationObject->innerContentClasses) . '"></div>';
            $element .= '<div class="content-loader-container"></div>';
            $element .= '<div id="' . $paginationObject->sentinel . '"></div>';
        $element .= '</div>';

        return $element;
    }

    public static function buildPaginationTableElement(object $paginationObject): string {
        $element = '<div class="pagination-container" id="' . $paginationObject->id . '" ';
            $element .= 'data-template="' . $paginationObject->template . '" ';
            $element .= 'data-endpoint="' . $paginationObject->endpoint . '" ';
            $element .= 'data-sentinel="' . $paginationObject->sentinel . '" ';
            $element .= 'data-current-view-target="' . $paginationObject->currentViewId . '" ';
            $element .= 'data-total-view-target="' . $paginationObject->totalViewId . '" ';
            if(array_key_exists('sort', $paginationObject->utilities)) $element .= 'data-sort-target="[data-id=' . $paginationObject->utilities['sort'] . ']" ';
            if(array_key_exists('filter', $paginationObject->utilities)) $element .= 'data-filter-target="[data-id=' . $paginationObject->utilities['filter'] . ']" ';
            if(array_key_exists('search', $paginationObject->utilities)) $element .= 'data-search-target="[data-id=' . $paginationObject->utilities['search'] . ']" ';
            $element .= '>';

            $element .= '<div class="w-100 mt-2 position-relative ">';
                $element .= '<table class="' . implode(' ', $paginationObject->tableClasses) . '" id="' . $paginationObject->tableId . '">';
                    $element .= '<thead class="' . implode(' ', $paginationObject->theadClasses) . '">';
                        $element .= '<tr>';
                        foreach ($paginationObject->table as $column) $element .= '<th>' . Titles::cleanUcAll($column) . '</th>';
                        $element .= '</tr>';
                    $element .= '</thead>';
                    $element .= '<tbody class="' . implode(' ', $paginationObject->tbodyClasses) . '"></tbody>';
                $element .= '</table>';
                $element .= '<div class="content-loader-container"></div>';
                $element .= '<div id="' . $paginationObject->sentinel . '"></div>';
            $element .= '</div>';
        $element .= '</div>';

        return $element;
    }


    public static function buildPaginationSortElement(object $paginationObject): string {
        if(!array_key_exists('sort', $paginationObject->utilities)) return "";
        if(empty($paginationObject->utilities['items']['sort']['items'])) return "";
        $sortObj = $paginationObject->utilities['items']['sort'];
        $defaultIds = $sortObj['defaults'];
        $defaultObjects = array_reduce($sortObj['items'], function ($initial, $item) use ($defaultIds) {
           if(!isset($initial)) $initial = [];
           $obj = array_values(array_filter($item, function ($item) use ($defaultIds) {
               return in_array($item['id'], $defaultIds);
           }));
           if(!empty($obj)) $obj = $obj[0];//Defaults are unique per group.
            $initial[] = $obj;
           return $initial;
        });


        $defaultObject = $defaultObjects[0]; //Sort can only have 1 default.



        $element = '<div class="dropdown nav-item-v2 nav-item-icon p-0">';
            $element .= '<input type="hidden" name="' . $sortObj['id'] . '" data-id="' . $sortObj['id'] . '" value="' . $defaultObject['column'] . '|||' . $defaultObject['direction'] . '" />';
            $element .= '<button class="btn-v2 mute-btn dropdown-toggle dropdown-no-arrow py-1 px-2 " style="height: 45px;" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $element .= '<i class="mdi mdi-swap-vertical font-14 mr-1"></i>';
                $element .= '<span class="font-weight-medium font-14">' . $paginationObject->sortButtonName . '</span>';
            $element .= '</button>';
            $element .= '<div class="dropdown-menu section-dropdown" id="">';
                $i = 0;
                foreach ($sortObj['items'] as $groupName => $group) {
                    $i++;
                    $element .= '<div class="account-body ' . ($i === count($sortObj['items']) ? 'border-none' : '') . '">';
                        $element .= '<p class="font-weight-bold mb-0 font-14">' . Titles::cleanUcAll($groupName) . '</p>';
                        foreach ($group as $item) {
                            $element .= '<div class="list-item cursor-pointer justify-content-between" data-input="' . $item['column'] . '|||' . $item['direction'] . '">';
                                $element .= '<div class="flex-row-start flex-align-center flex-nowrap ' . (in_array($item['id'], $defaultIds) ? 'selected' : '') . ' list-style-selected" style="column-gap: .5rem;">';
                                    $element .= '<p class="mb-0 font-13">' . $item['title'] . '</p>';
                                $element .= '</div>';
                            $element .= '</div>';
                        }
                    $element .= '</div>';
                }
            $element .= '</div>';
        $element .= '</div>';
        return $element;
    }


    public static function buildPaginationFilterElement(object $paginationObject): string {
        if(!array_key_exists('filter', $paginationObject->utilities)) return "";
        if(empty($paginationObject->utilities['items']['filter']['items'])) return "";
        $filterObj = $paginationObject->utilities['items']['filter'];
        $defaultIds = $filterObj['defaults'];
        $defaultObjects = array_reduce($filterObj['items'], function ($initial, $item) use ($defaultIds) {
            if(!isset($initial)) $initial = [];
            $obj = array_values(array_filter($item, function ($item) use ($defaultIds) {
                return in_array($item['id'], $defaultIds);
            }));
            if(!empty($obj)) $initial[] = $obj[0]; //Defaults are unique per group.
            return $initial;
        });



        $element = '<div class="dropdown nav-item-v2 nav-item-icon p-0">';
        foreach ($defaultObjects as $defaultObject) {
            $groupName = array_key_exists('group', $defaultObject) ? $defaultObject['group'] : "Unknown";
            $element .= '<input type="hidden" name="' . $filterObj['id'] . '[]" data-id="' . $filterObj['id'] . '" data-group="' . $groupName . '" value="' . $defaultObject['column'] . '" />';
        }
        $element .= '<button class="btn-v2 mute-btn dropdown-toggle dropdown-no-arrow py-1 px-2 " style="height: 45px;" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $element .= '<i class="mdi mdi-filter font-14 mr-1"></i>';
        $element .= '<span class="font-weight-medium font-14">' . $paginationObject->filterButtonName . '</span>';
        $element .= '</button>';
        $element .= '<div class="dropdown-menu section-dropdown" id="">';
        $i = 0;
        foreach ($filterObj['items'] as $groupName => $group) {
            $i++;
            $element .= '<div class="account-body ' . ($i === count($filterObj['items']) ? 'border-none' : '') . '">';
            $element .= '<p class="font-weight-bold mb-0 font-14">' . Titles::cleanUcAll($groupName) . '</p>';
            foreach ($group as $item) {
                $element .= '<div  class="list-item cursor-pointer justify-content-between" data-input="' . $item['column'] . '" data-group="' . $groupName . '" >';
                $element .= '<div class="flex-row-start flex-align-center flex-nowrap ' . (in_array($item['id'], $defaultIds) ? 'selected' : '') . ' list-style-selected" style="column-gap: .5rem;">';
                $element .= '<p class="mb-0 font-13">' . $item['title'] . '</p>';
                $element .= '</div>';
                $element .= '</div>';
            }
            $element .= '</div>';
        }
        $element .= '</div>';
        $element .= '</div>';
        return $element;
    }




    public static function preparePaginationTable(array $tableColumns, string $endpoint, string $template, array $options = [], array $utilities = []): object {
        $obj = new stdClass();
        $obj->table = $tableColumns;
        $obj->endpoint = $endpoint;
        $obj->template = $template;
        $obj->utilities = [];

        foreach ($options as $key => $value) {
            if($key === 'id') $obj->id = $value;
            if($key === 'table_id') $obj->tableId = $value;
            if($key === 'sentinel') $obj->sentinel = $value;
            if($key === 'table_classes') $obj->tableClasses = is_array($value) ? $value : explode(',', $value);
            if($key === 'thead_classes') $obj->theadClasses = is_array($value) ? $value : explode(',', $value);
            if($key === 'tbody_classes') $obj->tbodyClasses = is_array($value) ? $value : explode(',', $value);
            if($key === 'inner_content_classes') $obj->innerContentClasses = is_array($value) ? $value : explode(',', $value);
            if($key === 'current_view_id') $obj->currentViewId = $value;
            if($key === 'total_view_id') $obj->totalViewId = $value;
            if($key === 'filter_button_name') $obj->filterButtonName = $value;
            if($key === 'sort_button_name') $obj->sortButtonName = $value;
        }
        if(!property_exists($obj, 'filterButtonName')) $obj->filterButtonName = 'Filter';
        if(!property_exists($obj, 'sortButtonName')) $obj->sortButtonName = 'Sort';
        if(!property_exists($obj, 'id')) $obj->id = generateUniqueId(9, 'INT_STRING');
        if(!property_exists($obj, 'currentViewId')) $obj->currentViewId = '.current-view-count';
        if(!property_exists($obj, 'totalViewId')) $obj->totalViewId = '.total-view-count';
        if(!property_exists($obj, 'tableId')) $obj->tableId = generateUniqueId(8, 'INT_STRING');
        if(!property_exists($obj, 'sentinel')) $obj->sentinel = generateUniqueId(7, 'INT_STRING');
        if(!property_exists($obj, 'tableClasses')) $obj->tableClasses = ['table', 'table-hover'];
        if(!property_exists($obj, 'theadClasses')) $obj->theadClasses = ['color-gray', 'no-text-transform', 'font-weight-medium'];
        if(!property_exists($obj, 'tbodyClasses')) $obj->tbodyClasses = ['inner-content'];
        if(!property_exists($obj, 'innerContentClasses')) $obj->innerContentClasses = ['inner-content'];
        if(!in_array('table', $obj->tableClasses)) $obj->tableClasses[] = 'table';
        if(!in_array('inner-content', $obj->tbodyClasses)) $obj->tbodyClasses[] = 'inner-content';
        if(!in_array('inner-content', $obj->innerContentClasses)) $obj->innerContentClasses[] = 'inner-content';


        $sortId = array_key_exists('sort_id', $options) ? $options['sort_id'] : generateUniqueId(11, 'INT_STRING');
        $filterId = array_key_exists('filter_id', $options) ? $options['filter_id'] : generateUniqueId(11, 'INT_STRING');
        $searchId = array_key_exists('search_id', $options) ? $options['search_id'] : generateUniqueId(11, 'INT_STRING');


        foreach ($utilities as $key => $value) {
            if(!array_key_exists('items', $obj->utilities)) $obj->utilities['items'] = [];
            if(in_array($key, ['filter', 'sort'])) {
                if(!is_array($value) || empty($value)) continue;
                $defaultId = null;
                $defaults = $groupDefaultIds = [];
                $groups = [];
                foreach ($value as $k => $v) {
                    $itemId = generateUniqueId(6, 'INT_STRING', true);
                    if(empty($defaultId)) $defaultId = $itemId;
                    $group = array_key_exists('group', $v) ? $v['group'] : 'Unknown';
                    if(!array_key_exists($group, $groups)) $groups[$group] = [];
                    $v['id'] = $itemId;
                    $groups[$group][] = $v;

                    if(!in_array($group, $groupDefaultIds) && array_key_exists('default', $v) && $v['default']) {
                        $defaults[] = $itemId;
                        $groupDefaultIds[] = $group;
                    }
                }
                if(empty($defaults) && $key === 'sort' && !empty($defaultId)) $defaults[] = $defaultId;

                $obj->utilities['items'][$key] = [
                    'id' => $key === 'filter' ? $filterId : $sortId,
                    'defaults' => $defaults,
                    'items' => $groups,
                ];
            }
            elseif($key === 'search') {
                $obj->utilities['items'][$key] = [
                    'id' => $searchId,
                    'placeholder' => $value,
                ];
            }
            else continue;
            $obj->utilities[$key] = $obj->utilities['items'][$key]['id'];
        }

        return $obj;
    }

}