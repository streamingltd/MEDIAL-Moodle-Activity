 <?php


/**
 * This file contains a library of functions and constants for the helixmedia module
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$tasks = [
    [
        'classname' => 'mod_helixmedia\task\cleanup',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
