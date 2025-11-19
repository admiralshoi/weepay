<?php

namespace Database\includes;
use Database\Paginator;

class PaginatorBlueprint {
    public ?int $limit = null;
    public ?int $offset = null;
    public ?string $predicateOffset = null;
    public ?string $order = null;
    public ?string $column = null;
    public ?string $selection = null;
    public ?array $predicateConditions = null;
    public ?array $predicateDelimiterList = null;
    public ?array $predicateBindings = null;
    public string $buildMethod = "ORDERLY";
    protected Paginator $paginator;



    public function __construct(Paginator $paginator) {
        $this->paginator = $paginator;
    }


    /**
     * Build method of the pagination.
     * Used when you want to load newly inserted rows that didn't exist when the first batch was fetched.
     *
     * @return Paginator
     */
    public function dynamic(): Paginator {
        $this->buildMethod = "DYNAMIC";
        return $this->paginator;
    }

    /**
     * Build method of the pagination
     * Returns a proper pagination without regard for newly inserted rows.
     *
     * @return Paginator
     */
    public function orderly(): Paginator {
        $this->buildMethod = "ORDERLY";
        return $this->paginator;
    }



    public function set(
        ?int $limit = null,
        ?int $offset = null,
        ?string $order = null,
        ?string $column = null,
        ?string $selection = null,
        ?string $predicateOffset = null,
        ?array $predicateConditions = null,
        ?array $predicateDelimiterList = null,
        ?array $predicateBindings = null,
    ): static {
        if(!isset($this->limit) || (!is_null($limit) && $limit !== $this->limit)) $this->limit = $limit;
        if(!isset($this->offset) || (!is_null($offset) && $offset !== $this->offset)) $this->offset = $offset;
        if(!isset($this->order) || (!is_null($order) && $order !== $this->order)) $this->order = $order;
        if(!isset($this->column) || (!is_null($column) && $column !== $this->column)) $this->column = $column;
        if(!isset($this->selection) || (!is_null($selection) && $selection !== $this->selection)) $this->selection = $selection;
        if(!isset($this->predicateOffset) || (!is_null($predicateOffset) && $predicateOffset !== $this->predicateOffset)) $this->predicateOffset = $predicateOffset;
        if(!isset($this->predicateConditions) || (!is_null($predicateConditions) && $predicateConditions !== $this->predicateConditions)) $this->predicateConditions = $predicateConditions;
        if(!isset($this->predicateDelimiterList) || (!is_null($predicateDelimiterList) && $predicateDelimiterList !== $this->predicateDelimiterList)) $this->predicateDelimiterList = $predicateDelimiterList;
        if(!isset($this->predicateBindings) || (!is_null($predicateBindings) && $predicateBindings !== $this->predicateBindings)) $this->predicateBindings = $predicateBindings;
        return $this;
    }
    public function setLimit(?int $limit): static { $this->limit = $limit; return $this; }
    public function setOffset(?int $limit): static { $this->limit = $limit; return $this; }
    public function setOrder(?string $order): static { $this->order = $order; return $this; }
    public function setColumn(?string $column): static { $this->column = $column; return $this; }
    public function setSelection(?string $selection): static { $this->selection = $selection; return $this; }
    public function setPredicateOffset(?string $predicateOffset): static { $this->predicateOffset = $predicateOffset; return $this; }
    public function setPredicateConditions(?array $conditions): static { $this->predicateConditions = $conditions; return $this; }
    public function setPredicateDelimiters(?array $delimiters): static { $this->predicateDelimiterList = $delimiters; return $this; }
    public function setPredicateBindings(?array $bindings): static { $this->predicateBindings = $bindings; return $this; }



}
