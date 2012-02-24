<?php
class Members
{
    /**
     * Cosmic site members
     * @var array
     */
    protected $_members;

    /**
     * Read data from a file
     *
     * @param string $config
     * @return void
     */
    public function __construct($file)
    {
        $this->_members = include($file);
    }

    /**
     * Select by planet
     *
     * @param string $planet
     * @return integer
     */
    public function getFromPlanet($planet)
    {
        $count = 0;
        foreach ($this->_members as $member)
        {
            if ($member[1] == $planet)
                $count++;
        }
        return $count;
    }

    /**
     * How many humans?
     *
     * @return integer
     */
    public function getHumans()
    {
        $count = 0;
        foreach ($this->_members as $member)
        {
            if ('Human' == $member[2])
                $count++;
        }
        return array('humans' => $count);
    }
}