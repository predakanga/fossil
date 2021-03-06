<?php

/*
 * Copyright (c) 2011, predakanga
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the <organization> nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Fossil\Plugins\Schedule\Commands;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputDefinition,
    Symfony\Component\Console\Input\InputArgument,
    Fossil\DoctrineExtensions\MemoryWriter,
    Fossil\Commands\BaseCommand,
    Fossil\Plugins\Schedule\Models\ScheduledTask,
    Fossil\Plugins\Schedule\Models\ScheduledTaskResult;

/**
 * Description of RunSchedule
 *
 * @author predakanga
 */
class RunSchedule extends BaseCommand {
    protected function configure() {
        $this->setName("runSchedule");
        $this->setDescription("Runs all currently due scheduled tasks");
        $definition = new InputDefinition(array());
        $this->setDefinition($definition);
    }
    
    protected function findTasks() {
        $cache = $this->container->get("Cache");
        $annoMgr = $this->container->get("AnnotationManager");
        $orm = $this->container->get("ORM");
        
        $cachedTasks = $cache->get("knownTasks", true);
        if(!$cachedTasks) {
            $cachedTasks = array();
            // Check for all classes with @Fossil\Plugins\Schedule\Annotations\Schedule
            $tasks = $annoMgr->getClassesWithAnnotation("Fossil\\Plugins\\Schedule\\Annotations\\Schedule");
            foreach($tasks as $taskClass) {
                // Grab the task name
                $taskNameAnno = $annoMgr->getClassAnnotations($taskClass, "F:Instanced");
                $isInstAnno = $annoMgr->getClassAnnotations($taskClass, "F:InstancedType");
                $scheduleAnno = $annoMgr->getClassAnnotations($taskClass, "Fossil\\Plugins\\Schedule\\Annotations\\Schedule");
                if(!$isInstAnno) {
                    throw new \Exception("$taskClass has a Schedule annotation, but is not an instanced class");
                }
                $taskName = "";
                if($taskNameAnno) {
                    if(isset($taskNameAnno[0]->name)) {
                        $taskName = $taskNameAnno[0]->name;
                    } elseif(isset($taskNameAnno[0]->value)) {
                        $taskName = $taskNameAnno[0]->value;
                    }
                } 
                if($taskName == "") {
                    $taskName = substr($taskClass, strrpos($taskClass, "\\")+1);
                }

                // Make sure that the model is up to date
                $taskModel = ScheduledTask::findOneByTask($this->container, $taskName);
                if(!$taskModel) {
                    $taskModel = ScheduledTask::create($this->container);
                    $taskModel->task = $taskName;
                    $taskModel->nextRun = new \DateTime();
                    $taskModel->period = $scheduleAnno[0]->period;
                    $taskModel->description = $scheduleAnno[0]->desc;
                    $taskModel->save();
                } else {
                    // Update the period and reschedule the task if it's changed
                    if($taskModel->period != $scheduleAnno[0]->period) {
                        $taskModel->period = $scheduleAnno[0]->period;
                        $taskModel->nextRun = new \DateTime();
                        $taskModel->nextRun->add(new \DateInterval($taskModel->period));
                        $taskModel->description = $scheduleAnno[0]->desc;
                    }
                }
                $cachedTasks[] = $taskName;
            }
            $cache->set("knownTasks", $cachedTasks, true);
            $orm->flush();
        }
        return $cachedTasks;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $start_time = microtime(true);
        
        // Check whether we have a list of tasks
        $taskNames = $this->findTasks();
        
        $tasksToRun = ScheduledTask::getDueTasks($this->container, $taskNames);
        
        $writer = new MemoryWriter();
        
        foreach($tasksToRun as $taskModel) {
            echo "Running {$taskModel->task}\n";
            $writer->clear();
            
            // Run the task
            $task = $this->container->createObject("task", $taskModel->task, array());
            $task->run($writer);
            
            // Save the result
            $runAt = new \DateTime();
            $result = ScheduledTaskResult::create($this->container);
            $result->result = $task->getResult();
            $result->output = $writer->getOutput();
            $result->runAt = $runAt;
            $result->scheduledItem = $taskModel;
            $result->save();
            
            $taskModel->results->add($result);
            // And update the next run time
            $taskModel->nextRun = clone $runAt;
            $taskModel->nextRun->add(new \DateInterval($taskModel->period));
            $taskModel->save();
        }
        
        $end_time = microtime(true);
        
        $time_passed = ($end_time - $start_time);
        
        $output->writeln("Ran all scheduled tasks. Took " . $time_passed . " seconds");
    }
}
