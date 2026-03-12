<?php
// Recurring donation scheduling utility
function scheduleRecurringDonation($pdo, $donation_id, $frequency, $next_date) {
    $stmt = $pdo->prepare("UPDATE donations SET is_recurring = 1, recurring_schedule = ?, next_recurring_date = ? WHERE donation_id = ?");
    $stmt->execute([$frequency, $next_date, $donation_id]);
}
?>