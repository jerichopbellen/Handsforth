<?php
// Legacy typo route retained for backward compatibility.
$donation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = $donation_id > 0 ? ('?id=' . $donation_id) : '';
header('Location: distributeDonation.php' . $query);
exit();

