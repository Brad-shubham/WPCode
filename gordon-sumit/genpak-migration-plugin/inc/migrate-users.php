<?php

namespace Genpak\Plugins\Migration;


class MigrateUsers extends BaseClass
{
    protected $capsule;

    const EMAILS = [
        'guest@guest.com', 'dev.genpak@mannixmarketing.com', 'kcallahan@genpak.com', 'tcaruso@genpak.com',
        'sdavis@genpak.com', 'kkelly@genpak.com', 'alevit@genpak.com', 'coblak@genpak.com',
        'dpate@genpak.com', 'seanp@genpak.com', 'sseagraves@genpak.com', ' dsilaus@genpak.com',
        ' ksmith@genpak.com', 'jtappan@genpak.com', 'klenhardt@genpak.com', 'rsherrod@genpak.com',
        'lcarpanini@genpak.com', 'dev.genpak@mannixmarketing.com', 'rbombard@genpak.com', 'jveater@genpak.com',
        'cfranke@genpak.com', 'psmithey@genpak.com', 'rsnyder@genpak.com', 'cbeaver@genpak.com',
        'ssasser@genpak.com', 'dpalmer@genpak.com', 'aszabo@genpak.com', 'swilson@genpak.com',
        'areyes@genpak.com', 'brezner@genpak.com', 'mpettit@genpak.com', 'jmays@genpak.com',
        'dmcnally@genpak.com', 'brichards@genpak.com', 'nbeaudette@genpak.com', 'dev.genpak@mannixmarketing.com',
        'bbrown@genpak.com', 'kconnor@genpak.com', 'grider@genpak.com', 'raustin@genpak.com',
        'jcross@genpak.com', 'tomroberts@genpak.com', 'jmcintyre@genpak.com', 'jcrosby@genpak.com',
        'apattenden@genpak.com', 'sstorz@genpak.com', 'bhenzie@genpak.com', 'jperrault@genpak.com',
        'npacheco@genpak.com', 'BMay@genpak.com', 'BLogan@genpak.com', 'SDeol@genpak.com',

        'llabell@genpak.com', 'jcunningham@genpak.com', 'atonery@genpak.com', 'araibagi@genpak.com',
        'bbrinkerhoff@genpak.com', 'cjenson@genpak.com', 'bquinn@genpak.com', 'bross@genpak.com',
        'cmurphy@genpak.com', 'ballers@genpak.com', 'jbaker@genpak.com', 'mschell@genpak.com',
        'dthompson@genpak.com', 'dev.genpak@mannixmarketing.com', 'dev.genpak@mannixmarketing.com', 'arunnalls@genpak.com',
        'dev.genpak@mannixmarketing.com', 'dev.genpak@mannixmarketing.com', 'mmontgomery@genpak.com', 'pmahle@genpak.com',
        'lkear@genpak.com', 'rhinton@genpak.com', 'cstidham@genpak.com', 'rpeacock@genpak.com',
        'chollan@genpak.com', 'ChrisL@whiteandhodge.com', 'Stevew@whiteandhodge.com', 'Richg@eklay.com',
        'reginao@eklay.com', 'pobrien@eklay.biz', 'ktremblay@kisales.com', 'kinneyb@whiteandhodge.com',
        'chrism@whiteandhodge.com', 'mmerrimac@kisales.com', 'sfranks@kisales.com', 'jeff.nelson@kisales.com',
        'ccase@kisales.com', 'kathy.mcdonald@kisales.com', 'lzubroski@kisales.com', 'dev.genpak@mannixmarketing.com',
        'billb@eklay.com', 'barryl@whiteandhodge.com', 'cmckeighan@genpak.com', 'doterelo@ajksales.com',
        'kmcauliffe@ajksales.com', 'dbutts@ajksales.com', 'agimilaro@ajksales.com', 'dmcualiffe@ajksales.com',
        'dmcauliffe@ajksales.com', 'tcollins@ajksales.com', 'jbifulco@ajksales.com', 'lisah@whiteandhodge.com',
        'keithw@whiteandhodge.com', 'bbarth@djpayne.com', 'glennb@whiteandhodge.com', 'ejensen@advance-sales.net',
        'rhansen@advance-sales.net', 'cjg@eklay.com', 'Robyn@creativesalesnj.com', 'fdefazio@creativesalesnj.com',
        'jkovach@genpak.com', 'miap@whiteandhodge.com', 'dorisc@whiteandhodge.com', 'margieg@eklay.com',
        'jamie@mannixmarketing.com', 'johnb@whiteandhodge.com', 'dslaughter@genpak.com', 'prorie@genpak.com',
        'katiej@whiteandhodge.com', 'pierre.brantome@safeway.com', 'glenns@whiteandhodge.com', 'kd@genpak.com',
        'seanp@genpak.com', 'cheryll@whiteandhodge.com', 'morgan@morganmktg.com', 'lisa@morganmktg.com',
        'MPECINO@GENPAK.COM', 'JasonG@whiteandhodge.com', 'aegan@genpak.com', 'premier@premiermidwest.com',
        'keith@premiermidwest.com', 'chaase@djpayne.com', 'akleinman@genpak.com', 'lmorin@genpak.com',
        'jzak@genpak.com', 'ebowers@pamsinc.com', 'dcampbell@genpak.com', 'billg@eklay.com',
        'seanh@eklay.com', 'dtestin@eklay.biz', 'mlykins@genpak.com', 'tcrown@tcm-sales.com',
        'bpatskou@genpak.com', 'kspence@genpak.com', 'tricia@petersonusa.com', 'test@test.com',
        'cliles@genpak.com', 'ccason@genpak.com', 'stever@whiteandhodge.com', 'mbaughman@genpak.com',
        'IHevenor@genpak.com', 'mklakulak@genpak.com', 'jblaha@genpak.com', 'azarate@genpak.com',
        'jamie@mannixmarketing.com', 'CGuion@genpak.com', 'mepena@tcm-sales.com', 'MorganB@whiteandhodge.com',
        'chaley@genpak.com', 'michele@eklay.com', 'maltimari@genpak.com', 'LBernola@genpak.com',
        'btroxell@ajksales.com', 'agershan@genpak.com', 'neelyb@whiteandhodge.com', 'lmazza@kisales.com',
        'mfrinzi@genpak.com', 'cking@kisales.com', 'tsmith@kisales.com', 'ngabel@kisales.com',
        'mcorrea@kisales.com'
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
