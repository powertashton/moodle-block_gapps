<?php
/**
 * gsync View and actions
 *
 * @author Chris Stones
 * @version $Id$
 * @package blocks/gapps
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

class block_gapps_controller_gsync extends mr_controller_block {
    /**
     * Default screen
     *
     * Demo of plugins
     */
    public function view_action() {
        // $this->set_headerparams('tab', 'status','title','Status');

        $this->print_header();

        echo $this->output->heading('Demo of gsync controller');
       
        echo "gsync test";
        
        echo $this->output->box_start('generalbox boxaligncenter boxwidthnormal');

        echo $this->output->box_end();
        $this->print_footer();
    }

    public function status_action() {
        global $CFG,$OUTPUT;
        
        $this->tabs->set('status');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');


        try {
            $gapps = new blocks_gapps_model_gsync();
            print $OUTPUT->notification(get_string('connectionsuccess','block_gapps'),'notifysuccess');
        } catch (blocks_gdata_exception $e) {
            $a = NULL;
            $a->msg = $e->getMessage();
            print $OUTPUT->notification(get_string('gappsconnectiontestfailed','block_gapps',$a));
        }
        
        // Output Details on the Status Connection
        // useful for debugging
       
        $this->print_footer();

    }

    public function usersview_action() {
        global $COURSE, $CFG;

        $this->tabs->set('users');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/report/users.php');
        require_once($CFG->dirroot.'/user/filters/lib.php');

        // don't auto run because moodle's userfilter clears the _POST global and we need to save
        // and process those
        $report = new blocks_gapps_report_users($this->url, $COURSE->id,false);
             

        $filter =  new user_filtering(NULL, $this->url);//,
                                    //  array('hook' => $hook, 'pagesize' => $pagesize));

        mr_var::instance()->set('blocks_gdata_filter', $filter);
        $report->run();
        $output = $this->mroutput->render($report);
        print $output;
        
        $this->print_footer();
    }

    public function users_action() {
        global $CFG,$SESSION,$DB;
        $this->tabs->set('users');
        $operationstatus = true;
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        //print_object($_POST);


        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gapps_model_gsync(false);

            if (optional_param('allusers', '', PARAM_RAW)) {
 //               $this->notify->bad('notimplementedyet','block_gapps');
//                $operationstatus = false;
//                list($select, $from, $where) = $this->get_sql('users');

                // Obtain sql from the stored filter
                if (isset($SESSION->blocks_gapps_report_users->fsql)) {
                    $fsql = $SESSION->blocks_gapps_report_users->fsql;
                    $fparams = $SESSION->blocks_gapps_report_users->fparams;
                } else {
                    throw new blocks_gdata_exception('missingfiltersql');
                }

                // Bulk processing
                if ($rs = $DB->get_recordset_sql($fsql,$fparams)) {
                    while ($rs->valid()) {
                        $user = $rs->current();
                        $gapps->moodle_remove_user($user->id);
                        $rs->next();
                    }
                    $rs->close();
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }
//                // Bulk processing
//                if ($rs = get_recordset_sql("$select $from $where")) {
//                    while ($user = rs_fetch_next_record($rs)) {
//                        $gapps->moodle_remove_user($user->id);
//                    }
//                    rs_close($rs);
//                } else {
//                    throw new blocks_gdata_exception('invalidparameter');
//                }


            } else {
                // Handle ID submit
                foreach ($userids as $userid) {
                    $gapps->moodle_remove_user($userid);
                }
            }
          //  redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=users');
        }

        $operationstatus and $this->notify->good('changessaved','block_gapps');
        $actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=usersview';//.$COURSE->id;
        redirect($actionurl);



        // CODE TO CONVERT
        /*
        require_once($CFG->dirroot.'/blocks/gdata/gapps.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gdata_gapps(false);

            if (optional_param('allusers', '', PARAM_RAW)) {
                list($select, $from, $where) = $this->get_sql('users');

                // Bulk processing
                if ($rs = get_recordset_sql("$select $from $where")) {
                    while ($user = rs_fetch_next_record($rs)) {
                        $gapps->moodle_remove_user($user->id);
                    }
                    rs_close($rs);
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }
            } else {
                // Handle ID submit
                foreach ($userids as $userid) {
                    $gapps->moodle_remove_user($userid);
                }
            }
            redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=users');
        }
        */

    }

    public function addusers_action() {
        global $CFG,$COURSE,$DB,$SESSION;
        $this->tabs->set('addusers');
        $operationstatus = true;

        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }

            $gapps = new blocks_gapps_model_gsync(false);


            if (optional_param('allusers', '', PARAM_RAW)) { 
                //// process ALL usersides on that page
                //$this->notify->bad('notimplementedyet','block_gapps');
                //$operationstatus = false;

                // Obtain sql from the stored filter
                if (isset($SESSION->blocks_gapps_report_addusers->fsql)) {
                    $fsql = $SESSION->blocks_gapps_report_addusers->fsql;
                    $fparams = $SESSION->blocks_gapps_report_addusers->fparams;
                } else {
                    throw new blocks_gdata_exception('missingfiltersql');
                }
                
                // Bulk processing
                if ($rs = $DB->get_recordset_sql($fsql,$fparams)) {
                    while ($rs->valid()) {
                        $user = $rs->current();
                        $user = $DB->get_record('user',array('id'=>$user->id));
                        $gapps->moodle_create_user($user);
                        $rs->next();
                    }
                    $rs->close();
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }



                //throw new blocks_gdata_exception('notimplementedyet');
//                list($select, $from, $where) = $this->get_sql('addusers'); // need to CONVERT
//
//                // Bulk processing
//                if ($rs = get_recordset_sql("$select $from $where")) {
//                    while ($user = rs_fetch_next_record($rs)) {
//                        $gapps->moodle_create_user($user);
//                    }
//                    rs_close($rs);
//                } else {
//                    throw new blocks_gdata_exception('invalidparameter');
//                }


            } else { 
                // Process selected user IDs
                foreach ($userids as $userid) {
                    // return a user object with only id,username and password
                    if ($user = $DB->get_record('user', array('id'=> $userid), 'id, username, password')) {
                        $gapps->moodle_create_user($user);
                    } else {
                        throw new blocks_gdata_exception('invalidparameter');
                    }
                }
        }
        }


        $operationstatus and $this->notify->good('changessaved','block_gapps');
        $actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=addusersview';//.$COURSE->id;
        redirect($actionurl);

        
        //// CODE to CONVERT
        /*
          global $CFG;

        require_once($CFG->dirroot.'/blocks/gdata/gapps.php');

        if ($userids = optional_param('userids', 0, PARAM_INT) or optional_param('allusers', '', PARAM_RAW)) {
            if (!confirm_sesskey()) {
                throw new blocks_gdata_exception('confirmsesskeybad', 'error');
            }
            $gapps = new blocks_gdata_gapps(false);

            if (optional_param('allusers', '', PARAM_RAW)) {
                list($select, $from, $where) = $this->get_sql('addusers');

                // Bulk processing
                if ($rs = get_recordset_sql("$select $from $where")) {
                    while ($user = rs_fetch_next_record($rs)) {
                        $gapps->moodle_create_user($user);
                    }
                    rs_close($rs);
                } else {
                    throw new blocks_gdata_exception('invalidparameter');
                }
            } else {
                // Process user IDs
                foreach ($userids as $userid) {
                    if ($user = get_record('user', 'id', $userid, '', '', '', '', 'id, username, password')) {
                        $gapps->moodle_create_user($user);
                    } else {
                        throw new blocks_gdata_exception('invalidparameter');
                    }
                }
            }
            redirect($CFG->wwwroot.'/blocks/gdata/index.php?hook=addusers');
        }
        */


        //$this->print_footer();
    }


    public function addusersview_action() {
        global $CFG,$COURSE;
        $this->tabs->set('addusers');
        $this->print_header();

        require_once($CFG->dirroot.'/blocks/gapps/report/addusers.php');
        require_once($CFG->dirroot.'/user/filters/lib.php');

        // don't auto run because moodle's userfilter clears the _POST global and we need to save
        // and process those
        $report = new blocks_gapps_report_addusers($this->url, $COURSE->id,false);

        $filter =  new user_filtering(NULL, $this->url);

        mr_var::instance()->set('blocks_gdata_filter', $filter);
        $report->run();
        $output = $this->mroutput->render($report);
        print $output;
        $this->print_footer();
    }
    

    /**
     * Testing Interface (to call model/diagnostic.php  you can bring up the dev docs in another tab
     */

    public function viewdiagnostics_action() {
        global $CFG,$COURSE,$OUTPUT;
        $this->tabs->set('diagnostic');
        $this->print_header();

        echo $this->output->heading('Heading Line of code');


        echo "Tables of data for current settings of all aspects<br/>";
        echo "status of systems";


        $this->print_footer();
    }


    public function runcron_action() {
        global $CFG,$DB;

        $this->tabs->set('diagnostic');
        $this->print_header();

        ob_start();
        // emulates the old cron.php test file
        // preps the moodle cron to accept the
        $nomoodlecookie = true; // cookie not needed

        require_once($CFG->libdir.'/blocklib.php');

        set_time_limit(0);

        $starttime = microtime();
        $timenow   = time();

        // the gapps cron needs to run
        if ($block = $DB->get_record_select("block", "cron > 0 AND ((? - lastcron) > cron) AND visible = 1 AND name = 'gapps'",array($timenow))) {
            //if (block_method_result('gdata', 'cron_alt')) {
                if (!$DB->set_field('block', 'lastcron', $timenow, array('id'=> $block->id))) {
                    mtrace('Error: could not update timestamp for '.$block->name);
                }
            //}
        } else {
            mtrace('Not time to run gapps gsync cron');
        }

        $difftime = microtime_diff($starttime, microtime());
        mtrace("Execution took ".$difftime." seconds");



        // now set up and run the gapps cron
        require_once($CFG->dirroot.'/blocks/gapps/model/gsync.php');
        $gapps = new blocks_gapps_model_gsync(false);
        $gapps->cron(true);


        $buffer = ob_get_flush();
        print_object($buffer);
        $this->print_footer();
        
        //$this->notify->good('notimplementedyet','block_gapps');
        
        //$actionurl = $CFG->wwwroot.'/blocks/gapps/view.php?controller=gsync&action=viewdiagnostics';
        //redirect($actionurl);
    }


    public function viewdocs_action() {
        global $CFG,$COURSE;
        $this->tabs->set('diagnostic');
        $this->print_header();

        echo $this->output->heading("Place to show phpdoc output (and proper links)");

        $this->print_footer();
    }



}