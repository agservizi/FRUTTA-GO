<?php
// api/reports.php - API per report
$method = $_SERVER['REQUEST_METHOD'];


$db = getDB();
$storeId = getCurrentStoreId();

if ($method === 'GET') {
    $type = $_GET['type'] ?? 'daily';

    try {
        if ($type === 'daily') {
            // Report giornaliero
            $stmt = $db->prepare("
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as sales_count,
                    SUM(total) as total_revenue,
                    AVG(total) as avg_sale
                FROM sales
                WHERE store_id = ? AND DATE(created_at) = CURDATE()
                GROUP BY DATE(created_at)
            ");
            $stmt->execute([$storeId]);
            $daily = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['date' => date('Y-m-d'), 'sales_count' => 0, 'total_revenue' => 0, 'avg_sale' => 0];

            $stmt = $db->prepare("
                SELECT COALESCE(SUM(si.qty * COALESCE(p.price_cost, 0)), 0) as total_cost
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id AND p.store_id = s.store_id
                WHERE s.store_id = ? AND DATE(s.created_at) = CURDATE()
            ");
            $stmt->execute([$storeId]);
            $dailyCost = $stmt->fetch(PDO::FETCH_ASSOC);
            $daily['total_cost'] = (float)($dailyCost['total_cost'] ?? 0);
            $daily['total_profit'] = (float)$daily['total_revenue'] - (float)$daily['total_cost'];
            $daily['margin_pct'] = (float)$daily['total_revenue'] > 0
                ? ($daily['total_profit'] / (float)$daily['total_revenue']) * 100
                : 0;

            // Top 10 prodotti oggi
            $stmt = $db->prepare("
                SELECT
                    p.name,
                    SUM(si.qty) as total_qty,
                    SUM(si.line_total) as total_revenue,
                    SUM(si.qty * COALESCE(p.price_cost, 0)) as total_cost,
                    SUM(si.line_total) - SUM(si.qty * COALESCE(p.price_cost, 0)) as total_profit,
                    CASE
                        WHEN SUM(si.line_total) > 0
                        THEN ((SUM(si.line_total) - SUM(si.qty * COALESCE(p.price_cost, 0))) / SUM(si.line_total)) * 100
                        ELSE 0
                    END as margin_pct
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.store_id = ? AND p.store_id = s.store_id AND DATE(s.created_at) = CURDATE()
                GROUP BY p.id, p.name
                ORDER BY total_revenue DESC
                LIMIT 10
            ");
            $stmt->execute([$storeId]);
            $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            successResponse(['daily' => $daily, 'top_products' => $top_products]);

        } elseif ($type === 'monthly') {
            // Report mensile
            $stmt = $db->prepare("
                SELECT
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as sales_count,
                    SUM(total) as total_revenue,
                    AVG(total) as avg_sale,
                    MAX(total) as best_day_revenue,
                    DATE(MAX(created_at)) as best_day
                FROM sales
                WHERE store_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ");
            $stmt->execute([$storeId]);
            $monthly = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['month' => date('Y-m'), 'sales_count' => 0, 'total_revenue' => 0, 'avg_sale' => 0, 'best_day_revenue' => 0, 'best_day' => null];

            $stmt = $db->prepare("
                SELECT COALESCE(SUM(si.qty * COALESCE(p.price_cost, 0)), 0) as total_cost
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id AND p.store_id = s.store_id
                WHERE s.store_id = ? AND MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())
            ");
            $stmt->execute([$storeId]);
            $monthlyCost = $stmt->fetch(PDO::FETCH_ASSOC);
            $monthly['total_cost'] = (float)($monthlyCost['total_cost'] ?? 0);
            $monthly['total_profit'] = (float)$monthly['total_revenue'] - (float)$monthly['total_cost'];
            $monthly['margin_pct'] = (float)$monthly['total_revenue'] > 0
                ? ($monthly['total_profit'] / (float)$monthly['total_revenue']) * 100
                : 0;

            // Prodotti più venduti mese
            $stmt = $db->prepare("
                SELECT
                    p.name,
                    SUM(si.qty) as total_qty,
                    SUM(si.line_total) as total_revenue,
                    SUM(si.qty * COALESCE(p.price_cost, 0)) as total_cost,
                    SUM(si.line_total) - SUM(si.qty * COALESCE(p.price_cost, 0)) as total_profit,
                    CASE
                        WHEN SUM(si.line_total) > 0
                        THEN ((SUM(si.line_total) - SUM(si.qty * COALESCE(p.price_cost, 0))) / SUM(si.line_total)) * 100
                        ELSE 0
                    END as margin_pct
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN sales s ON si.sale_id = s.id
                WHERE s.store_id = ? AND p.store_id = s.store_id AND MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())
                GROUP BY p.id, p.name
                ORDER BY total_revenue DESC
                LIMIT 10
            ");
            $stmt->execute([$storeId]);
            $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Andamento giornaliero del mese
            $stmt = $db->prepare("
                SELECT
                    DATE(s.created_at) as day,
                    COUNT(DISTINCT s.id) as sales_count,
                    SUM(si.line_total) as total_revenue,
                    SUM(si.qty * COALESCE(p.price_cost, 0)) as total_cost,
                    SUM(si.line_total) - SUM(si.qty * COALESCE(p.price_cost, 0)) as total_profit,
                    CASE
                        WHEN SUM(si.line_total) > 0
                        THEN ((SUM(si.line_total) - SUM(si.qty * COALESCE(p.price_cost, 0))) / SUM(si.line_total)) * 100
                        ELSE 0
                    END as margin_pct
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                WHERE s.store_id = ? AND p.store_id = s.store_id AND MONTH(s.created_at) = MONTH(CURDATE()) AND YEAR(s.created_at) = YEAR(CURDATE())
                GROUP BY DATE(s.created_at)
                ORDER BY DATE(s.created_at) DESC
            ");
            $stmt->execute([$storeId]);
            $daily_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

            successResponse(['monthly' => $monthly, 'top_products' => $top_products, 'daily_breakdown' => $daily_breakdown]);
        } else {
            errorResponse('Tipo report non valido');
        }
    } catch (Exception $e) {
        logError("Get report error: " . $e->getMessage());
        errorResponse('Errore nel recupero report', 500);
    }
} else {
    errorResponse('Metodo non supportato', 405);
}
?>