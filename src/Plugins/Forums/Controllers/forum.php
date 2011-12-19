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

namespace Fossil\Plugins\Forums\Controllers;

use Fossil\OM,
    Fossil\Requests\BaseRequest,
    Fossil\Exceptions\NoSuchInstanceException,
    Fossil\Plugins\Users\Controllers\LoginRequiredController,
    Fossil\Plugins\Users\Models\User,
    Fossil\Plugins\Forums\Models\Forum as ForumModel,
    Fossil\Plugins\Forums\Models\ForumTopic,
    Fossil\Plugins\Forums\Models\ForumPost,
    Fossil\Plugins\Forums\Forms\NewTopic,
    Fossil\Plugins\Forums\Forms\NewPost;

/**
 * Description of Forum
 *
 * @author predakanga
 */
class Forum extends LoginRequiredController {
    public function indexAction() {
        return "list";
    }
    
    public function runList() {
        $data = array('categories' => $this->collectSubforums());
        return OM::obj("Responses", "Template")->create("fossil:forums/listForums", $data);
    }
    
    public function runViewForum(ForumModel $id) {
        // TODO: Rewrite templates to use forum= in links
        $forum = $id;
        return OM::obj("Responses", "Template")->create("fossil:forums/viewForum", array('forum' => $forum));
    }
    
    public function runViewTopic(ForumTopic $id, NewPost $form) {
        // TODO: Rewrite templates to use topic= in links
        $topic = $id;
        $topic->viewCount++;
        $form->tid = $topic->id;
        return OM::obj("Responses", "Template")->create("fossil:forums/viewTopic", array('topic' => $topic));
    }
    
    public function runNewTopic(NewTopic $form) {
        if($form->isSubmitted() && $form->isValid()) {
            $forum = ForumModel::find($this->container, $form->fid);
            if(!$forum)
                throw new NoSuchInstanceException("Subforum not found");
            // Create the new topic
            $topic = new ForumTopic();
            $topic->author = User::me();
            $topic->forum = $forum;
            $topic->name = $form->title;
            $topic->save();
            $firstPost = new ForumPost();
            $firstPost->postedAt = new \DateTime();
            $firstPost->topic = $topic;
            $firstPost->content = $form->content;
            $firstPost->author = User::me();
            $firstPost->save();
            
            // And bounce back to the list page
            return OM::obj("Responses", "Redirect")->create("?controller=forum&action=viewForum&id={$forum->id}");
        } else {
            if(!isset($req->args['fid']))
                throw new NoSuchInstanceException("Subforum not found");
            $form->fid = $req->args['fid'];
            return OM::obj("Responses", "Template")->create("fossil:forums/newTopic");
        }
    }
    
    public function runNewPost(NewPost $form) {
        if(!$form->isSubmitted()) {
            throw new NoSuchInstanceException("You shouldn't be here...");
        } else if(!$form->isValid()) {
            // Show the user an appropriate message
            die("Form was invalid");
        }
        $topic = ForumTopic::find($this->container, $form->tid);
        if(!$topic) {
            throw new NoSuchInstanceException("Topic not found");
        }
        // Create the new post
        $newPost = new ForumPost();
        $newPost->postedAt = new \DateTime();
        $newPost->topic = $topic;
        $newPost->content = $form->content;
        $newPost->author = User::me();
        $newPost->save();
        // And redirect back to the topic
        // TODO: Redirect to the last page
        return OM::obj("Responses", "Redirect")->create("?controller=forum&action=viewTopic&id={$topic->id}");
    }
    
    protected function collectSubforums() {
        $cats = array();
        $forums = OM::ORM()->getEM()->createQuery("SELECT forum, category
                                                   FROM Fossil\Plugins\Forums\Models\Forum forum
                                                   LEFT JOIN forum.category category");
        $catKeyMap = array();
        foreach($forums->getResult() as $forum) {
            // Check whether user can view forum
            if($forum->canBeViewedBy(User::me())) {
                $category = $forum->category;
                
                $catInfo = array('name' => "Uncategorized", 'id' => "none");
                if($category) {
                    $catInfo['name'] = $category->name;
                    $catInfo['id'] = $category->id;
                }
                $key = count($cats);
                if(isset($catKeyMap[$catInfo['id']]))
                    $key = $catKeyMap[$catInfo['id']];
                else
                    $catKeyMap[$catInfo['id']] = $key;
                
                if(!isset($cats[$key]))
                    $cats[$key] = array('info' => $catInfo, 'forums' => array());
                $cats[$key]['forums'][] = $forum;
            }
        }
        
        return $cats;
    }
}

?>
