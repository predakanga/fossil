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

namespace Fossil\Util;

use Fossil\Object,
    Fossil\Plugins\Users\Models\User;

/**
 * Description of Crypto
 *
 * @author predakanga
 */
class Crypto extends Object {
    public function encrypt($plaintext, $forUser = null) {
        $cryptKey = $this->getSitewideKey();
        
        // First, take care of the user sub-envelope
        if($forUser) {
            // Package up our contents into something that we can decode
            $plaintext = $forUser->id . "$" . $this->encryptUserData($plaintext, $forUser, false);
        }
        
        // Next, generate the HMAC
        $signature = hash_hmac("sha256", $plaintext, $cryptKey, true);
        
        // Finally, combine and encrypt
        $totalMessage = $signature . $plaintext;
        $cyphertext = $this->_encrypt($cryptKey, $totalMessage);
        
        // And return the base64
        return base64_encode($cyphertext);
    }
    
    public function encryptUserData($plaintext, $forUser, $base64Encode = true) {
        assert($forUser instanceof User);
        
        $cyphertext = $this->_encrypt($forUser->cryptKey, $plaintext);
        if($base64Wrap) {
            $cyphertext = base64_encode($cyphertext);
        }
        return $cyphertext;
    }
    
    public function decrypt($cyphertext, $forUser = false) {
        $cryptKey = $this->getSitewideKey();
        
        // First, decrypt the envelope
        $realCypherText = base64_decode($cyphertext);
        $envelope = $this->_decrypt($cryptKey, $realCypherText);
        // Then, extract the SHA256 HMAC
        // 32 chars, in full binary
        $signature = substr($envelope, 0, 32);
        // And confirm it
        // TODO: Use a seperate key for the hash? Should still be safe, only guards against
        // bit-flipping attacks
        $contents = substr($envelope, 32);
        $contentsHash = hash_hmac("sha256", $contents, $cryptKey, true);
        
        if($contentsHash != $signature) {
            throw new \Exception("Encrypted message seems to have been tampered with");
        }
        
        // Finally, if it's forUser, decrypt the internal data
        if($forUser) {
            $pieces = explode('$', $contents, 2);
            $uid = $pieces[0];
            $user = User::find($uid);
            return $this->decryptUserData($pieces[1], $user, false);
        } else {
            return $contents;
        }
    }
    
    public function decryptUserData($cyphertext, $forUser, $base64Decode = true) {
        assert($forUser instanceof User);
        // For now at least, there's no HMAC on the user data, since it should never be exposed
        if($base64Decode) {
            $cyphertext = base64_decode($cyphertext);
        }
        return $this->_decrypt($forUser->cryptKey, $cyphertext);
    }
    
    protected function _encrypt($key, $plaintext) {
        // Generate an IV
        $ivSize = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_RANDOM);
        
        $cyphertext = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $plaintext, MCRYPT_MODE_CFB, $iv);
        return $iv . $cyphertext;
    }
    
    protected function _decrypt($key, $cyphertext) {
        // Extract the IV and the actual cyphertext
        $ivSize = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CFB);
        $iv = substr($cyphertext, 0, $ivSize);
        $realCyphertext = substr($cyphertext, $ivSize);
        
        // And decrypt it
        $plaintext = mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $realCyphertext, MCRYPT_MODE_CFB, $iv);
        return $plaintext;
    }
    
    /** PBKDF2 Implementation (described in RFC 2898)
     *
     *  @param string password password
     *  @param string salt salt
     *  @param int iterations iteration count (use 1000 or higher)
     *  @param int keyLength derived key length
     *  @param string algo hash algorithm
     *
     *  @return string derived key
    */
    public function pbkdf2($password, $salt, $iterations, $keyLength, $algo = 'sha256') {
        $hl = strlen(hash($algo, null, true)); # Hash length
        $kb = ceil($keyLength / $hl);              # Key blocks to compute
        $dk = '';                           # Derived key
     
        # Create key
        for ( $block = 1; $block <= $kb; $block ++ ) {
     
            # Initial hash for this block
            $ib = $b = hash_hmac($algo, $salt . pack('N', $block), $password, true);
     
            # Perform block iterations
            for ( $i = 1; $i < $iterations; $i ++ )
     
                # XOR each iterate
                $ib ^= ($b = hash_hmac($algo, $b, $password, true));
     
            $dk .= $ib; # Append iterated block
        }
     
        # Return derived key of correct length
        return substr($dk, 0, $keyLength);
    }
    
    protected function getSitewideKey() {
        static $cryptKey;
        
        if(!$cryptKey) {
            $settings = $this->container->get("Settings");
            $cryptKey = base64_decode($settings->get("Fossil", "CryptKey"));
            if(!$cryptKey) {
                throw new \Exception("No site-wide key was set");
            }
        }
        return $cryptKey;
    }
    
    protected $bcrypt_workfactor = 8;
    
    public function bcrypt($input) {
        require_once(__DIR__ . D_S . "../libs/PasswordHash.php");
        
        $hasher = new \PasswordHash($this->bcrypt_workfactor, false);
        return $hasher->HashPassword($input);
    }
    
    public function bcrypt_check($input, $hash) {
        require_once(__DIR__ . D_S . "../libs/PasswordHash.php");
        
        $hasher = new \PasswordHash($this->bcrypt_workfactor, false);
        return $hasher->CheckPassword($input, $hash);
    }
}
