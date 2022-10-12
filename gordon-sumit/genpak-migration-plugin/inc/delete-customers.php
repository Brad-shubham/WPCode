<?php


namespace Genpak\Plugins\Migration;


class DeleteCustomers
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
            'role' => 'customer',
            'meta_key' => 'reference_id'
        ]);

        foreach ($users as $user) {
            wp_delete_user($user->ID);
            echo "Deleted Customer " . $user->data->user_email . "\n";
        }
    }
}
