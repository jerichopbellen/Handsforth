<?php

function buildNamedPlaceholders(array $values, string $prefix) {
    $placeholders = [];
    $params = [];

    foreach (array_values($values) as $index => $value) {
        $name = ':' . $prefix . $index;
        $placeholders[] = $name;
        $params[$name] = $value;
    }

    return [$placeholders, $params];
}

function fetchDonationReportRows(PDO $pdo, array $filters = []) {
    $filterType = trim((string)($filters['type'] ?? ''));
    $filterDateFrom = trim((string)($filters['date_from'] ?? ''));
    $filterDateTo = trim((string)($filters['date_to'] ?? ''));
    $search = trim((string)($filters['search'] ?? ''));

    $sql = "SELECT
                d.donation_id,
                d.donation_type,
                d.amount AS header_amount,
                d.description,
                d.date_received,
                d.receipt_file,
                d.txn_number,
                d.status,
                d.created_at,
                d.updated_at,
                donors.name AS donor_name,
                md.amount AS monetary_amount,
                md.payment_method,
                md.check_number,
                md.designation,
                md.recurring,
                CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS staff_name
            FROM donations d
            LEFT JOIN donors ON d.donor_id = donors.donor_id
            LEFT JOIN monetary_details md ON md.id = (
                SELECT MAX(md2.id)
                FROM monetary_details md2
                WHERE md2.donation_id = d.donation_id
            )
            LEFT JOIN users u ON d.staff_id = u.user_id
            WHERE 1=1";

    $params = [];

    if ($filterType !== '') {
        $sql .= ' AND d.donation_type = :type';
        $params[':type'] = $filterType;
    }
    if ($search !== '') {
        $sql .= ' AND (donors.name LIKE :search OR d.description LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
    if ($filterDateFrom !== '') {
        $sql .= ' AND d.date_received >= :date_from';
        $params[':date_from'] = $filterDateFrom;
    }
    if ($filterDateTo !== '') {
        $sql .= ' AND d.date_received <= :date_to';
        $params[':date_to'] = $filterDateTo;
    }

    $sql .= ' ORDER BY d.date_received DESC, d.donation_id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $baseRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($baseRows)) {
        return [];
    }

    $donationIds = array_values(array_unique(array_map('intval', array_column($baseRows, 'donation_id'))));
    [$idPlaceholders, $idParams] = buildNamedPlaceholders($donationIds, 'id');

    $goodsByDonation = [];
    if (!empty($idPlaceholders)) {
        $goodsSql = 'SELECT donation_id, item_id, description, quantity, unit
                     FROM donation_items
                     WHERE donation_id IN (' . implode(',', $idPlaceholders) . ')
                     ORDER BY donation_id ASC, item_id ASC';
        $goodsStmt = $pdo->prepare($goodsSql);
        $goodsStmt->execute($idParams);
        $goodsRows = $goodsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($goodsRows as $item) {
            $donationId = (int)$item['donation_id'];
            $itemId = (int)$item['item_id'];
            $itemQty = (int)($item['quantity'] ?? 0);
            $itemDesc = trim((string)($item['description'] ?? ''));
            $itemUnit = trim((string)($item['unit'] ?? ''));

            if (!isset($goodsByDonation[$donationId])) {
                $goodsByDonation[$donationId] = [
                    'total_units' => 0,
                    'items' => [],
                ];
            }

            $goodsByDonation[$donationId]['total_units'] += max(0, $itemQty);
            $goodsByDonation[$donationId]['items'][$itemId] = [
                'description' => $itemDesc,
                'quantity' => max(0, $itemQty),
                'unit' => $itemUnit,
            ];
        }
    }

    $distributionByDonation = [];
    if (!empty($idPlaceholders)) {
        $distSql = 'SELECT donation_id, distributed_amount, notes
                    FROM distributions
                    WHERE donation_id IN (' . implode(',', $idPlaceholders) . ')';
        $distStmt = $pdo->prepare($distSql);
        $distStmt->execute($idParams);
        $distRows = $distStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($distRows as $dist) {
            $donationId = (int)$dist['donation_id'];
            if (!isset($distributionByDonation[$donationId])) {
                $distributionByDonation[$donationId] = [
                    'total_distributed' => 0.0,
                    'goods_items_by_id' => [],
                    'goods_items_by_desc' => [],
                ];
            }

            $distributionByDonation[$donationId]['total_distributed'] += (float)($dist['distributed_amount'] ?? 0);

            $notes = json_decode((string)($dist['notes'] ?? ''), true);
            if (!is_array($notes) || ($notes['type'] ?? '') !== 'goods_distribution') {
                continue;
            }

            $items = $notes['items'] ?? [];
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                $itemId = (int)($item['item_id'] ?? 0);
                $itemDesc = trim((string)($item['description'] ?? ''));
                $itemQty = (int)($item['quantity'] ?? 0);
                if ($itemQty <= 0) {
                    continue;
                }

                if ($itemDesc === '') {
                    $itemDesc = 'Item';
                }

                if ($itemId > 0) {
                    if (!isset($distributionByDonation[$donationId]['goods_items_by_id'][$itemId])) {
                        $distributionByDonation[$donationId]['goods_items_by_id'][$itemId] = [
                            'description' => $itemDesc,
                            'quantity' => 0,
                        ];
                    }
                    $distributionByDonation[$donationId]['goods_items_by_id'][$itemId]['quantity'] += $itemQty;
                }

                if (!isset($distributionByDonation[$donationId]['goods_items_by_desc'][$itemDesc])) {
                    $distributionByDonation[$donationId]['goods_items_by_desc'][$itemDesc] = 0;
                }
                $distributionByDonation[$donationId]['goods_items_by_desc'][$itemDesc] += $itemQty;
            }
        }
    }

    $reportRows = [];
    foreach ($baseRows as $row) {
        $donationId = (int)$row['donation_id'];
        $donationType = (string)($row['donation_type'] ?? '');
        $distInfo = $distributionByDonation[$donationId] ?? [
            'total_distributed' => 0.0,
            'goods_items_by_id' => [],
            'goods_items_by_desc' => [],
        ];

        $donorName = trim((string)($row['donor_name'] ?? ''));
        if ($donorName === '') {
            $donorName = 'Anonymous';
        }

        $staffName = trim((string)($row['staff_name'] ?? ''));
        if ($staffName === '') {
            $staffName = 'N/A';
        }

        $distributionStatus = 'Not Distributed';
        $distributionProgress = 'No distributions yet';
        $itemTypeProgress = '';
        $distributionDetails = '';

        $amountMonetary = '';
        $goodsDescription = '';
        $amountOrGoods = '';

        $fundAmountRaw = 0.0;
        $fundDistributedRaw = 0.0;
        $goodsTotalUnitsRaw = 0;
        $goodsDistributedUnitsRaw = 0;

        if ($donationType === 'funds') {
            $fundAmountRaw = (float)($row['monetary_amount'] ?? $row['header_amount'] ?? 0);
            $fundDistributedRaw = (float)($distInfo['total_distributed'] ?? 0);

            $amountMonetary = '$' . number_format($fundAmountRaw, 2);
            $amountOrGoods = $amountMonetary;

            if ($fundDistributedRaw > 0) {
                $isFullyDistributed = $fundAmountRaw > 0 && ($fundDistributedRaw + 0.00001 >= $fundAmountRaw);
                $distributionStatus = $isFullyDistributed ? 'Fully Distributed' : 'Partially Distributed';
                $distributionProgress = '$' . number_format($fundDistributedRaw, 2) . ' of $' . number_format($fundAmountRaw, 2) . ' distributed';
            }
        } else {
            $goods = $goodsByDonation[$donationId] ?? ['total_units' => 0, 'items' => []];
            $goodsTotalUnitsRaw = (int)($goods['total_units'] ?? 0);
            $goodsDistributedUnitsRaw = (int)round((float)($distInfo['total_distributed'] ?? 0));

            $descriptionParts = [];
            foreach ($goods['items'] as $item) {
                $desc = trim((string)($item['description'] ?? ''));
                $qty = (int)($item['quantity'] ?? 0);
                $unit = trim((string)($item['unit'] ?? ''));
                if ($qty <= 0) {
                    continue;
                }
                if ($desc === '') {
                    $desc = 'Item';
                }
                $part = $desc . ' x' . $qty;
                if ($unit !== '') {
                    $part .= ' ' . $unit;
                }
                $descriptionParts[] = $part;
            }

            $goodsDescription = implode('; ', $descriptionParts);
            if ($goodsDescription === '') {
                $goodsDescription = trim((string)($row['description'] ?? ''));
            }
            if ($goodsDescription === '') {
                $goodsDescription = 'N/A';
            }

            $amountOrGoods = $goodsDescription;

            $itemTypesTotal = 0;
            $itemTypesFullyDistributed = 0;
            $distributedById = $distInfo['goods_items_by_id'] ?? [];
            foreach ($goods['items'] as $itemId => $item) {
                $qty = (int)($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $itemTypesTotal++;
                $distributedQty = (int)($distributedById[$itemId]['quantity'] ?? 0);
                if ($distributedQty >= $qty) {
                    $itemTypesFullyDistributed++;
                }
            }

            if ($goodsDistributedUnitsRaw > 0) {
                $isFullyDistributed = $itemTypesTotal > 0 && $itemTypesFullyDistributed >= $itemTypesTotal;
                $distributionStatus = $isFullyDistributed ? 'Fully Distributed' : 'Partially Distributed';
                $distributionProgress = $goodsDistributedUnitsRaw . ' of ' . $goodsTotalUnitsRaw . ' units distributed';
                if ($itemTypesTotal > 0) {
                    $itemTypeProgress = $itemTypesFullyDistributed . ' of ' . $itemTypesTotal . ' item types fully distributed';
                }

                $itemDetailParts = [];
                foreach (($distInfo['goods_items_by_desc'] ?? []) as $itemDesc => $itemQty) {
                    $itemDetailParts[] = $itemDesc . ' x' . (int)$itemQty;
                }
                $distributionDetails = implode('; ', $itemDetailParts);
            }
        }

        $reportRows[] = [
            'donation_id' => $donationId,
            'donor_name' => $donorName,
            'donation_type' => ucfirst($donationType),
            'amount_monetary' => $amountMonetary,
            'goods_description' => $goodsDescription,
            'amount_or_goods' => $amountOrGoods,
            'date_received' => (string)($row['date_received'] ?? ''),
            'distribution_status' => $distributionStatus,
            'distribution_progress' => $distributionProgress,
            'item_type_progress' => $itemTypeProgress,
            'distribution_details' => $distributionDetails,
            'payment_method' => (string)($row['payment_method'] ?? ''),
            'reference_number' => (string)($row['check_number'] ?? ''),
            'designation' => (string)($row['designation'] ?? ''),
            'recurring' => !empty($row['recurring']) ? 'Yes' : 'No',
            'staff_name' => $staffName,
            'receipt_file' => (string)($row['receipt_file'] ?? ''),
            'txn_number' => (string)($row['txn_number'] ?? ''),
            'record_status' => (string)($row['status'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'fund_amount_raw' => $fundAmountRaw,
            'fund_distributed_raw' => $fundDistributedRaw,
            'goods_total_units_raw' => $goodsTotalUnitsRaw,
            'goods_distributed_units_raw' => $goodsDistributedUnitsRaw,
        ];
    }

    return $reportRows;
}

function summarizeDonationReportRows(array $rows) {
    $summary = [
        'total_donations' => count($rows),
        'total_funds_donated' => 0.0,
        'total_funds_distributed' => 0.0,
        'total_goods_units_donated' => 0,
        'total_goods_units_distributed' => 0,
    ];

    foreach ($rows as $row) {
        $type = strtolower((string)($row['donation_type'] ?? ''));
        if ($type === 'funds') {
            $summary['total_funds_donated'] += (float)($row['fund_amount_raw'] ?? 0);
            $summary['total_funds_distributed'] += (float)($row['fund_distributed_raw'] ?? 0);
        } elseif ($type === 'goods') {
            $summary['total_goods_units_donated'] += (int)($row['goods_total_units_raw'] ?? 0);
            $summary['total_goods_units_distributed'] += (int)($row['goods_distributed_units_raw'] ?? 0);
        }
    }

    return $summary;
}
