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
                                    'debug'         => false,
                                    'max-age'       => 0,
                                    'modelPath'    => '../model',
                                    'model'         => null,
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

        if ($settings = parse_ini_file($config, true))
        {
            // Object configuration
            if (!empty($settings['service']) && is_array($settings['service']))
            {
                $this->_config = array_merge($this->_config, $settings['service']);

                if (empty($this->_config['max-age']) || ($this->_config['max-age'] <= 0))
                    $this->_config['cache'] = array('Cache-Control: no-cache');
                else
                {
                    $this->_config['cache'] = array(
                                                    'Last-Modified: ' . date('c'),
                                                    'Cache-Control: public, must-revalidate, max-age=' . $this->_config['max-age'],
                                                    );
                }

                if (empty($this->_config['model']))
                    $this->_serverError('Unable to find the model name. Please, set "model = " in the [service] section of your config file.');
                else
                {
                    // Grab the model config
                    if (!empty($settings['config']) && is_array($settings['config']))
                        $config = $settings['config'];
                    else
                        $config = array();

                    // Model!
                    require(trim($this->_config['modelPath'], ' /') . '/' . $this->_config['model'] . '.php');
                    $this->_model = new $this->_config['model']($config);

                    // Some validation...
                    if (!method_exists($this->_model, '__invoke'))
                        $this->_serverError('Unable to find an "__invoke" method.');
                }

            }
            else
                $this->_serverError('Unable to find a [service] section the ini configuration file.');
        }
        else
            $this->_serverError('Unable to read ini configuration from ' . $config . '.');
    }

    /**
     * Give a JSON response
     *
     * @return void
     */
    public function run()
    {
        // No CSRF protection
        // Be careful if authenticated by cookie

        // Bug!!! https://bugs.php.net/bug.php?id=50029
        // $response = $this->_model($_GET);

        $response = $response = $this->_model->__invoke($_GET);
        header('HTTP/1.1 200 OK');
        if($this->_config['debug'])
        {
            header('Cache-Control: no-cache');
            header('Content-Type: text/plain');
            var_dump($response);
        }
        else
        {
            foreach($this->_config['cache'] as $cacheHeader)
                header($cacheHeader);
            header('Content-Type: application/json');
            echo json_encode((array) $response); // Encoding... no XSS risk
        }
    }
}