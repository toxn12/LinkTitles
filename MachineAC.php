<?php
    class MachineAC{
        
        private $state;
        private $op;
        private $failure;

        public function __construct($terms){
            $this->state = array();
            $this->state[] = new State(0);
            $this->op = array();
            $this->op[] = array();
            self::makeGoto($terms);
            self::makeFailure();
        }

        public function match($query){
            $s = 0;
            $chars = self::mb_str_split($query);
            $ret = array();
            for($i = 0; $i < count($chars); $i++){
                while(is_null(self::g($s, $chars[$i]))){
                    $s = $this->failure[$s];
                }
                $s = self::g($s, $chars[$i]);
                foreach($this->op[$s] as $x){
                    $ret[] = self::addResult($x, $i);
                }
            }
            return $ret;
        }

        private function addResult($found, $pos){
            return array("index" => $pos - mb_strlen($found, 'UTF-8') + 1, "keyword" => $found);
        }

        private function makeGoto($terms){
            foreach($terms as $term){
                $cur = $this->state[0];
                foreach(self::mb_str_split($term) as $x){
                    if(!$cur->hasKey($x)){
                        $new = new State(count($this->state));
                        $cur->nextState[$x] = $new;
                        $this->state[] = $new;
                        $this->op[] = array();
                    }
                    $cur = $cur->nextState[$x];
                }
                $s = $cur->id;
                $this->op[$s][] = $term;
            }
        }

       private function makeFailure(){
           $fail = array();
           for($i = 0 ; $i < count($this->state); $i++){
               $fail[] = 0;
           }
           $queue = array(0);
           while(count($queue) > 0){
               $s = array_pop($queue);
               foreach(array_keys($this->state[$s]->nextState) as $x){
                   $nextState = self::g($s, $x);
                   if(!is_null($nextState)){
                       $queue[] = $nextState;
                   }
                   if($s != 0){
                       $f = $fail[$s];
                       while(is_null(self::g($f, $x))){
                           $f = $fail[$f];
                       }
                       $fail[$nextState] = self::g($f, $x);
                       foreach($this->op[$fail[$nextState]] as $w){
                           $this->op[$nextState][] = $w;
                       }
                   }
               }
           }
           $this->failure= $fail;
       }

       private function g($s, $x){
               if(array_key_exists($x, $this->state[$s]->nextState)){
                   return $this->state[$s]->nextState[$x]->id;
               }else{
                   if ($s == 0){
                       return 0;
                   }else{
                       return null;
                   }
               }
       }

       private function mb_str_split($str, $split_len = 1){
           mb_internal_encoding('UTF-8');
           mb_regex_encoding('UTF-8');

           if($split_len <= 0){
               $split_len = 1;
           }

           $strlen = mb_strlen($str, 'UTF-8');
           $ret = array();
           
           for ($i = 0; $i < $strlen; $i += $split_len){
               $ret[] = mb_substr($str, $i, $split_len);
           }
           return $ret;
       }
   }

