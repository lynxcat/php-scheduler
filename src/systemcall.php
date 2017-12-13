<?php 
    namespace Lynxcat;
    
    class SystemCall{
        private $callback;

        public function __construct($callback){
            $this->callback = $callback;
        }

        public function __invoke(Task $task, Scheduler $scheduler){
            $callback = $this->callback;
            $callback($task, $scheduler);
        }
    }