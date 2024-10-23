<?php

/**
 * Shuffle the participants and generate results for a class.
 * 
 * @param array $participants The list of participants in a class.
 * @return array The shuffled list of participants with their assigned places.
 */
function shuffleResults($participants) {
    // Fisher-Yates shuffle
    for ($i = count($participants) - 1; $i > 0; $i--) {
        $j = rand(0, $i);
        $temp = $participants[$i];
        $participants[$i] = $participants[$j];
        $participants[$j] = $temp;
    }

    // Assign places
    $results = [];
    foreach ($participants as $index => $participant) {
        $results[] = [
            'place' => $index + 1,
            'rider' => $participant['rider'],
            'horse' => $participant['horse']
        ];
    }

    return $results;
}
?>
