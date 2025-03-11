<?php
require '../vendor/autoload.php';

header('Content-Type: application/json');

require_once 'result_generator.php';

try {
    // Validate required fields
    if (empty($_POST['event_name']) || 
        empty($_POST['class']) || empty($_POST['participant']) || 
        !is_array($_POST['class']) || !is_array($_POST['participant'])) {
        throw new Exception('Missing or invalid required fields');
    }

    $eventName = $_POST['event_name'];
    $classes = $_POST['class'];
    $participants = $_POST['participant'];

    // Validate arrays have same length
    if (count($classes) !== count($participants)) {
        throw new Exception('Invalid form data');
    }

    // Load event data
    $storagePath = is_dir('/events') ? '/events' : '../data';
    $participantsFile = "$storagePath/$eventName.json";
    if (!file_exists($participantsFile)) {
        throw new Exception('Event not found');
    }

    $eventData = json_decode(file_get_contents($participantsFile), true);
    if (!$eventData) {
        throw new Exception('Invalid event data');
    }

    // Check if registration period has ended
    $lastDate = new DateTime($eventData['lastDate']);
    $currentDate = new DateTime();
    
    if ($currentDate > $lastDate) {
        throw new Exception('Registration period has ended');
    }

    // Prepare registrations
    $registrations = [];
    for ($i = 0; $i < count($classes); $i++) {
        $className = trim($classes[$i]);
        $participantName = trim($participants[$i]);

        if (empty($className) || empty($participantName)) {
            continue;
        }

        $registrations[] = [
            'class' => $className,
            'participant' => $participantName
        ];
    }

    // Validate class exists and counts
    foreach ($registrations as $reg) {
        if (!isset($eventData['classes'][$reg['class']])) {
            throw new Exception("Class {$reg['class']} does not exist");
        }
        
        // Check if class already has results
        if (isset($eventData['results'][$reg['class']])) {
            throw new Exception("Class {$reg['class']} is already completed and has results");
        }
    }

    // Count and validate
    $classParticipantCount = [];
    $participantClassCount = [];
    $participantsInClass = [];

    // Check new registrations for duplicate participants in same class
    $newParticipantsInClass = [];
    foreach ($registrations as $reg) {
        if (!isset($newParticipantsInClass[$reg['class']])) {
            $newParticipantsInClass[$reg['class']] = [];
        }
        
        if (in_array(strtolower($reg['participant']), array_map('strtolower', $newParticipantsInClass[$reg['class']]))) {
            throw new Exception("Participant {$reg['participant']} is already being registered in class {$reg['class']}");
        }
        
        $newParticipantsInClass[$reg['class']][] = $reg['participant'];
    }

    // Count existing entries and check for duplicates
    foreach ($eventData['classes'] as $className => $entries) {
        $classParticipantCount[$className] = count($entries);
        $participantsInClass[$className] = [];
        
        foreach ($entries as $entry) {
            $participantClassCount[$entry['participant']] = 
                ($participantClassCount[$entry['participant']] ?? 0) + 1;
            $participantsInClass[$className][] = strtolower($entry['participant']);
        }
    }

    // Validate new registrations
    foreach ($registrations as $reg) {
        // Check if participant is already in the class
        if (in_array(strtolower($reg['participant']), $participantsInClass[$reg['class']] ?? [])) {
            throw new Exception("Participant {$reg['participant']} is already registered in class {$reg['class']}");
        }

        // Check class capacity
        $classParticipantCount[$reg['class']] = 
            ($classParticipantCount[$reg['class']] ?? 0) + 1;
        if ($classParticipantCount[$reg['class']] > $eventData['rules']['maxHorsesPerClass']) {
            throw new Exception("Class {$reg['class']} would exceed maximum capacity");
        }

        // Check classes per participant
        $participantClassCount[$reg['participant']] = 
            ($participantClassCount[$reg['participant']] ?? 0) + 1;
        if ($participantClassCount[$reg['participant']] > $eventData['rules']['maxClassesPerHorse']) {
            throw new Exception(
                "Participant {$reg['participant']} would exceed maximum classes"
            );
        }
    }

    // All validations passed, add registrations
    foreach ($registrations as $reg) {
        $eventData['classes'][$reg['class']][] = [
            'participant' => $reg['participant']
        ];
    }

    // Initialize result generator and check for results generation
    $resultGenerator = new ResultGenerator($eventData);
    $resultsGenerated = $resultGenerator->checkAndGenerateResults();
    $eventData = $resultGenerator->getEventData();

    // Save updated data
    if (!file_put_contents($participantsFile, 
        json_encode($eventData, JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to save registration data');
    }

    echo json_encode([
        'success' => 'Registration successful',
        'resultsGenerated' => $resultsGenerated
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

