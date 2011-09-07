<?php

namespace Fossil\Models;

/**
 * Description of Setting
 *
 * @author predakanga
 * @Entity
 */
class Setting extends Model {
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    public $id;
    
    /** @Column(type="string") */
    public $name;
    
    /** @Column(type="string") */
    public $value;
}

?>
