<?php

function sqldate2friendly($sqldate)
{
    //return strftime("%A, %B %e", strtotime($sqldate));
    return strftime("%a, %b %e, %Y", strtotime($sqldate));
    //return strftime("%m/%d/%Y", strtotime($sqldate));
}
