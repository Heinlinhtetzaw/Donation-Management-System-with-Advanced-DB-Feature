<?php

function current_admin_identity(mysqli $conn) {
    $sessionAdminId = positive_int($_SESSION['admin_id'] ?? null);
    $sessionUsername = trim((string) ($_SESSION['admin_username'] ?? ''));

    if ($sessionAdminId !== null) {
        $stmt = $conn->prepare('SELECT admin_id, adname FROM admin WHERE admin_id = ? LIMIT 1');
        $stmt->bind_param('i', $sessionAdminId);
    } elseif ($sessionUsername !== '') {
        // Supports sessions created before admin_id was added to the login session.
        $stmt = $conn->prepare('SELECT admin_id, adname FROM admin WHERE adname = ? LIMIT 1');
        $stmt->bind_param('s', $sessionUsername);
    } else {
        throw new RuntimeException('Authenticated administrator identity is unavailable.');
    }

    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$admin) {
        throw new RuntimeException('Authenticated administrator no longer exists.');
    }

    $identity = [
        'id' => (int) $admin['admin_id'],
        'username' => (string) $admin['adname'],
    ];
    $_SESSION['admin_id'] = $identity['id'];
    $_SESSION['admin_username'] = $identity['username'];

    return $identity;
}

function write_admin_audit(mysqli $conn, array $admin, $action, $entityType, $entityId, $details) {
    $adminId = (int) $admin['id'];
    $adminUsername = (string) $admin['username'];
    $entityId = (string) $entityId;
    $details = (string) $details;
    $details = function_exists('mb_substr')
        ? mb_substr($details, 0, 1000, 'UTF-8')
        : substr($details, 0, 1000);
    $stmt = $conn->prepare(
        'INSERT INTO admin_audit_logs '
        . '(admin_id, admin_username, action_type, entity_type, entity_id, details) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssss', $adminId, $adminUsername, $action, $entityType, $entityId, $details);
    $stmt->execute();
    $stmt->close();
}
