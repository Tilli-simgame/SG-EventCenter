<?php
// Set the appropriate content type for JSON response
header('Content-Type: application/json');

// Path to the data directory
$storagePath = is_dir('/events') ? '/events' : 'data/';
$dataDir = $storagePath;

// Log the directory being used
error_log("Using data directory: " . $dataDir);

// Get all JSON files from the data directory
$files = glob($dataDir . '*.json');

// Log the files found
error_log("Found files: " . print_r($files, true));

// Format the filenames (remove the directory path)
$eventFiles = array_map(function($file) use ($dataDir) {
    return str_replace($dataDir, '', $file);
}, $files);

// Log the formatted file names
error_log("Formatted file names: " . print_r($eventFiles, true));

// Return the list as JSON with additional debug info
$response = [
    'files' => $eventFiles,
    'debug' => [
        'dataDir' => $dataDir,
        'rawFiles' => $files,
        'serverPath' => $_SERVER['DOCUMENT_ROOT'],
        'scriptPath' => __DIR__
    ]
];

echo json_encode($response);

