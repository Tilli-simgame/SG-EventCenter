<?php
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
?>