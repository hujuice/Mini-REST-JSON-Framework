<?php
/**
 * Very simple REST-JSON Framework class
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sergio Vaccaro <hujuice@inservibile.org>
 * @version 1.0
 * @copyright Copyright (c) 2012, Sergio Vaccaro <hujuice@inservibile.org>
 * @license http://opensource.org/licenses/GPL-3.0 GNU Public License
 */
class RestJson
{
    /**
     * Service configuration
     * @var array
     */
    protected $_config = array(
                            'model'     => null,
                            'modelPath' => '../model',
                            'timezone'  => 'UTC',
                            'max-age'   => 0,
                            'debug'     => false,
                            );

    /**
     * The model
     * @var mixed
     */
    protected $_model;

    /**
     * Immediately close with a 500 header and an error message
     *
     * @param string $errorMsg
     * @return void
     */
    protected function _serverError($message = '')
    {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain');
        echo $message, PHP_EOL;
        exit;
    }

    /**
     * Error handler
     *
     * @param integer $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return boolean
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if ($this->_config['debug'])
            return false; // Back to ordinary errors
        else
            $this->_serverError($errstr); // Will exit
    }

    /**
     * Exception handler
     *
     * @param Exception $exception
     * @return void
     */
    public function exceptionHandler($exception)
    {
        if ($this->_config['debug'])
            $this->_serverError('Model Exception:' . "\n" . $exception->getTraceAsString()); // Will exit
        else
            $this->_serverError('Model Error'); // Will exit
    }

    /**
     * Read a configuration file and create the model
     *
     * @param string $config
     * @return void
     */
    public function __construct($config)
    {
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));

        // Check check check!

        // Configuration file
        if (!$settings = parse_ini_file($config, true))
            $this->_serverError('Unable to read ini configuration from ' . $config . '.');

        // Service configuration
        if (empty($settings['service']) || !is_array($settings['service']))
            $this->_serverError('Unable to find a [service] section the ini configuration file.');

        // Model name
        if (empty($settings['service']['model']))
            $this->_serverError('Unable to find the model name. Please, set "model = " in the [service] section of your config file.');

        // End check

        // Prepare the service configuration
        $this->_config = array_merge($this->_config, array_filter($settings['service']));

        // Set the timezone
        date_default_timezone_set($this->_config['timezone']);

        // Prepare cache headers
        if ($this->_config['debug'] || empty($this->_config['max-age']) || ((integer) $this->_config['max-age'] <= 0))
            $this->_config['cache'] = array('Cache-Control: no-cache');
        else
            $this->_config['cache'] = array(
                                            'Last-Modified: ' . date(DATE_RFC1123),
                                            'Cache-Control: max-age=' . (integer) $this->_config['max-age'] . ', must-revalidate',
                                            );

        // Prepare the model configuration
        if (!empty($settings['model']) && is_array($settings['model']))
            $modelConfig = $settings['model'];
        else
            $modelConfig = array();

        // Build the model!
        require($this->_config['modelPath'] . '/' . $this->_config['model'] . '.php');
        $this->_model = new $this->_config['model']($modelConfig);

        // Validate the model...
        if (!method_exists($this->_model, '__invoke'))
            $this->_serverError('Unable to find an "__invoke" method.');
    }

    /**
     * Give a JSON response
     *
     * @return void
     */
    public function run()
    {
        // Try to revalidate the cache
        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
        {
            if ((strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $this->_config['max-age']) > time()) // fresh!
            {
                header("HTTP/1.1 304 Not Modified");
                exit;
            }
        }

        // No CSRF protection
        // Be careful if authenticated by cookie

        // Bug!!! https://bugs.php.net/bug.php?id=50029
        // $response = $this->_model($_GET);
        $response = $response = $this->_model->__invoke($_GET);

        // Headers
        header('HTTP/1.1 200 OK');
        foreach($this->_config['cache'] as $cacheHeader)
            header($cacheHeader);

        // Return response
        if($this->_config['debug'])
        {
            header('Content-Type: text/plain');
            var_dump($response);
        }
        else
        {
            header('Content-Type: application/json');
            echo json_encode((array) $response); // Encoding... no XSS risk
        }
    }
}