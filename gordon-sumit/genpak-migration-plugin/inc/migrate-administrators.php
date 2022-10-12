<?php


namespace Genpak\Plugins\Migration;


class MigrateAdministrators extends BaseClass
{
    protected $capsule;

    /**
     *
     */
    public function __invoke()
    {
        $source_credentials = $this->getSourceCredentials();

        if (!$source_credentials) {
            echo "\e[0;31;40mSource not found, please configure the plugin settings.\e[0m\n";
            return false;
        }

        $this->capsule = $this->getConnection($source_credentials);

        echo "Administrators Migration Started.\n";

        $this->capsule->table('tbl_console_users')
            ->orderBy('user_id', 'asc')
            ->chunk(10, function ($admins) {
                foreach ($admins as $admin) {
                    $this->migrate($admin);
                }
            });

        echo "Administrators Migration Completed.\n";
    }

    /**
     * @param $admin
     * @return bool
     */
    public function migrate($admin)
    {
        if (email_exists($admin->user_email)) {
            echo "\e[0;31;40mAdministrator " . $admin->user_email . " already exists.\e[0m\n";
            return false;
        }

        $username = $admin->user_name;
        $password = $admin->user_password;
        $email = $admin->user_email;
        $full_name = explode(' ', $username);
        $first_name = $last_name = '';

        if (!$username) {
            $username = explode('@', $email);
        }

        if ($full_name && $full_name[0]) {
            $first_name = $full_name[0];
        }
        if ($full_name && $full_name[1]) {
            $last_name = $full_name[1];
        }

        $admin_data = [
            'user_pass' => $password,
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => $username,
            'nick_name' => $first_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'user_registered' => $admin->user_signup,
            'role' => 'administrator',
        ];

        $user_id = wp_insert_user($admin_data);

        update_user_meta($user_id, 'reference_id', $admin->user_id);
    }
}
