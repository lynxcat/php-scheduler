<?php 
    namespace Lynxcat;
    use Lynxcat\YieldReturnValue;

    class Task{
        private $coroutine, $tid, $context, $sendValue, $flag = true, $step = 0;

        public function __construct(int $id, \Generator $coroutine){
            $this->tid = $id;
            $this->coroutine = new \SplStack();
            $this->coroutine->push($coroutine);
        }

        public function run(){
            $this->step++;

            if(!$this->isFinished()){
                $coroutine = $this->coroutine->pop();

                if($this->flag){    
                    $this->flag = false;
                }else{
                    $coroutine->send($this->sendValue);
                    $this->setSendValue(null);
                }
                
                $res = $coroutine->current();

                if(!$this->isFinished($coroutine)){
                    $this->coroutine->push($coroutine);
                }

                if($res instanceof \Generator){
                    $this->coroutine->push($res);
                    $this->flag = true;
                    $this->setSendValue(null);

                }else if($res instanceof YieldReturnValue){
                    $this->coroutine->pop();
                    $this->flag = false;
                    $this->setSendValue($res->current());
                }

                return $res;
            }

            return false;
        }

        public function setSendValue($val){
            $this->sendValue = $val;
        }

        public function getTaskId(){
            return $this->tid;
        }

        public function getStep(){
            return $this->step;
        }

        public function isFinished($coroutine = null){
            if($coroutine == null){
                return $this->coroutine->isEmpty();
            }else if($coroutine instanceof \Generator){
                return !$coroutine->valid();
            }else{
                throw new Exception("coroutine is not Generator or null", 1);   
            }
        }
    }

