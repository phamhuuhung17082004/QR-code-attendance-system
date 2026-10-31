<?php
/**
 * Test suite for IP CIDR matching (Hạng mục 2).
 * Usage: php tests/ip_cidr_test.php
 */
require_once __DIR__ . '/../lib/security.php';

$testCases = [
    // 1. IPv4 trong / ngoài dải
    ['name' => 'IPv4 inside /24', 'ip' => '192.168.1.50', 'cidrs' => '192.168.1.0/24', 'expected' => true],
    ['name' => 'IPv4 outside /24', 'ip' => '192.168.2.50', 'cidrs' => '192.168.1.0/24', 'expected' => false],

    // 2. Biên dải IPv4 (/24, /32, /0)
    ['name' => 'IPv4 /24 lower bound', 'ip' => '192.168.1.0', 'cidrs' => '192.168.1.0/24', 'expected' => true],
    ['name' => 'IPv4 /24 upper bound', 'ip' => '192.168.1.255', 'cidrs' => '192.168.1.0/24', 'expected' => true],
    ['name' => 'IPv4 /32 match', 'ip' => '10.0.0.1', 'cidrs' => '10.0.0.1/32', 'expected' => true],
    ['name' => 'IPv4 /32 non-match', 'ip' => '10.0.0.2', 'cidrs' => '10.0.0.1/32', 'expected' => false],
    ['name' => 'IPv4 /0 any ip 1', 'ip' => '1.2.3.4', 'cidrs' => '0.0.0.0/0', 'expected' => true],
    ['name' => 'IPv4 /0 any ip 2', 'ip' => '255.255.255.255', 'cidrs' => '0.0.0.0/0', 'expected' => true],

    // 3. IPv6 trong / ngoài dải
    ['name' => 'IPv6 inside /48', 'ip' => '2001:db8:abcd::1', 'cidrs' => '2001:db8:abcd::/48', 'expected' => true],
    ['name' => 'IPv6 outside /48', 'ip' => '2001:db8:abce::1', 'cidrs' => '2001:db8:abcd::/48', 'expected' => false],
    ['name' => 'IPv6 /128 match', 'ip' => '2001:db8::1', 'cidrs' => '2001:db8::1/128', 'expected' => true],
    ['name' => 'IPv6 /128 non-match', 'ip' => '2001:db8::2', 'cidrs' => '2001:db8::1/128', 'expected' => false],
    ['name' => 'IPv6 /0 any ip', 'ip' => 'fe80::1', 'cidrs' => '::/0', 'expected' => true],

    // 4. IPv4-mapped IPv6 (::ffff:a.b.c.d)
    ['name' => 'IPv4-mapped inside IPv4 CIDR', 'ip' => '::ffff:192.168.1.5', 'cidrs' => '192.168.1.0/24', 'expected' => true],
    ['name' => 'IPv4-mapped outside IPv4 CIDR', 'ip' => '::ffff:192.168.2.5', 'cidrs' => '192.168.1.0/24', 'expected' => false],
    ['name' => 'IPv4 inside IPv4-mapped IPv6 CIDR', 'ip' => '192.168.1.5', 'cidrs' => '::ffff:192.168.1.0/120', 'expected' => true],
    ['name' => 'IPv4-mapped inside IPv4-mapped IPv6 CIDR', 'ip' => '::ffff:192.168.1.5', 'cidrs' => '::ffff:192.168.1.0/120', 'expected' => true],

    // 5. CIDR sai định dạng
    ['name' => 'Malformed CIDR no slash', 'ip' => '192.168.1.1', 'cidrs' => '192.168.1.1', 'expected' => false],
    ['name' => 'Malformed CIDR invalid ip', 'ip' => '192.168.1.1', 'cidrs' => 'invalid/24', 'expected' => false],
    ['name' => 'Malformed CIDR invalid prefix 33', 'ip' => '192.168.1.1', 'cidrs' => '192.168.1.0/33', 'expected' => false],
    ['name' => 'Malformed CIDR invalid prefix -1', 'ip' => '192.168.1.1', 'cidrs' => '192.168.1.0/-1', 'expected' => false],
    ['name' => 'Malformed CIDR garbage', 'ip' => '192.168.1.1', 'cidrs' => 'garbage', 'expected' => false],
    ['name' => 'Malformed IP address', 'ip' => 'not-an-ip', 'cidrs' => '192.168.1.0/24', 'expected' => false],

    // 6. Chuỗi rỗng
    ['name' => 'Empty CIDRs string', 'ip' => '192.168.1.1', 'cidrs' => '', 'expected' => false],
    ['name' => 'Whitespace only CIDRs', 'ip' => '192.168.1.1', 'cidrs' => "   \n\t  ", 'expected' => false],
    ['name' => 'Empty client IP', 'ip' => '', 'cidrs' => '192.168.1.0/24', 'expected' => false],

    // 7. Nhiều CIDR (dấu phẩy, khoảng trắng, xuống dòng)
    ['name' => 'Multiple CIDRs match first IPv4', 'ip' => '10.5.0.1', 'cidrs' => '10.0.0.0/8, 192.168.1.0/24, 2001:db8::/32', 'expected' => true],
    ['name' => 'Multiple CIDRs match second IPv4', 'ip' => '192.168.1.99', 'cidrs' => '10.0.0.0/8, 192.168.1.0/24, 2001:db8::/32', 'expected' => true],
    ['name' => 'Multiple CIDRs match IPv6', 'ip' => '2001:db8:ffff::1', 'cidrs' => '10.0.0.0/8, 192.168.1.0/24, 2001:db8::/32', 'expected' => true],
    ['name' => 'Multiple CIDRs none match', 'ip' => '172.16.0.1', 'cidrs' => '10.0.0.0/8, 192.168.1.0/24, 2001:db8::/32', 'expected' => false],
    ['name' => 'Multiple CIDRs with newlines and spaces', 'ip' => '192.168.1.5', 'cidrs' => "  10.0.0.0/8 ,\n\r 192.168.1.0/24\n", 'expected' => true],
];

$passed = 0;
$failed = 0;

echo "=== Running IP CIDR Tests ===\n";
foreach ($testCases as $index => $tc) {
    $result = ip_in_cidrs($tc['ip'], $tc['cidrs']);
    if ($result === $tc['expected']) {
        echo sprintf("[PASS] Case #%02d: %s\n", $index + 1, $tc['name']);
        $passed++;
    } else {
        echo sprintf(
            "[FAIL] Case #%02d: %s | IP: '%s' | CIDRs: '%s' | Expected: %s, Got: %s\n",
            $index + 1,
            $tc['name'],
            $tc['ip'],
            $tc['cidrs'],
            $tc['expected'] ? 'true' : 'false',
            $result ? 'true' : 'false'
        );
        $failed++;
    }
}

// Test cidr_list_parse directly
$parseSample = " 192.168.1.0/24, invalid, 2001:db8::/48\n10.0.0.0/8 ,, 1.2.3.4/35 ";
$parsed = cidr_list_parse($parseSample);
$expectedParsed = ['192.168.1.0/24', '2001:db8::/48', '10.0.0.0/8'];
if ($parsed === $expectedParsed) {
    echo "[PASS] cidr_list_parse extraction test\n";
    $passed++;
} else {
    echo "[FAIL] cidr_list_parse: " . json_encode($parsed) . " != " . json_encode($expectedParsed) . "\n";
    $failed++;
}

echo "=============================\n";
echo "Results: $passed PASSED, $failed FAILED\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

