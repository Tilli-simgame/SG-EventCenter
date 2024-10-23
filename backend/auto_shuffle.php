<?php
include 'shuffle_results.php';  // Include the shuffle function

$eventName = $_POST['event_name'];  // Get the event name from the request
$participantsFile = "../data/$eventName.json";

// Read the event data
if (!file_exists($participantsFile)) {
    echo json_encode(['error' => 'Event not found.']);
    exit;
}

$participantsData = json_decode(file_get_contents($participantsFile), true);
$currentDate = new DateTime();
$lastDate = DateTime::createFromFormat('Ymd', $participantsData['lastDate']);

// Check if the last date has passed
if ($currentDate > $lastDate) {
    $shuffledClasses = [];  // To track which classes were shuffled

    // Shuffle results for classes that don't already have results
    foreach ($participantsData['classes'] as $className => $participants) {
        // Check if class is empty or already has results
        if (count($participants) === 0) {
            continue;  // Skip empty classes
        }

        if (!isset($participantsData['results'][$className])) {
            // Shuffle and save results for this class
            $participantsData['results'][$className] = shuffleResults($participants);
            $shuffledClasses[] = $className;  // Track shuffled classes
        }
    }

    // Save the updated event data
    if (!empty($shuffledClasses)) {
        file_put_contents($participantsFile, json_encode($participantsData, JSON_PRETTY_PRINT));
        echo json_encode(['success' => 'Results auto-shuffled and saved.', 'shuffled_classes' => $shuffledClasses]);
    } else {
        echo json_encode(['success' => 'No classes needed shuffling.']);
    }
} else {
    echo json_encode(['error' => 'Last date has not passed yet.']);
}
?>
