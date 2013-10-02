<?php
function vd() {
    $vars = func_get_args();
    call_user_func_array('var_dump', $vars);
}

function vp() {
    echo '<pre>';
    $vars = func_get_args();
    call_user_func_array('var_dump', $vars);
    echo '</pre>';
}

function vde() {
    $vars = func_get_args();
    call_user_func_array('var_dump', $vars);
    exit;
}

function vpe() {
    echo '<pre>';
    call_user_func_array('vd', func_get_args());
    echo '</pre>';
    exit;
}