<?php

function route_handler_a() {
    echo 'some data';
}

function route_handler_b($data) {
    echo $data  . ' was received';
}

function route_handler_c($data1, $data2, $data3) {
    echo "$data1 , $data2 and $data3 were received";
}

function route_handler_d($data = 'nothing was received') {
    echo $data;
}

function custom_middleware() {
    echo "Middleware function.";
}
