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

namespace Fossil\Plugins\Schedule\Tasks;

use Fossil\Plugins\Schedule\Annotations\Schedule,
    Fossil\Tasks\BaseTask,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of ClearOldScheduleResults
 *
 * @author predakanga
 * @Schedule(period="P1D", desc="Clears old schedule results")
 */
class ClearOldScheduleResults extends BaseTask {
    /**
     * @F:Inject("ORM")
     * @var Fossil\ORM
     */
    protected $orm;
    
    public function run(OutputInterface $out) {
        $out->writeln("Deleting successful schedule results other than the most recent");
        // TODO: Find a better way to do this
        $dql = "SELECT str, st FROM Fossil\Plugins\Schedule\Models\ScheduledTaskResult str
                               JOIN str.scheduledItem st
                WHERE str.result = ?1
                ORDER BY str.id DESC";
        $q = $this->orm->getEM()->createQuery($dql)->setParameter(1, BaseTask::RESULT_SUCCEEDED);
        $objs = $q->getResult();
        $firstSkipped = array();
        $toDel = array();
        foreach($objs as $str) {
            if(isset($firstSkipped[$str->scheduledItem->id])) {
                $toDel[] = $str->id;
            } else {
                $firstSkipped[$str->scheduledItem->id] = true;
            }
        }
        if(count($toDel)) {
            $dql = "DELETE Fossil\Plugins\Schedule\Models\ScheduledTaskResult str
                    WHERE str.id IN (?1)";
            $q = $this->orm->getEM()->createQuery($dql)->setParameter(1, $toDel);
            $numDel = $q->getSingleScalarResult();
            $out->writeln("Deleted $numDel successful schedule results");
        } else {
            $out->writeln("No successful schedule results to delete");
        }
        
        $out->writeln("Deleting unsuccessful schedule results older than a week");
        $cutoff = new \DateTime();
        $cutoff->sub(new \DateInterval("P1W"));
        $dql = "DELETE Fossil\Plugins\Schedule\Models\ScheduledTaskResult str
                WHERE str.result = ?1 AND str.runAt <= ?2";
        $q = $this->orm->getEM()->createQuery($dql)->setParameter(1, BaseTask::RESULT_FAILED)
                                                   ->setParameter(2, $cutoff);
        $numDel = $q->getSingleScalarResult();
        $out->writeln("Deleted $numDel unsuccessful schedule results");
        $this->result = BaseTask::RESULT_SUCCEEDED;
    }
}
