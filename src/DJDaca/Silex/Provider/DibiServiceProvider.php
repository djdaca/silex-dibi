<?php

namespace DJDaca\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Dibi\Connection;

/**
 * Dibi Provider.
 *
 * @author Daniel Čekan <djdaca@seznam.cz>
 */
class DibiServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['db.default_options'] = array(
            'driver' => 'mysql',
            'database' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        );
        
        $app['database.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;
            if ($initialized) {
                return;
            }
            $initialized = true;
            if (!isset($app['database.options'])) {
                $app['database.options'] = array('default' => isset($app['db.options']) ? $app['db.options'] : array());
            }
            $tmp = $app['database.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($app['db.default_options'], $options);
                if (!isset($app['database.default'])) {
                    $app['database.default'] = $name;
                }
            }
            $app['database.options'] = $tmp;
        });
        
        $app['database'] = function ($app) {
            $app['database.options.initializer']();
            $dbs = new Container();
            foreach ($app['database.options'] as $name => $config) {
                $dbs[$name] = function ($dbs) use ($config, $name) {
                    return new Connection($config, $name);
                };
            }
            return $dbs;
        };
        
        // shortcuts for the "first" DB
        $app['db'] = function ($app) {
            $dbs = $app['database'];
            return $dbs[$app['database.default']];
        };
        $app['db.config'] = function ($app) {
            $dbs = $app['database.config'];
            return $dbs[$app['database.default']];
        };
    }
}