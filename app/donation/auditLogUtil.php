<?php
// Audit logging utility for donations
function logDonationAction($pdo, $action_type, $donation_id, $user_id, $old_value, $new_value) {
    $stmt = $pdo->prepare("INSERT INTO audit_logs (table_name, record_id, action_type, performed_by, timestamp, old_value, new_value) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
    $stmt->execute([
        'donations',
        $donation_id,
        $action_type,
        $user_id,
        json_encode($old_value),
        json_encode($new_value)
    ]);
}
?>