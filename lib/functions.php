<?php
# Haters gonna hate.
function current_user()
{
    return User::current();
}

function CONFIG()
{
    return Rails::application()->booruConfig();
}

# filemtime() won't precisely return the time according
# to local settings.
function filemodtime($filePath)
{
    $time = filemtime($filePath);
    $isDST = (date('I', $time) == 1);
    $systemDST = (date('I') == 1);
    $adjustment = 0;

    if ($isDST == false && $systemDST == true)
        $adjustment = 3600;
    elseif ($isDST == true && $systemDST == false)
        $adjustment = -3600;
    else
        $adjustment = 0;

    return ($time + $adjustment);
}