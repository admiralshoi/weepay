<?php

namespace classes\utility;
use classes\Methods;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrHandler {

    private ?object $built = null;

    public function build(string $url): static {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $url,
            size: 300,
            margin: 10
        );

        $this->built = $builder->build();
        return $this;
    }
    public function saveToFile(string $destination): bool {
        if($this->built === null) return false;
        $path = explode("/", $destination);
        array_pop($path);
        $path = implode("/", $path);
        $root = "";

        if(!is_dir($path)) $root = ROOT;
        if(!is_dir($root . $path)) return false;
        $this->built->saveToFile(__DIR__ . '/merchant_12345.png');
        return true;
    }

    public function get(): ?object {
        return $this->built;
    }

}