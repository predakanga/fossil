<?php

/**
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
 * 
 * @author predakanga
 * @since 0.1
 * @category Fossil Plugins
 * @package Users
 * @subpackage Models
 * @license https://github.com/predakanga/Fossil/blob/master/LICENSE.txt New BSD License
 */

namespace Fossil\Plugins\Users\Models;

use \Fossil\OM;

/**
 * @author predakanga
 * @Entity
 * @Table(name="FossilUser")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="inheritedType", type="string")
 * @DiscriminatorMap({"User" = "User"})
 * @F:InitialDataset("data/users.yml")
 */
class User extends \Fossil\Models\Model {
    const GENDER_UNKNOWN = 0;
    const GENDER_M = 1;
    const GENDER_F = 2;
    
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    protected $id;
    
    /** @Column(unique=true) */
    protected $name;
    
    /** @Column */
    protected $password;
    
    /** @Column */
    protected $email;
    
    /** @Column(type="datetime") */
    protected $joinDate;
    
    /** @Column */
    protected $avatar = "";
    
    /** @Column(type="integer") */
    protected $gender = self::GENDER_UNKNOWN;
    
    /** @Column(type="date") */
    protected $birthday;
    
    /** @Column */
    protected $timezone = "";
    
    /**
     * @ManyToOne(targetEntity="UserClass", inversedBy="members")
     * @JoinColumn(name="userClass_id", referencedColumnName="id", nullable=false)
     * @var UserClass
     */
    protected $userClass;
    
    /**
     * @ManyToMany(targetEntity="Permission")
     * @JoinTable(name="users_granted_permissions")
     * @var Permission[]
     */
    protected $grantedPermissions;
    /**
     * @ManyToMany(targetEntity="Permission")
     * @JoinTable(name="users_revoked_permissions")
     * @var Permission[]
     */
    protected $revokedPermissions;
    
    /** @OneToMany(targetEntity="PrivateMessageConversationParticipant", mappedBy="user") */
    protected $conversations;
    
    public function __construct($container) {
        parent::__construct($container);
        $this->userClass = $this->defaultUserclass();
        $this->joinDate = new \DateTime();
        $this->birthday = new \DateTime("1950-01-01");
    }
    
    protected function defaultUserclass() {
        return UserClass::findOneBy($this->container, array("name" => "Users"));
    }
    
    protected function hashPassword($value) {
        return md5($value);
    }
    
    protected function setPassword($value) {
        // Hash the password - temporary
        $this->password = $this->hashPassword($value);
    }
    
    public function verifyPassword($value) {
        return $this->hashPassword($value) == $this->password;
    }
    
    public static function me($container, $reattach=true) {
        static $me = null;
        static $attached = false;
        
        if($me) {
            if(!$attached && $reattach) {
                $me->reattach($container);
                $attached = true;
            }
            return $me;
        }
        // TODO: Add cookie support
        $session = $container->get("Session");
        $haveCookie = false;
        if(isset($session->get("FossilAuth")->user)) {
            $user = $session->get("FossilAuth")->user;
            $me = $user;
            if($reattach) {
                $user->reattach($container);
                $attached = true;
            } else {
                $user->restoreObjects($container);
                $attached = false;
            }
            return $user;
        } else {
            if(isset($session->get("FossilAuth")->userID)) {
                $user = self::find($container, $session->get("FossilAuth")->userID);
                $me = $user;
                $attached = true;
                $session->get("FossilAuth")->user = $user;
                return $user;
            } else if($haveCookie) {

            }
        }
        return null;
    }
    
    public function storeToSession() {
        if(self::me($this->container) == $this)
        $session = $this->container->get("Session");
        $session->get("FossilAuth")->user = $this;
    }
    
    public function getRoles() {
        return $this->userClass->roles->toArray();
    }
    
    public function getPermissions() {
        $permissions = array();
        foreach($this->getRoles() as $role) {
            $permissions = array_merge($permissions, $role->permissions->toArray());
        }
        // Add/subtract user-specific permissions
        foreach($this->revokedPermissions as $perm) {
            if($index = array_search($perm, $permissions, true)) {
                unset($permissions[$index]);
            }
        }
        foreach($this->grantedPermissions as $perm) {
            if(!in_array($perm, $permissions)) {
                $permissions[] = $perm;
            }
        }
        return $permissions;
    }
    
    public function hasRole(Role $role) {
        return in_array($role, $this->getRoles());
    }
    
    public function hasPermission(Permission $permission) {
        return in_array($permission, $this->getPermissions());
    }
    
    /**
     * @F:Memoize(postStore="storeToSession")
     */
    public function isDev() {
        return true;
    }
    
    /**
     * @F:Memoize(postStore="storeToSession")
     */
    public function isAdmin() {
        return true;
    }
    
    /**
     * @F:Memoize(postStore="storeToSession")
     */
    public function getUnreadConversationCount() {
        return PrivateMessageConversation::getUnreadCount($this->container, $this);
    }
    
    public function getAvatarURL($size) {
        if(empty($this->avatar)) {
            return $this->getGravatarURL($size);
        } else {
            return $this->avatar;
        }
    }
    
    public function getGravatarURL($size) {
        return "http://www.gravatar.com/avatar/" . md5(trim(strtolower($this->email))) . "?s=$size&amp;d=retro&amp;pg=r";
    }
}
