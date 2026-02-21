<?php
/**
 * Services definition.
 *
 * @package mod_solo
 * @author  Justin Hunt Poodll.com
 */

$functions = array(

        'assignsubmission_cloudpoodll_check_grammar' => array(
                'classname' => '\assignsubmission_cloudpoodll\external',
                'methodname' => 'check_grammar',
                'description' => 'check grammar',
                'capabilities' => 'assignsubmission/cloudpoodll:use',
                'type' => 'read',
                'ajax' => true,
        ),
        'assignsubmission_cloudpoodll_upload_whiteboard_image' => array(
                'classname' => '\assignsubmission_cloudpoodll\external',
                'methodname' => 'upload_whiteboard_image',
                'description' => 'upload whiteboard image',
                'capabilities' => 'assignsubmission/cloudpoodll:use',
                'type' => 'write',
                'ajax' => true,
        )
);