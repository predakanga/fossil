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

namespace Fossil\Plugins\Wiki\Controllers;

use Fossil\Plugins\Users\Controllers\LoginRequiredController,
    Fossil\Plugins\Wiki\Models\WikiPage,
    Fossil\Plugins\Wiki\Forms\EditWiki,
    Fossil\Plugins\Users\Annotations\Compilation\RequireRole,
    Fossil\Models\PaginationProxy;

/**
 * Description of Wiki
 *
 * @author predakanga
 */
class Wiki extends LoginRequiredController {
    /**
     * @F:Inject(type="ORM", lazy=true)
     * @var Fossil\ORM
     */
    protected $orm;
    
    public function indexAction() {
        return "view";
    }
    
    public function ensurePage() {
        $strPage = $this->getArgument("page");
        $page = null;
        if(!$strPage) {
            // Default case, for when no page specified
            $strPage = "index";
            $page = WikiPage::find($this->container, $strPage);
        }
        if(!$page) {
            // If we still have no page, create it
            $page = WikiPage::create($this->container);
            $page->title = $strPage;
            $page->save();
        }
        return $page;
    }
    
    public function runList() {
        $listQuery = $this->orm->getEM()->createQuery("SELECT wp FROM Fossil\Plugins\Wiki\Models\WikiPage wp");
        return $this->templateResponse("fossil:wiki/list", array("pages" => $listQuery));
    }
    
    public function runView(WikiPage $page = null, $revision = 0) {
        if(!$page) {
            $page = $this->ensurePage();
            $strPage = $this->getArgument("page");
            if($page->id != $strPage) {
                return $this->redirectResponse("?controller=wiki&action=view&page={$page->id}");
            }
        }
        if(!$page->currentRevision) {
            return $this->templateResponse("fossil:wiki/create", array("page" => $page));
        }
        $revModel = null;
        if(!$revision) {
            $revModel = $page->currentRevision;
        } else {
            foreach($page->revisions as $kRevModel) {
                if($kRevModel->revision == $revision) {
                    $revModel = $kRevModel;
                    break;
                }
            }
        }
        if($revModel) {
            return $this->templateResponse("fossil:wiki/view", array("page" => $page,
                                                                     "rev" => $revModel));
        } else {
            return $this->templateResponse("fossil:wiki/invalidRev", array("page" => $page));
        }
    }
    
    public function runEdit(EditWiki $form, WikiPage $page = null) {
        if($form->isSubmitted()) {
            if($form->isValid()) {
                // Make sure the summary has actually changed
                $page = WikiPage::find($this->container, $form->page);
                if(!$page) {
                    throw new \Fossil\Exceptions\NoSuchTargetException("Unknown page specified");
                }
                // Save the title, if it's our first edit
                if(!$page->currentRevision) {
                    $page->title = $form->title;
                }
                // Only save the revision when we have no existing rev,
                // or there's been a change in the content
                if(!$page->currentRevision || $page->currentRevision->content != $form->content) {
                    $page->newRevision($form->content, $form->summary);
                }
                return $this->redirectResponse("?controller=wiki&action=view&page={$page->id}");
            } else {
                $page = WikiPage::find($this->container, $form->page);
            }
        } else {
            if(!$page) {
                $page = $this->ensurePage();
                $strPage = $this->getArgument("page");
                if($page->id != $strPage) {
                    return $this->redirectResponse("?controller=wiki&action=edit&page={$page->id}");
                }
            }
            $form->page = $page->id;
            if($page->currentRevision) {
                $form->content = $page->currentRevision->content;
            } else {
                $form->setFieldType("title", "text");
                $form->title = $page->id;
            }
        }
        return $this->templateResponse("fossil:wiki/edit", array("page" => $page));
    }
    
    public function runDelete(WikiPage $page, $confirm = false) {
        if($confirm == "yes") {
            $page->delete();
            return $this->templateResponse("fossil:wiki/deleteConfirmed");
        } else {
            return $this->templateResponse("fossil:wiki/delete", array("page" => $page));
        }
    }
    
    public function runHistory(WikiPage $page) {
        return $this->templateResponse("fossil:wiki/history", array("page" => $page));
    }
}
