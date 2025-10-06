<?php
/**
 * Group Detail Router
 * Handles URLs like /group/group-slug
 */

// Get the slug from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/group/';

// Extract slug from URL
$slug = '';
if (strpos($requestUri, $basePath) === 0) {
    $slug = substr($requestUri, strlen($basePath));
    // Remove query string if present
    if (($pos = strpos($slug, '?')) !== false) {
        $slug = substr($slug, 0, $pos);
    }
    // Remove trailing slash
    $slug = rtrim($slug, '/');
}

// Set the slug in GET for the group detail page
$_GET['slug'] = $slug;

// Include the group detail page
require_once 'index.php';
?>