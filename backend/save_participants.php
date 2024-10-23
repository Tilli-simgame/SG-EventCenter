<?php
// Get the event name dynamically from the POST request
$eventName = $_POST['event_name'];

// Validate the event name to avoid security risks (injection)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $eventName)) {
    echo json_encode(['error' => 'Invalid event name']);
    exit;
}

// Define the JSON file path based on the event name
$participantsFile = "../data/$eventName.json";

// Read existing participants data
if (!file_exists($participantsFile)) {
    echo json_encode(['error' => 'Event not found']);
    exit;
} 

$participantsData = json_decode(file_get_contents($participantsFile), true);

// Extract the rules from the JSON file
$rules = $participantsData['rules'];

// Get POST data
$riderName = $_POST['rider_name'];
$horses = $_POST['horse'];
$classes = $_POST['class'];

// Check rule: Max horses per class
foreach ($classes as $index => $className) {
    $horseName = $horses[$index];

    // Ensure the class exists in the event data
    if (!isset($participantsData['classes'][$className])) {
        echo json_encode(['error' => "Class $className does not exist."]);
        exit;
    }

    // Rule: Check if the class is full
    if (count($participantsData['classes'][$className]) >= $rules['maxHorsesPerClass']) {
        echo json_encode(['error' => "Class $className is full (max {$rules['maxHorsesPerClass']} horses)."]);
        exit;
    }

    // Rule: Check if the horse is already registered in too many classes
    $horseEntries = 0;
    foreach ($participantsData['classes'] as $classParticipants) {
        foreach ($classParticipants as $participant) {
            if ($participant['horse'] === $horseName) {
                $horseEntries++;
            }
        }
    }
    if ($horseEntries >= $rules['maxClassesPerHorse']) {
        echo json_encode(['error' => "Horse $horseName has already entered in {$rules['maxClassesPerHorse']} classes."]);
        exit;
    }

    // Rule: Check if the rider already has too many horses in this class
    $riderEntriesInClass = 0;
    foreach ($participantsData['classes'][$className] as $participant) {
        if ($participant['rider'] === $riderName) {
            $riderEntriesInClass++;
        }
    }
    if ($riderEntriesInClass >= $rules['maxHorsesPerRiderInClass']) {
        echo json_encode(['error' => "Rider $riderName already has {$rules['maxHorsesPerRiderInClass']} horses in class $className."]);
        exit;
    }

    // Add the participant to the class
    $participantsData['classes'][$className][] = [
        'rider' => $riderName,
        'horse' => $horseName
    ];
}

// Save the updated data back to the event-specific JSON file
file_put_contents($participantsFile, json_encode($participantsData, JSON_PRETTY_PRINT));

echo json_encode(['success' => 'Successfully registered!']);
?>
