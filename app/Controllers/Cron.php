<?php

namespace App\Controllers;

use App\Libraries\Cron_job;

class Cron extends App_Controller {

    private $cron_job;

    function __construct() {
        parent::__construct();
        $this->cron_job = new Cron_job();
    }

    function index() {
        ini_set('max_execution_time', 300); //execute maximum 300 seconds 

        $last_cron_job_time = get_setting('last_cron_job_time');

        $minimum_cron_interval_seconds = get_setting('minimum_cron_interval_seconds');
        if (!$minimum_cron_interval_seconds) {
            $minimum_cron_interval_seconds = 300; //5 minutes
        }

        $current_time = strtotime(get_current_utc_time());

        if ($last_cron_job_time == "" || ($current_time > ($last_cron_job_time * 1 + $minimum_cron_interval_seconds))) {
            $this->cron_job->run();
            $this->runGedNotifications();
            app_hooks()->do_action("app_hook_after_cron_run");
            $this->Settings_model->save_setting("last_cron_job_time", $current_time);
            echo "Cron job executed.";
        } else {
            $start = new \DateTime(date("Y-m-d H:i:s", $last_cron_job_time * 1 + $minimum_cron_interval_seconds));
            $end = new \DateTime();
            $diff = $end->diff($start);
            $format = "%i minutes, %s seconds.";

            if ($diff->i <= 0) {
                $format = "%s seconds.";
            }
            echo "Please try after " . $end->diff($start)->format($format);
        }
    }

    private function runGedNotifications()
    {
        if (!class_exists('\GED\Libraries\GedNotificationService')) {
            return;
        }

        try {
            $service = new \GED\Libraries\GedNotificationService();
            $service->run(array('source' => 'cron'));
        } catch (\Throwable $e) {
            log_message('error', '[GED] Cron notification run error: ' . $e->getMessage());
        }
    }
}

/* End of file Cron.php */
/* Location: ./app/controllers/Cron.php */
