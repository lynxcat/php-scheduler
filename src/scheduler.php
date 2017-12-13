<?php 
    namespace Lynxcat;
    use Lynxcat\SystemCall;
    use Lynxcat\Task;

    class Scheduler{
        private $task_queue, $task_id_maps = [], $task_id = 0, $events = [], $step, $finished_ids = [];


        public function __construct(){
            $this->task_queue = new \SplQueue();
        }

        public function run(){
            try {
                while(!$this->task_queue->isEmpty()){
                    $this->step++;
                    $task = $this->task_queue->dequeue();

                    $task_id = $task->getTaskId();
                    if(!isset($this->task_id_maps[$task_id])){
                        continue;
                    }

                    if($this->task_id_maps[$task_id]['status'] == "sleep" && isset($this->task_id_maps[$task_id]['sleep_time']) && $this->task_id_maps[$task_id]['sleep_time'] <= microtime(true)){
                        $this->task_id_maps[$task_id]['status'] = "run";
                    }

                    if($this->task_id_maps[$task_id]['status'] == "run"){
                        $res = $task->run();

                        if($res instanceof SystemCall){
                            $res($task, $this);
                            continue;
                        }
                    }

                    if(!$task->isFinished()){
                        $this->schedule($task);
                    }else{
                        unset($this->task_id_maps[$task_id]);
                        array_push($this->finished_ids, $task_id);
                        $this->trigger("taskFinished", $this->finished_ids);
                    }
                }
            } catch (\Exception $e) {
                throw new Exception("Error Scheduler!", 1);
            }
        }

        public function getFinishedTaskIds(){
            return $this->finished_ids;
        }

        public function exec(\Generator $coroutine){
            $task = new Task(++$this->task_id, $coroutine);
            $this->task_id_maps[$task->getTaskId()] = ['status' => 'run'];
            $this->schedule($task);
            return $task->getTaskId();
        }

        public function schedule(Task $task){
            $this->task_queue->enqueue($task);
        }

        public function getStep(){
            return $this->step;
        }

        public function wait(Task $task, $child_ids){
            if(isset($this->task_id_maps[$task->getTaskId()])){
                $this->task_id_maps[$task->getTaskId()]['status'] = "sleep";

                $this->on("taskFinished", function($ids) use($task, $child_ids){
                    $flag = array_diff($child_ids, $ids);

                    if(empty($flag)){
                        if(isset($this->task_id_maps[$task->getTaskId()])){
                            $this->task_id_maps[$task->getTaskId()]['status'] = "run";
                        }
                    }
                });

                return true;
            }
            return false;
        }

        public function sleep($task_id, $time = null){
            $this->task_id_maps[$task_id]['status'] = "sleep";

            if($time != null && is_int($time)){
                $this->task_id_maps[$task_id]['sleep_time'] = microtime(true) + $time / 1000;
            }
        }

        public function kill($task_id){
            if(isset($this->task_id_maps[$task_id])){
                unset($this->task_id_maps[$task_id]);
                return true;
            }
            return false;
        }


        public function on($event, $callback){
           if(!isset($this->events[$event])){
                $this->events[$event] = array();
            }

            array_push($this->events[$event], $callback);
        }

        public function trigger($event, $ids){
            $callbacks = isset($this->events[$event]) ? $this->events[$event] : [];

            foreach ($callbacks as $callback) {
                $callback($ids);
            }
        }
    }
