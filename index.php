<?php

    require_once('./vendor/autoload.php');

    use Lynxcat\Scheduler;
    use Lynxcat\Task;
    use Lynxcat\SystemCall;
    use Lynxcat\YieldReturnValue;


    function getTaskId(){
        return new SystemCall(function(Task $task, Scheduler $scheduler){
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    function newTask(\Generator $coroutine){
        return new SystemCall(function(Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->exec($coroutine));
            $scheduler->schedule($task);
        });
    }

    function waitpid($childs){
        return new SystemCall(function(Task $task, Scheduler $scheduler) use ($childs) {
            $childs = is_array($childs) ? $childs : [$childs];
            $flag = array_diff($childs, $scheduler->getFinishedTaskIds());

            if(empty($flag)){
                $scheduler->schedule($task);
            }else{
                $scheduler->wait($task, $childs);
                $scheduler->schedule($task);
            }
        });
    }

    function sleepself($time){
        return new SystemCall(function(Task $task, Scheduler $scheduler) use ($time) {
            $scheduler->sleep($task->getTaskId(), $time);
            $scheduler->schedule($task);
        });
    }

    function child(){
        $i = 0;

        while($i < 5){
            echo $i++;
            echo "\n";
            yield;
        }
    }

    function getTest(){
        yield new YieldReturnValue("2etst\n");
    }


    function gen(){
        echo "start";
        echo "\n";
        echo yield getTest();
        $child = yield newTask(child());
        $child2 = yield newTask(child());

        yield waitpid([$child, $child2]);
        $i = 5;

        //睡眠3秒， 以毫秒为单位
        yield sleepself(3000);

        while($i < 10){
            echo $i++;
            echo "\n";
            yield;
        }

        echo "test";
        echo "\n";
    }

    function test(){
        $child = yield newTask(gen());
        yield waitpid($child);
        echo "test child is:".$child."\n";
        echo "运行一点东西\n";
        yield;
        echo "释放控制权在调度";
    }

    $scheduler = new Scheduler();
    $scheduler->exec(test());
    $scheduler->run();