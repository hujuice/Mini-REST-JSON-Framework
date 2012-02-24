<?php
class myWs extends Members
{
    /**
     * Read data from a file
     *
     * @param array $config
     * @return void
     */
    public function __construct($config)
    {
        parent::__construct($config['members']);
    }

    /**
     * Receive request data and call the needed method
     *
     * @param array $params
     * @return mixed
     */
    public function __invoke($params)
    {
        if (isset($params['action']))
        {
            switch($param['action'])
            {
                case 'planet':
                    if (isset($params['planet']))
                        return $this->getFromPlanet($params['planet']);
                case 'humans':
                    return $this->getHumans();
                default
                    return;
            }
        }
    }
}