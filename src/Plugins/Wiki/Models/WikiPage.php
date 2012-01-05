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

namespace Fossil\Plugins\Wiki\Models;

use Fossil\Models\Model,
    Fossil\Plugins\Users\Models\User;

/**
 * Description of WikiPage
 *
 * @author predakanga
 * @Entity
 */
class WikiPage extends Model {
    /**
     * @Id @Column
     * @var string
     */
    protected $id;
    /**
     * @Column
     * @var string
     */
    protected $title;
    
    /** 
     * @OneToOne(targetEntity="WikiPageRevision")
     * @JoinColumn(name="currentRevision_id", referencedColumnName="id", nullable=true)
     * @var WikiPageRevision
     */
    protected $currentRevision;
    /**
     * @OneToMany(targetEntity="WikiPageRevision", mappedBy="page")
     * @var WikiPageRevision[]
     */
    protected $revisions;
    
    protected function setTitle($title) {
        // Title is WOM
        if($this->currentRevision) {
            throw new \Exception("Can't change the title once it's been created.");
        }
        // Slugify the title
        // Convert UTF-8 to ASCII if applicable
        $slug = $title;
        if(function_exists('iconv')) {
            $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
        }
        $slug = preg_replace('/[^\w]/', '_', $slug);
        $slug = strtolower($slug);
        $slug = trim($slug, '_');
        if($slug == "") {
            throw new \Exception("Invalid title: $title slugs to nothing\n");
        }
        // Check for an already-existing one
        $existingSlug = self::find($this->container, $slug);
        if($existingSlug && $existingSlug != $this) {
            throw new \Exception("Invalid title: $title slugs to $slug, which is already in use\n");
        }
        
        $this->id = $slug;
        $this->title = $title;
    }
    
    public function newRevision($content, $summary) {
        $newRevNo = 1;
        if($this->currentRevision) {
            $newRevNo = $this->currentRevision->revision + 1;
        }
        
        $newRev = new WikiPageRevision($this->container);
        $newRev->author = User::me($this->container);
        $newRev->revision = $newRevNo;
        $newRev->content = $content;
        $newRev->editSummary = $summary;
        $newRev->page = $this;
        $this->currentRevision = $newRev;
        $newRev->save();
    }
    
    public function delete() {
        // Get rid of the cyclic relationship first
        $this->currentRevision = null;
        $this->orm->flush();
        
        parent::delete();
        
        foreach($this->revisions as $rev) {
            $rev->delete();
        }
    }
}
