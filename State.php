<?php
    class State{
        public $id;
        public $nextState;

        public function __construct($i){
            $this->id = $i;
            $this->nextState = array();
        }

        public function hasKey($x){
            return array_key_exists($x, $this->nextState);
        }
    }