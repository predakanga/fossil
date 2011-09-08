<?php

namespace Fossil\Models;

/**
 * Description of Setting
 *
 * @author predakanga
 * @Entity
 * @F:InitialDataset("data/settings.yml")
 */
class Setting extends Model {
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    protected $id;
    
    /** @Column(type="string") */
    protected $name;
    
    /** @Column(type="string") */
    protected $value;
}

?>
