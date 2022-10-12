<?php


namespace Genpak\Plugins\Migration;


use Illuminate\Database\Capsule\Manager as Capsule;

class BaseClass
{
    /**
     * @return array|bool
     */
    public function getSourceCredentials()
    {
        $gpm_options = get_option('gpm_options');

        if (empty($gpm_options['driver']) || empty($gpm_options['host']) || empty($gpm_options['port'])
            || empty($gpm_options['database']) || empty($gpm_options['username'])) {

            return false;
        }

        return $credentials = [
            'driver' => $gpm_options['driver'],
            'host' => $gpm_options['host'],
            'port' => $gpm_options['port'],
            'database' => $gpm_options['database'],
            'username' => $gpm_options['username'],
            'password' => $gpm_options['password'],
            'charset' => 'utf8',
        ];
    }

    /**
     * @return array
     */
    public function getDestinationCredentials()
    {
        return $credentials = [
            'driver' => 'mysql',
            'host' => DB_HOST,
            'port' => '3306',
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
        ];
    }

    /**
     * @param $credentials
     * @return Capsule
     */
    public function getConnection($credentials)
    {
        $capsule = new Capsule;

        $capsule->addConnection($credentials);

        // Make this Capsule instance available globally via static methods... (optional)
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $capsule->bootEloquent();

        return $capsule;
    }
}
