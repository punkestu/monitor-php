<?php
$allowed_origin = "https://mitragts.co.id";

// Check the origin of the request and allow it if it matches
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] == $allowed_origin) {
    header("Access-Control-Allow-Origin: " . $allowed_origin);
}

// Allow specific methods (GET, POST, PUT, DELETE, etc.)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers (you can modify this as needed)
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Exit after sending headers to handle preflight request
    exit();
}

// --- System info functions
function formatBytes($b){return $b;}
function getCpuCores() {
    $cpuInfo = file('/proc/cpuinfo');
    $coreCount = 0;
    foreach ($cpuInfo as $line) {
        if (preg_match('/^processor/', $line)) {
            $coreCount++;
        }
    }
    return $coreCount;
}
function getCPU() {
    $load = sys_getloadavg();
    return $load[0]; // 1-minute load avg
}
function getRAM() {
    $mem = file('/proc/meminfo');
    $result = [];
    foreach ($mem as $line) {
        if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
            $result[$m[1]] = (int)$m[2];
        }
    }
    $total = $result['MemTotal'] ?? 0;
    $free = $result['MemFree'] ?? 0;
    return $total > 0 ? round((($total - $free) / $total) * 100, 2) : 0;
}
function getDiskUsage() {
    $totalSpace = disk_total_space('/');
    $freeSpace = disk_free_space('/');
    $usedSpace = $totalSpace - $freeSpace;
    return $totalSpace > 0 ? round(($usedSpace / $totalSpace) * 100, 2) : 0;
}

// --- Write to JSON
$filename = __DIR__ . '/data.json';
$data = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
$data[] = [
    'time' => time() * 1000, // JS uses ms
    'cpu' => (getCPU()/getCpuCores())*100,
    'ram' => getRAM(),
    'disk' => getDiskUsage(),
];
if (count($data) > 100) array_shift($data); // Keep last 100 points
file_put_contents($filename, json_encode($data));
echo 'OK';
