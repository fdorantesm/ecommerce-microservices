<?php

namespace App\Libraries;

class BarCode {
    private $api = "https://bwipjs-api.metafloor.com";
    private $bcid = "code128";
    private $image = null;
    private $scale = 1;
    private $scaleX = 3;
    private $scaleY = 1;
    private $text = null;
    
    public function __construct(Array $params = []) {
        foreach ($params as $key => $value) {
            if (property_exists(get_class($this), $key)) {
                $this->{$key} = $value;
            }
        }
        
        print_r($this->image);
        
        $queryString = http_build_query([
            "bcid" => $this->bcid,
            "scale" => $this->scale,
            "scaleX" => $this->scaleX,
            "scaleY" => $this->scaleY,
            "text" => $this->text,
            "alttext" => $this->text,
        ]);

        $this->image = "{$this->api}/?{$queryString}";

    }

    public function getImageUrl() {
        return $this->image;
    }

    public function getImageObject() {
        $image = imagecreatefromstring(file_get_contents($this->image));
        header('Content-Type: image/png');
        return imagepng($image);
    }
}
