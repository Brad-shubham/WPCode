<?php

namespace Genpak\Plugins\Migration;


class DeleteAdministrators
{
    /**
     *
     */
    public function __invoke()
    {
        $this->deleteAll();;
    }

    /**
     *
     */
    public function deleteAll()
    {
        $users = get_users([
            'role' => 'administrator',
            'meta_key' => 'reference_id'
        ]);

        foreach ($users as $user) {
            wp_delete_user($user->ID);
            echo "Deleted Administrator " . $user->data->user_email . "\n";
        }
    }
}
