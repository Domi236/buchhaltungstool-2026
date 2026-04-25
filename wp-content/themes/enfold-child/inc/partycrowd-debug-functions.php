<?php

/**
 * Funktionen zum Debuggen und für Error Reporting
 **/



add_action('avia_builder_mode', 'builder_set_debug');
function builder_set_debug()
{
    return 'debug';
}
