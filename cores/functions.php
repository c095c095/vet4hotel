<?php

function base_url($path = '')
{
    return SITE_URL . '/' . ltrim($path, '/');
}

function assets($path = '')
{
    return base_url('assets/' . ltrim($path, '/'));
}

function redirect($page)
{
    echo "<script> window.location.href = '?page=" . $page . "'; </script> ";
    exit();
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}
