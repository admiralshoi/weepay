<?php

namespace Database;

use Countable;
use Iterator;
use classes\Methods;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use stdClass;

class Collection implements Iterator, Countable {
    private object $items;
    private int $position = 0;
    private ?Paginator $paginator = null;

    public function __construct(array|object $items = []) {
        $this->setItems($items);
    }

    public function setItems(array|object $items = []): static {
        $this->items = $this->toObject($items);
        $this->position = 0;
        return $this;
    }

    public function splice(int $offset, ?int $length = null, mixed $replacement = []): static {
        $items = $this->toArray();
        return new static(array_splice($items, $offset, $length, $replacement));
    }
    public function filter(callable $callback): static {
        return (new static(array_values(array_filter($this->toArray(), $callback))))->dupePaginator($this->paginator);
    }
    public function chunk(int $length, bool $preserve_keys = false): static {
        return (new static(array_chunk($this->toArray(), $length, $preserve_keys)))->dupePaginator($this->paginator);
    }
    public function grabChunk(int $length, int $key = 0, bool $preserve_keys = false): static {
        $data = array_chunk($this->toArray(), $length, $preserve_keys);
        if(array_key_exists($key, $data)) $data = $data[$key];
        return (new static($data))->dupePaginator($this->paginator);
    }
    public function map(callable $callback): static {
        return (new static(array_map($callback, $this->toArray())))->dupePaginator($this->paginator);
    }

    public function values(): array {
        return array_values($this->toArray());
    }
    public function unique(): array {
        return array_values(array_unique($this->toArray()));
    }
    public function reverse(): array {
        return array_reverse($this->toArray());
    }


    public function groupByKey(string $key = "type"): static {
        $list = [];
        foreach ($this->items as $n => $item) {
            if(!property_exists($item, $key)) $type = "";
            else $type = $item->$key;
            if(!array_key_exists($type, $list)) $list[$type] = [];
            if(is_numeric($n)) $list[$type][] = $item;
            else $list[$type][$n] = $item;
        }
        return new Collection($list);
    }

    public function sortByKey($key = "", $ascending = false, array $specialReplacement = array(), array $splitReplace = array(), $key2 = ""): static {
        $items = $this->toArray();
        Methods::sortByKey($items, $key, $ascending, $specialReplacement, $splitReplace, $key2);
        $this->setItems($items);
        return $this;
    }

    public function nestedArray(array $keys, mixed $defaultReturnKey = null): mixed {
        $targetObject = $this->toArray();
        if(empty($keys) || empty($targetObject)) return $defaultReturnKey;

        foreach ($keys as $key) {
            if(!is_array($targetObject) || !array_key_exists($key, $targetObject)) return $defaultReturnKey;
            $targetObject = $targetObject[$key];
        }

        return $targetObject;
    }


    public function reduce(callable $callback, mixed $initial = null): mixed{
        $value = array_reduce($this->toArray(), $callback, $initial);
        return is_array($value) || is_object($value) ? new static($value) : $value;
    }

    public function merge(array|object ...$collections): static {
        $merged = $this->toArray();
        foreach ($collections as $collection) {
            if ($collection instanceof Collection) {
                $collectionItems = $collection->toArray();
            } elseif (is_array($collection)) {
                $collectionItems = $collection;
            } elseif (is_object($collection)) {
                $collectionItems = toArray($collection);
            } else {
                continue;
            }
            $merged = $this->mergeArrays($merged, $collectionItems);
        }

        return (new static($merged))->dupePaginator($this->paginator);
    }

    private function mergeArrays(array $array1, array $array2): array {
        return array_merge($array1, $array2);
    }

    public function add($item, $key = null) {
        $items = $this->toArray();
        if($key !== null) $items[$key] = $item;
        else $items[] = $item;
        $this->items = $this->toObject($items);
    }
    public function updateItem(string|int $key, mixed $value): void {
        $items = $this->toArray();
        if(!array_key_exists($key, $items)) return;
        $items[$key] = $value;
        $this->items = $this->toObject($items);
    }
    public function update(string|object $items): void {
        if($items instanceof Collection) $items = $items->toArray();
        $this->items = $this->toObject($items);
    }

    public function list(): object {
        return $this->items;
    }

    #[Pure] public function empty(): bool {
        return $this->count() === 0;
    }

    public function toArray(): array {
        return toArray($this->items);
    }
    public function toObject(array|object $array): object {
        $object = new stdClass();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $object->$key = $this->toObject($value);
            }
            elseif ($value instanceof Collection) {
                $object->$key = $value->list();
            } else {
                $object->$key = $value;
            }
        }
        return $object;
    }

    #[Pure] public function first(): object {
        return $this->empty()? new StdClass() : $this->get(0);
    }
    #[Pure] public function last(): object {
        return $this->empty()? new StdClass() : $this->get(($this->count() - 1));
    }
    public function current(): mixed {
        $item = $this->toArray()[$this->position];
        return is_array($item) ? $this->toObject($item) : $item;
    }
    public function get(string|int $key) {
        if(is_string($key)) return $this->items->$key;
        $items = $this->toArray();
        if(!array_key_exists($key, $items)) return null;
        $item = $items[$key];
        return is_array($item) ? $this->toObject($item) : $item;
    }

    public function next(): void {
        ++$this->position;
    }

    public function key(): mixed {
        return $this->position;
    }

    public function valid(): bool {
        return array_key_exists($this->position, $this->toArray());
    }

    public function rewind(): void {
        $this->position = 0;
    }

    public function count(): int {
        return count(get_object_vars($this->items));
    }





    public function setPagination(QueryBuilder $queryBuilder): static {
        $this->paginator = new Paginator($queryBuilder, $this->count());
        return $this;
    }

    /**
     *
     * Should only be called when items are retained, such as "map()"....
     * or if used on other functions such as filter you'd still be paginating the original items' logic in DB.
     *
     *
     *
     * @param Paginator|null $paginator
     * @return $this
     */
    protected function dupePaginator(?Paginator $paginator): static {
        $this->paginator = $paginator;
        return $this;
    }


    #[ArrayShape(["pagination" => "array|null", "data" => "array", "meta" => "array"])]
    public function apiPaginationResponse(array $metaData = []): array {
        return [
            "pagination" => $this->getCursor(),
            "data" => $this->toArray(),
            "meta" => $metaData
        ];
    }

    #[ArrayShape(["next" => "bool", "previous" => "bool", "after" => "string"])]
    public function getCursor(): ?array { return is_null($this->paginator) ? null : $this->paginator->getCursor($this); }
    public function nextPage(): Collection { return is_null($this->paginator) ? new Collection() : $this->paginator->next($this); }



}
