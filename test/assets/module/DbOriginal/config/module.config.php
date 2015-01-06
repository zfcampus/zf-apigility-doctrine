<?php

namespace ZFTestApigilityDb;

return array(
    'doctrine' => array(
        'driver' => array(
           'db_driver' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
                'paths' => array(__DIR__ . '/xml'),
            ),
            'orm_default' => array(
                'drivers' => array(
                    __NAMESPACE__ . '\Entity' => 'db_driver',
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'json_exceptions'          => array(
            'display'    => true,
            'ajax_only'  => true,
            'show_trace' => true
        ),

        'doctype'            => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map'       => array(),
        'strategies'         => array(
            'ViewJsonStrategy',
        ),
    ),
);
