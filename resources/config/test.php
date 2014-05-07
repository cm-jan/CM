<?php

$config->CM_Mail->send = false;

$config->CM_Elasticsearch_Client->enabled = false;

$config->CM_Db_Db->serversReadEnabled = false;
$config->CM_Db_Db->delayedEnabled = false;

$config->classConfigCacheEnabled = false;

$config->CM_Model_Splittest->withoutPersistence = true;

$config->CM_Model_Splitfeature->withoutPersistence = true;

$config->CM_Jobdistribution_Job_Abstract->gearmanEnabled = false;

$config->CM_ServiceManager->list = array_merge(
    $config->CM_ServiceManager->list,
    array(
        'MySql' => array(
            'class'     => 'CM_Db_Client',
            'arguments' => array(
                'localhost',
                3306,
                'root',
                '',
                'cm_test',
                300
            )
        ),
    )
);
