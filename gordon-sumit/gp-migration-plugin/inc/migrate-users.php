<?php

namespace Genpak\Plugins\Migration;


class MigrateUsers extends BaseClass
{
    protected $capsule;

    const EMAILS = [
        'test@test.com'
    ];

    public function __invoke()
    {
        $source_credentials = $this->getSourceCredentials();

        if (!$source_credentials) {
            echo "\e[0;31;40mSource not found, please configure the plugin settings.\e[0m\n";
            return false;
        }

        $this->capsule = $this->getConnection($source_credentials);

        echo "Users Migration Started.\n";

        $this->capsule->table('Users')
            ->orderBy('UserID', 'desc')
            ->whereIn('Email', self::EMAILS)
            ->chunk(10, function ($users) {
                foreach ($users as $user) {
                    $this->migrate($user);
                }
            });

        echo "Users Migration Completed.\n";
    }

    /**
     * @param $user
     * @return bool
     */
    public function migrate($user)
    {
        if (email_exists($user->Email)) {
            echo "\e[0;31;40mUser " . $user->Email . " already exists.\e[0m\n";
            return false;
        }

        $username = $user->UserName;
        $password = $user->Password;
        $email = $user->Email;
        $full_name = $user->FullName;
        $first_name = $last_name = null;


        if ($full_name && explode(' ', $full_name)[0]) {
            $first_name = explode(' ', $full_name)[0];
        }
        if ($full_name && explode(' ', $full_name)[1]) {
            $last_name = explode(' ', $full_name)[1];
        }

        $user_data = [
            'user_pass' => $password,
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => $username,
            'nick_name' => $first_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'customer',
        ];

        $user_id = wp_insert_user($user_data);

        update_user_meta($user_id, 'reference_id', $user->UserID);

    }

}
