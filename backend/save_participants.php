<?php
header('Content-Type: application/json');

class ResultGenerator {
  private $eventData;
  private $lastDate;
  
  public function __construct($eventData) {
      $this->eventData = $eventData;
      $this->lastDate = new DateTime($eventData['lastDate']);
  }
  
  public function checkAndGenerateResults() {
      $currentDate = new DateTime();
      $isRegistrationClosed = $currentDate > $this->lastDate;
      $updatedClasses = false;
      
      foreach ($this->eventData['classes'] as $className => $participants) {
          // Skip empty classes
          if (empty($participants)) {
              continue;
          }

          // Skip if results already exist for this class
          if (isset($this->eventData['results'][$className]) && 
              !empty($this->eventData['results'][$className])) {
              continue;
          }
          
          $isClassFull = count($participants) >= $this->eventData['rules']['maxHorsesPerClass'];
          
          // Generate results only if:
          // 1. Class is full OR registration period has ended
          // 2. Class doesn't already have results
          if (($isClassFull || $isRegistrationClosed)) {
              $this->generateResultsForClass($className);
              $updatedClasses = true;
          }
      }
      
      return $updatedClasses;
  }
  
  private function generateResultsForClass($className) {
      // Double-check that results don't exist before generating
      if (isset($this->eventData['results'][$className]) && 
          !empty($this->eventData['results'][$className])) {
          return;
      }

      $participants = $this->eventData['classes'][$className];
      
      // Only generate results if there are participants
      if (!empty($participants)) {
          // Shuffle participants for random placement
          shuffle($participants);
          
          // Generate results
          $results = [];
          foreach ($participants as $place => $participant) {
              $results[] = [
                  'place' => $place + 1,
                  'rider' => $participant['rider'],
                  'horse' => $participant['horse']
              ];
          }
          
          // Store results
          if (!isset($this->eventData['results'])) {
              $this->eventData['results'] = [];
          }
          $this->eventData['results'][$className] = $results;
      }
  }
  
  public function getEventData() {
      return $this->eventData;
  }
}

try {
    // Validate required fields
    if (empty($_POST['event_name']) || empty($_POST['rider_name']) || 
        empty($_POST['class']) || empty($_POST['horse']) || 
        !is_array($_POST['class']) || !is_array($_POST['horse'])) {
        throw new Exception('Missing or invalid required fields');
    }

    $eventName = $_POST['event_name'];
    $riderName = trim($_POST['rider_name']);
    $classes = $_POST['class'];
    $horses = $_POST['horse'];

    // Validate arrays have same length
    if (count($classes) !== count($horses)) {
        throw new Exception('Invalid form data');
    }

    // Load event data
    $participantsFile = "../data/$eventName.json";
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
        $horseName = trim($horses[$i]);

        if (empty($className) || empty($horseName)) {
            continue;
        }

        $registrations[] = [
            'class' => $className,
            'horse' => $horseName
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
    $classHorseCount = [];
    $riderClassHorseCount = [];
    $horseClassCount = [];
    $horsesInClass = [];

    // Check new registrations for duplicate horses in same class
    $newHorsesInClass = [];
    foreach ($registrations as $reg) {
        if (!isset($newHorsesInClass[$reg['class']])) {
            $newHorsesInClass[$reg['class']] = [];
        }
        
        if (in_array(strtolower($reg['horse']), array_map('strtolower', $newHorsesInClass[$reg['class']]))) {
            throw new Exception("Horse {$reg['horse']} is already being registered in class {$reg['class']}");
        }
        
        $newHorsesInClass[$reg['class']][] = $reg['horse'];
    }

    // Count existing entries and check for duplicates
    foreach ($eventData['classes'] as $className => $entries) {
        $classHorseCount[$className] = count($entries);
        $horsesInClass[$className] = [];
        
        foreach ($entries as $entry) {
            $riderClassHorseCount[$entry['rider']][$className] = 
                ($riderClassHorseCount[$entry['rider']][$className] ?? 0) + 1;
            $horseClassCount[$entry['horse']] = 
                ($horseClassCount[$entry['horse']] ?? 0) + 1;
            $horsesInClass[$className][] = strtolower($entry['horse']);
        }
    }

    // Validate new registrations
    foreach ($registrations as $reg) {
        // Check if horse is already in the class
        if (in_array(strtolower($reg['horse']), $horsesInClass[$reg['class']] ?? [])) {
            throw new Exception("Horse {$reg['horse']} is already registered in class {$reg['class']}");
        }

        // Check class capacity
        $classHorseCount[$reg['class']] = 
            ($classHorseCount[$reg['class']] ?? 0) + 1;
        if ($classHorseCount[$reg['class']] > $eventData['rules']['maxHorsesPerClass']) {
            throw new Exception("Class {$reg['class']} would exceed maximum capacity");
        }

        // Check horses per rider in class
        $riderClassHorseCount[$riderName][$reg['class']] = 
            ($riderClassHorseCount[$riderName][$reg['class']] ?? 0) + 1;
        if ($riderClassHorseCount[$riderName][$reg['class']] > 
            $eventData['rules']['maxHorsesPerRiderInClass']) {
            throw new Exception(
                "Exceeded maximum horses per rider in class {$reg['class']}"
            );
        }

        // Check classes per horse
        $horseClassCount[$reg['horse']] = 
            ($horseClassCount[$reg['horse']] ?? 0) + 1;
        if ($horseClassCount[$reg['horse']] > $eventData['rules']['maxClassesPerHorse']) {
            throw new Exception(
                "Horse {$reg['horse']} would exceed maximum classes"
            );
        }
    }

    // All validations passed, add registrations
    foreach ($registrations as $reg) {
        $eventData['classes'][$reg['class']][] = [
            'rider' => $riderName,
            'horse' => $reg['horse']
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