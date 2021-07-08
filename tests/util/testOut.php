<?php

function testOut($msg) {

    if(is_array($msg)) {
        $msg = print_r($msg,true);
    }

    fwrite(STDERR,$msg."\n");
}