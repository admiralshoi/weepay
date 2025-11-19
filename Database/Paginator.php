<?php

namespace Database;

use Database\includes\PaginatorBlueprint;
use JetBrains\PhpStorm\ArrayShape;

class Paginator {
    public PaginatorBlueprint $blueprint;
    protected QueryBuilder $queryBuilder;
    protected bool $hasNext = false;
    protected bool $hasPrevious = false;
    protected bool $isPrepared = false;


    function __construct(QueryBuilder $queryBuilder, int $itemCount) {
        $this->blueprint = new PaginatorBlueprint($this);
        $this->queryBuilder = $queryBuilder;


        $this->blueprint->set(
            $this->queryBuilder->getLimit(),
            $this->queryBuilder->getOffset(),
            $this->queryBuilder->getDirection(),
            $this->queryBuilder->getOrderColumn(),
            $this->queryBuilder->selectionSql(),
            $this->queryBuilder->getPredicateOffset(),
            $this->queryBuilder->getPredicateConditions(),
            $this->queryBuilder->getPredicateDelimiters(),
            $this->queryBuilder->getPredicateBindings(),
        );

        if($this->queryBuilder->getBuildOrder() === "DYNAMIC") $this->blueprint->dynamic();
        else $this->blueprint->orderly();
        debugLog([$itemCount, $this->blueprint->predicateOffset, $this->blueprint->limit, ], "blueprnttest");
        $this->hasNext = $itemCount > 0 && (!empty($this->blueprint->predicateOffset) || $itemCount >= $this->blueprint->limit);
    }


    public function next(?Collection $items = null): Collection {
        if(!$this->hasNext()) return $this->queryBuilder->emptyList();
        if(is_null($this->blueprint->limit) || $this->blueprint->limit < 1) return $this->queryBuilder->emptyList();
        if(is_null($this->blueprint->offset) || (empty($this->blueprint->predicateOffset) && $this->blueprint->offset < 0)) return $this->queryBuilder->emptyList();
        if(!empty($this->blueprint->predicateOffset) && !in_array($this->blueprint->predicateOffset, [">", "<", ">=", "<="])) return $this->queryBuilder->emptyList();
        if(!in_array($this->blueprint->order, ["ASC", "DESC"])) return $this->queryBuilder->emptyList();
        if(!empty($this->blueprint->predicateOffset) && (is_null($items) || $items->empty())) return $this->queryBuilder->emptyList();

        $this->prepareNext($items);
        $queryBuilder = $this->build();
        if(is_null($queryBuilder)) return $this->queryBuilder->emptyList();
        return $queryBuilder->paginate();
    }


    public function hasNext(): bool { return $this->hasNext; }
    public function hasPrevious(): bool { return $this->hasPrevious; }


    #[ArrayShape(["next" => "bool", "previous" => "bool", "after" => "string"])]
    public function getCursor(?Collection $items = null): array {
        $this->prepareNext($items);
        if(!is_int($this->blueprint->limit) || $this->blueprint->limit < 1) $cursorData = [];
        else $cursorData = [
            "build_method" => $this->blueprint->buildMethod,
            "limit" => $this->blueprint->limit,
            "offset" => $this->blueprint->offset,
            "column" => $this->blueprint->column,
            "direction" => $this->blueprint->order,
            "predicate_offset" => $this->blueprint->predicateOffset,
            "selection" => $this->blueprint->selection,
            "predicate_conditions" => $this->blueprint->predicateConditions,
            "predicate_delimiters" => $this->blueprint->predicateDelimiterList,
            "predicate_bindings" => $this->blueprint->predicateBindings,
        ];

        $cursor = encrypt(base64_encode(json_encode($cursorData)));
        return [
            "next" => $this->hasNext(),
            "previous" => $this->hasPrevious(),
            "after" => $cursor
        ];
    }



    public function prepareNext(?Collection $items = null): static {
        if($this->isPrepared) return $this;
        if(is_null($this->blueprint->offset)) $this->blueprint->offset = 0;
        if(!empty($this->blueprint->predicateOffset) && !is_null($items) && !$items->empty()) {
            $last = $items->last();
            if(!empty($this->blueprint->column) && property_exists($last, $this->blueprint->column)) $this->blueprint->offset = (int)$last->{$this->blueprint->column};
        }
        elseif(empty($this->blueprint->predicateOffset)) $this->blueprint->offset += $this->blueprint->limit;

        $buildMethod = $this->blueprint->buildMethod;
        if($buildMethod === "DYNAMIC") {
            $this->blueprint->setOrder($this->blueprint->order === "ASC" ? "DESC" : "ASC");
            $this->blueprint->orderly();
        }

        if(is_null($this->blueprint->column)) $this->blueprint->column = "id";
        if(is_null($this->blueprint->predicateConditions)) {
            $this->blueprint->predicateConditions = $this->blueprint->predicateDelimiterList = $this->blueprint->predicateBindings = [];
        }
        if(is_null($this->blueprint->predicateOffset)) $this->blueprint->predicateOffset = "";

        $this->isPrepared = true;
        return $this;
    }




    protected function build(): ?QueryBuilder {
        if(is_null($this->blueprint->selection)) return null;
        $buildMethod = $this->blueprint->buildMethod;
        if(!in_array($buildMethod, ["DYNAMIC", "ORDERLY"])) return null;


        return $this->queryBuilder->assemble(
            $this->blueprint->limit,
            $this->blueprint->offset,
            $this->blueprint->order,
            $this->blueprint->column,
            $this->blueprint->selection,
            $this->blueprint->buildMethod,
            $this->blueprint->predicateOffset,
            $this->blueprint->predicateConditions,
            $this->blueprint->predicateDelimiterList,
            $this->blueprint->predicateBindings,
        );
    }















}