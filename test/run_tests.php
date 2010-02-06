<?php

/**
* Sintags tests do not require any unit testing framework.
* 
* To run these tests just call:
*
*     php run_tests.php
*
* from the command line.
*/

defined('TESTING_BASE_DIR') || define('TESTING_BASE_DIR', dirname(__FILE__));

include dirname(__FILE__).'/../src/autoload.php';

define('AK_SINTAGS_AVALABLE_HELPERS', 'a:9:{s:7:"url_for";s:10:"url_helper";s:7:"link_to";s:10:"url_helper";s:7:"mail_to";s:10:"url_helper";s:10:"email_link";s:10:"url_helper";s:9:"translate";s:11:"text_helper";s:20:"number_to_human_size";s:13:"number_helper";s:6:"render";s:10:"controller";s:25:"distance_of_time_in_words";s:11:"date_helper";s:1:"h";s:11:"text_helper";}');
new AkSintags();

$test_results = array();

function _run_from_file($file_name, $all_in_one_test = true) {
    global $test_results;
    $multiple_expected_php = $multiple_sintags = '';
    $tests = explode('===================================', file_get_contents(TESTING_BASE_DIR.DS.'fixtures'.DS.$file_name));
    foreach ($tests as $test) {
        list($sintags, $php) = explode('-----------------------------------',$test);
        $sintags = trim($sintags);
        $expected_php = trim($php);
        if(empty($sintags)){
            return;
        }else{
            $multiple_sintags .= $sintags;
            $multiple_expected_php .= $expected_php;
        }
        $AkSintags = new AkSintagsParser();
        $php = $AkSintags->parse($sintags);


        if($php != $expected_php){
            $test_results['errors'][] = " FAILED!\n".
            "\n-------\nWith Sintags: \n".$sintags."\n".
            "\n-------\ngenerated: \n".$php."\n".
            "\n-------\nwhile expected: \n".$expected_php."\n-------------\n";
        }else{
            $test_results['success'][] = $sintags;
        }

    }

    if($all_in_one_test){
        $AkSintags = new AkSintagsParser();
        $php = $AkSintags->parse($multiple_sintags);
        if($php != $multiple_expected_php){
            $test_results['errors'][] = " FAILED!\n".
            "\n-------\nWith Sintags: \n".$multiple_sintags."\n".
            "\n-------\ngenerated: \n".$php."\n".
            "\n-------\nwhile expected: \n".$multiple_expected_php."\n-------------\n";
        }else{
            $test_results['success'][] = $sintags;
        }
    }
}


_run_from_file('sintags_blocks_data.txt');
_run_from_file('sintags_test_data.txt');

AkRouterHelper::generateHelperFunctionsFor('named_route', new AkRoute('/'));
        
_run_from_file('sintags_helpers_data.txt');

echo "\nRunning Sintags tests\n";
echo count((array)@$test_results['success'])." tests passed\n";
if(!empty($test_results['errors'])){
    echo join("\n", $test_results['errors']);
    echo count($test_results['errors'])." failure/s!!!";
}
echo "\nCompleted running tests.\n";
