<?php
/**
 * Services definition.
 *
 * @package mod_solo
 * @author  Justin Hunt Poodll.com
 */

$functions = array(

        'assignsubmission_cloudpoodll_check_grammar' => array(
                'classname'   => '\assignsubmission_cloudpoodll\external',
                'methodname'  => 'check_grammar',
                'description' => 'check grammar',
                'capabilities'=> 'assignsubmission/cloudpoodll:use',
                'type'        => 'read',
                'ajax'        => true,
        )
);
