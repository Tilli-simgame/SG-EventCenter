<?php
// Get the event name dynamically from the POST request
$eventName = $_POST['event_name'];
$riderName = $_POST['rider_name'];
$email = $_POST['email'];  // Email field if you have it in the form
$horses = $_POST['horse'];  // Array of horses
$classes = $_POST['class'];  // Array of corresponding classes

// Honeypot bot protection
if (!empty($_POST['honeypot'])) {
    echo json_encode(['error' => 'Bot detected!']);
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
$rules = $participantsData['rules'];

// Process the form and apply the rules (same as before)
foreach ($classes as $index => $className) {
    $horseName = trim($horses[$index]);

    // Ensure the class exists in the event data
    if (!isset($participantsData['classes'][$className])) {
        echo json_encode(['error' => "Class $className does not exist."]);
        exit;
    }

    // Check if the horse name is not empty (skip empty fields)
    if (empty($horseName)) {
        continue;  // Skip empty horse name inputs
    }

    // Rule: Check if the class is full
    if (count($participantsData['classes'][$className]) >= $rules['maxHorsesPerClass']) {
        echo json_encode(['error' => "Class $className is full."]);
        exit;
    }

    // Add the participant to the class
    $participantsData['classes'][$className][] = [
        'rider' => $riderName,
        'horse' => $horseName
    ];

    // Check if the class is now full after adding the participant
    if (count($participantsData['classes'][$className]) == $rules['maxHorsesPerClass']) {
        // Class is full, generate results
        $participants = $participantsData['classes'][$className];
        
        // Randomize the results
        shuffle($participants);
        
        // Assign places (e.g., 1st, 2nd, 3rd, etc.)
        $results = [];
        foreach ($participants as $place => $participant) {
            $results[] = [
                'place' => $place + 1,
                'rider' => $participant['rider'],
                'horse' => $participant['horse']
            ];
        }
        
        // Save the results in the JSON file under "results"
        if (!isset($participantsData['results'])) {
            $participantsData['results'] = [];
        }
        
        $participantsData['results'][$className] = $results;
    }
}

// Save the updated data back to the JSON file
file_put_contents($participantsFile, json_encode($participantsData, JSON_PRETTY_PRINT));

echo json_encode(['success' => 'Successfully registered and results generated if the class is full!']);
?>
