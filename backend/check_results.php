<?php
header('Content-Type: application/json');

require_once 'result_generator.php';

$storagePath = is_dir('/events') ? '/events' : '../data';

try {
    // Get event name from request
    if (empty($_GET['event'])) {
        throw new Exception('No event specified');
    }
    
    $eventName = $_GET['event'];
    $participantsFile = "$storagePath/$eventName.json";
    
    if (!file_exists($participantsFile)) {
        throw new Exception('Event not found');
    }
    
    $eventData = json_decode(file_get_contents($participantsFile), true);
    if (!$eventData) {
        throw new Exception('Invalid event data');
    }
    
    // Initialize result generator and check for results generation
    $resultGenerator = new ResultGenerator($eventData);
    $resultsGenerated = $resultGenerator->checkAndGenerateResults();
    
    if ($resultsGenerated) {
        // Save updated data if results were generated
        $updatedEventData = $resultGenerator->getEventData();
        if (!file_put_contents($participantsFile, json_encode($updatedEventData, JSON_PRETTY_PRINT))) {
            throw new Exception('Failed to save results');
        }
    }
    
    echo json_encode([
        'success' => true,
        'resultsGenerated' => $resultsGenerated,
        'eventData' => $resultGenerator->getEventData()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>