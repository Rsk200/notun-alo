<?php
// ============================================
// auto_assign_v2.php - AI Smart Assignment Engine
// PHP Integration with Flask Microservice
// ============================================

function autoAssignPickupAI(int $pickup_id, PDO $pdo): array {
    try {
        // 1. Fetch pickup + user coordinates
        $stmt = $pdo->prepare("
            SELECT p.*, u.lat as user_lat, u.lng as user_lng 
            FROM pickups p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ? AND p.status = 'pending'
        ");
        $stmt->execute([$pickup_id]);
        $pickup = $stmt->fetch();
        
        if (!$pickup) {
            return ['success' => false, 'reason' => 'Pickup not found or not pending'];
        }

        // 2. Build JSON payload for Flask API
        $payload = json_encode([
            'pickup_id'        => $pickup['id'],
            'category'         => $pickup['category'],
            'estimated_weight' => (float)$pickup['estimated_weight'],
            'schedule_date'    => $pickup['schedule_date'],
            'user_lat'         => $pickup['user_lat'] !== null ? (float)$pickup['user_lat'] : null,
            'user_lng'         => $pickup['user_lng'] !== null ? (float)$pickup['user_lng'] : null
        ]);

        // 3. POST to Flask with 3-second timeout
        $ch = curl_init('http://localhost:5005/assign');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3 seconds max
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $use_fallback = false;
        $api_result = null;

        if ($http_code === 200 && $response) {
            $api_result = json_decode($response, true);
            if (!$api_result || !$api_result['success']) {
                $use_fallback = true;
            }
        } else {
            $use_fallback = true;
        }

        // 4. CRITICAL FALLBACK (Tier 1 Rule-based)
        if ($use_fallback) {
            // Log fallback event
            $log_msg = "[" . date('Y-m-d H:i:s') . "] Fallback for Pickup ID $pickup_id. Reason: API failed ($curl_error HTTP $http_code).\n";
            @file_put_contents(__DIR__ . '/../logs/assignment_fallback.log', $log_msg, FILE_APPEND);

            // SQL ORDER BY active_pickups ASC
            $fallback_stmt = $pdo->query("
                SELECT agency_id FROM agency_stats 
                WHERE is_available = 1 AND load_ratio < 1.0
                ORDER BY active_pickups ASC 
                LIMIT 1
            ");
            $fallback_agency = $fallback_stmt->fetch();
            
            if (!$fallback_agency) {
                return ['success' => false, 'reason' => 'No agencies available even in fallback'];
            }
            
            $api_result = [
                'agency_id' => $fallback_agency['agency_id'],
                'agency_name' => 'Fallback Assigned',
                'score' => null,
                'model_version' => 'sql_fallback',
                'reason' => 'AI unavailable, used rule-based least-busy assignment.'
            ];
        }

        // 5. Successful Assignment Transaction
        $pdo->beginTransaction();
        
        // i. Update pickup status (race condition guard)
        $update_stmt = $pdo->prepare("
            UPDATE pickups 
            SET agency_id = ?, status = 'assigned', updated_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $update_stmt->execute([$api_result['agency_id'], $pickup_id]);
        
        if ($update_stmt->rowCount() === 0) {
            $pdo->rollBack();
            return ['success' => false, 'reason' => 'Race condition: Pickup already assigned or modified.'];
        }

        // ii. Insert assignment_log
        $log_stmt = $pdo->prepare("
            INSERT INTO assignment_log (pickup_id, agency_id, method, score_total, model_version)
            VALUES (?, ?, ?, ?, ?)
        ");
        $method = $use_fallback ? 'fallback_sql' : 'ai';
        $log_stmt->execute([
            $pickup_id, 
            $api_result['agency_id'], 
            $method,
            $api_result['score'], 
            $api_result['model_version']
        ]);

        // iii. Insert notification for Agency
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $agency_msg = "You have been assigned a new pickup request for " . $pickup['category'] . " on " . date('d M', strtotime($pickup['schedule_date'])) . ".";
        $notif_stmt->execute([$api_result['agency_id'], "New Pickup Assigned", $agency_msg]);

        // iv. Insert notification for Admins
        $admin_msg = "Pickup #$pickup_id assigned to Agency ID " . $api_result['agency_id'] . " via $method. " . ($api_result['reason'] ?? '');
        $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            $notif_stmt->execute([$admin['id'], "AI Assignment Executed", $admin_msg]);
        }

        $pdo->commit();

        return [
            'success' => true,
            'agency_id' => $api_result['agency_id'],
            'agency_name' => $api_result['agency_name'],
            'score' => $api_result['score'],
            'model_version' => $api_result['model_version']
        ];

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'reason' => $e->getMessage()];
    }
}
?>
