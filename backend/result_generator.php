<?php
class ResultGenerator {
    private $eventData;
    private $rules;
    private $lastDate;

    public function __construct($eventData) {
        $this->eventData = $eventData;
        $this->lastDate = new DateTime($eventData['lastDate']);
        $this->loadRules($eventData['ruleSet']); // Load category-based or default rules
    }

    private function loadRules($ruleSet) {
        // Attempt to load category-specific rules
        $rulesFile = __DIR__ . "/rules/{$ruleSet}.json";

        if (!file_exists($rulesFile)) {
            // Fallback to default rules if category rules do not exist
            $rulesFile = __DIR__ . "/rules/default.json";
        }

        $this->rules = json_decode(file_get_contents($rulesFile), true);

        if (!$this->rules) {
            throw new Exception("Failed to load rules from file: $rulesFile");
        }
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
            if ($isClassFull || $isRegistrationClosed) {
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
        $pointsRules = $this->rules[$className] ?? $this->rules['default']; // Use default rules if class-specific rules are missing

        // Only generate results if there are participants
        if (!empty($participants) && $pointsRules) {
            // Shuffle participants for random placement
            shuffle($participants);

            // Generate results
            $results = [];
            foreach ($participants as $place => $participant) {
                // Generate random points within the rule's range
                $points = rand($pointsRules[0]['min'], $pointsRules[count($pointsRules) - 1]['max']);
                $award = $this->determineAward($points, $pointsRules);

                $results[] = [
                    'place' => $place + 1,
                    'rider' => $participant['rider'],
                    'horse' => $participant['horse'],
                    'points' => $points,
                    'award' => $award
                ];
            }

            // Store results
            if (!isset($this->eventData['results'])) {
                $this->eventData['results'] = [];
            }
            $this->eventData['results'][$className] = $results;
        }
    }

    private function determineAward($points, $rules) {
        foreach ($rules as $rule) {
            if ($points >= $rule['min'] && $points <= $rule['max']) {
                return $rule['award'];
            }
        }
        return "Unknown";
    }

    public function getEventData() {
        return $this->eventData;
    }
}
