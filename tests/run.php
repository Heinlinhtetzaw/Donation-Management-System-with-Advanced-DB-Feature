<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/DonationAdminService.php';
require_once __DIR__ . '/../app/AuditViewService.php';

$tests = [];

function test_case($name, callable $test) {
    global $tests;
    $tests[] = [$name, $test];
}

function assert_same($expected, $actual, $message = 'Values differ.') {
    if ($expected !== $actual) {
        throw new RuntimeException(
            $message . ' Expected ' . var_export($expected, true)
            . ', received ' . var_export($actual, true) . '.'
        );
    }
}

function assert_true($actual, $message) {
    if ($actual !== true) {
        throw new RuntimeException($message);
    }
}

test_case('HTML output is escaped', static function () {
    assert_same('&lt;script&gt;&quot;x&quot;&lt;/script&gt;', e('<script>"x"</script>'), 'Escaping failed.');
});

test_case('scalar request strings are normalized', static function () {
    assert_same('value', request_string(['q' => ' value '], 'q'), 'Whitespace was not trimmed.');
    assert_same('', request_string(['q' => ['invalid']], 'q'), 'Array input was not rejected.');
});

test_case('Myanmar digits normalize to ASCII', static function () {
    assert_same('09123456789', normalize_myanmar_digits('၀၉၁၂၃၄၅၆၇၈၉'), 'Digit normalization failed.');
});

test_case('ISO dates are calendar-valid', static function () {
    assert_true(is_iso_date('2024-02-29'), 'Leap day should be valid.');
    assert_same(false, is_iso_date('2025-02-29'), 'Invalid leap day was accepted.');
});

test_case('positive integer validation rejects unsafe identifiers', static function () {
    assert_same(42, positive_int('42'), 'Positive integer was rejected.');
    assert_same(null, positive_int('0'), 'Zero was accepted.');
    assert_same(null, positive_int('1 OR 1=1'), 'Non-integer identifier was accepted.');
});

test_case('public media paths stay inside managed asset folders', static function () {
    assert_same('uploads/photo.webp', public_asset_path('uploads/photo.webp'), 'Valid upload path was rejected.');
    assert_same('image/logooo2.jpg', public_asset_path('../secret.txt'), 'Traversal path was accepted.');
});

test_case('donation references are deterministic', static function () {
    assert_same('DON-00000042', donation_reference_for_id(42), 'Donation reference format changed.');
});

test_case('ledger filters normalize supported values', static function () {
    $filters = donation_ledger_filters([
        'status' => 'Complete',
        'foundation_id' => '3',
        'q' => ' donor ',
        'from' => '2026-01-01',
        'to' => 'not-a-date',
    ]);
    assert_same('Complete', $filters['status'], 'Supported status was rejected.');
    assert_same(3, $filters['foundation_id'], 'Foundation identifier was not normalized.');
    assert_same('donor', $filters['search'], 'Search value was not normalized.');
    assert_same('', $filters['to'], 'Invalid date was accepted.');
});

test_case('audit record links distinguish live and deleted entities', static function () {
    assert_same('donation_detail.php?id=5', audit_record_url('donation', 5, 'updated'), 'Live link is wrong.');
    assert_same('audit_record.php?type=news&id=7', audit_record_url('news', 7, 'deleted'), 'Deleted link is wrong.');
});

$failures = 0;
foreach ($tests as [$name, $test]) {
    try {
        $test();
        echo 'PASS: ' . $name . PHP_EOL;
    } catch (Throwable $error) {
        $failures++;
        fwrite(STDERR, 'FAIL: ' . $name . ' — ' . $error->getMessage() . PHP_EOL);
    }
}

if ($failures > 0) {
    exit(1);
}

echo 'PASS: ' . count($tests) . ' unit checks passed.' . PHP_EOL;
