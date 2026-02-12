<?php

class MessageBox {

    private $text = array();
    private $class = "alert-success";
    private $delay = 2000;

    public function getText() {
        return implode("<br>", $this->text);
    }

    public function addText($text) {
        $this->text[] = $text;
    }

    public function setClass($class) {
        $this->class = $class;
    }

    public function getClass() {
        return $this->class;
    }
	
    public function setDelay($delay) {
            $this->delay = $delay;
    }

    public function getDelay() {
            return $this->delay;
    }

}
