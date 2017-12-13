<?php 
    namespace Lynxcat;
    class YieldReturnValue {
        private $value;

        public function __construct($val){
            $this->value = $val;
        }

        public function current(){
            return $this->value;
        }
    }