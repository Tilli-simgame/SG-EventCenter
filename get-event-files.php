<?php
// Set the appropriate content type for JSON response
header('Content-Type: application/json');

// Path to the data directory
$storagePath = is_dir('/events') ? '/events' : 'data/';
$dataDir = $storagePath;

// Get all JSON files from the data directory
$files = glob($dataDir . '*.json');

// Format the filenames (remove the directory path)
$eventFiles = array_map(function($file) use ($dataDir) {
    return str_replace($dataDir, '', $file);
}, $files);

// Return the list as JSON
echo json_encode([
    'files' => $eventFiles
]);
