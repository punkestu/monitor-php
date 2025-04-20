<?php
header("Access-Control-Allow-Origin: *");

// Allow specific methods (GET, POST, etc.)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// If pre-flight request, send a successful response
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the time window (in hours) from the query parameter, default to 24 hours
$window = isset($_GET['window']) ? (int) $_GET['window'] : 24;

// Read the data from data.json
$dataFile = 'data.json';
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

// Get the current timestamp
$currentTimestamp = time();

// Filter the data to only include entries within the selected time window
$filteredData = array_filter($data, function($entry) use ($currentTimestamp, $window) {
    return ($currentTimestamp - $entry['time'] / 1000) <= $window * 3600; // time in seconds
});

// Initialize the summary
$summary = [
    'totalEntries' => count($filteredData),
    'averageCPU' => 0,
    'averageRAM' => 0,
    'averageDISK' => 0,
    'minCPU' => PHP_INT_MAX,
    'maxCPU' => PHP_INT_MIN,
    'minRAM' => PHP_INT_MAX,
    'maxRAM' => PHP_INT_MIN,
    'minDISK' => PHP_INT_MAX,
    'maxDISK' => PHP_INT_MIN,
    'lastCPU' => 0,
    'lastRAM' => 0,
    'lastDISK' => 0,
    'maxCPUTimestamp' => 0,
    'maxRAMTimestamp' => 0,
    'maxDISKTimestamp' => 0
];

// Calculate averages and min/max values for the filtered data
if ($filteredData) {
    $cpuSum = 0;
    $ramSum = 0;
    $diskSum = 0;

    foreach ($filteredData as $entry) {
        $cpuSum += $entry['cpu'];
        $ramSum += $entry['ram'];
        $diskSum += $entry['disk'];

        // Track min/max CPU and RAM values along with their timestamps
        if ($entry['cpu'] < $summary['minCPU']) $summary['minCPU'] = $entry['cpu'];
        if ($entry['cpu'] > $summary['maxCPU']) {
            $summary['maxCPU'] = $entry['cpu'];
            $summary['maxCPUTimestamp'] = $entry['time']; // Timestamp of highest CPU usage
        }
        if ($entry['ram'] < $summary['minRAM']) $summary['minRAM'] = $entry['ram'];
        if ($entry['ram'] > $summary['maxRAM']) {
            $summary['maxRAM'] = $entry['ram'];
            $summary['maxRAMTimestamp'] = $entry['time']; // Timestamp of highest RAM usage
        }
        if ($entry['disk'] < $summary['minDISK']) $summary['minDISK'] = $entry['disk'];
        if ($entry['disk'] > $summary['maxDISK']) {
            $summary['maxDISK'] = $entry['disk'];
            $summary['maxDISKTimestamp'] = $entry['time']; // Timestamp of highest RAM usage
        }
    }
    $summary['maxCPU'] = round($summary['maxCPU'], 2);
    $summary['maxRAM'] = round($summary['maxRAM'], 2);
    $summary['maxDISK'] = round($summary['maxDISK'], 2);
    
    $summary['minCPU'] = round($summary['minCPU'], 2);
    $summary['minRAM'] = round($summary['minRAM'], 2);
    $summary['minDISK'] = round($summary['minDISK'], 2);

    $summary['averageCPU'] = round($cpuSum / count($filteredData), 2);
    $summary['averageRAM'] = round($ramSum / count($filteredData), 2);
    $summary['averageDISK'] = round($diskSum / count($filteredData), 2);

    // Get the latest (last) CPU and RAM values
    $lastEntry = end($filteredData);
    $summary['lastCPU'] = round($lastEntry['cpu'], 2);
    $summary['lastRAM'] = round($lastEntry['ram'], 2);
    $summary['lastDISK'] = round($lastEntry['disk'], 2);
}

// Convert timestamps to human-readable format
$summary['maxCPUTimestamp'] = date('Y-m-d H:i:s', $summary['maxCPUTimestamp'] / 1000);
$summary['maxRAMTimestamp'] = date('Y-m-d H:i:s', $summary['maxRAMTimestamp'] / 1000);
$summary['maxDISKTimestamp'] = date('Y-m-d H:i:s', $summary['maxDISKTimestamp'] / 1000);

// Return the summary as JSON
echo json_encode($summary);
?>
