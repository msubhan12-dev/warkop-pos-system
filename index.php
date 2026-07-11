<?php
// WARKOP OS
// Redirect to customer menu as default landing page, preserving query string
$queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: customer/menu' . $queryString);
exit;
