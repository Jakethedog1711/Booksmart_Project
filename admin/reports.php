<?php
// admin/reports.php
require_once '../includes/db_connect.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$report_type = $_GET['type'] ?? 'sales'; // Default to sales report
$report_data = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = sanitize_input($_POST['report_type']);
}

// --- Sales Report ---
if ($report_type === 'sales') {
    $start_date = $_POST['start_date'] ?? date('Y-m-01'); // Default to start of current month
    $end_date = $_POST['end_date'] ?? date('Y-m-t');     // Default to end of current month

    $sql = "SELECT
                DATE(o.Date_of_Purchase) AS SaleDate,
                SUM(oi.Quantity * oi.Price_at_Purchase) AS TotalSales,
                SUM(oi.Quantity) AS TotalItemsSold,
                COUNT(DISTINCT o.OrderID) AS TotalOrders
            FROM ORDERS o
            JOIN ORDERITEMS oi ON o.OrderID = oi.OrderID
            WHERE DATE(o.Date_of_Purchase) BETWEEN ? AND ?
            GROUP BY SaleDate
            ORDER BY SaleDate ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger">Database error for sales report: ' . $conn->error . '</div>';
    }
}
// --- Add other report types (e.g., inventory, customer) here ---
// Example: Inventory Report
elseif ($report_type === 'inventory') {
    $sql = "SELECT Product_Name, ProductType, Nr_books FROM PRODUCTS ORDER BY Nr_books ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    } else {
        $message = '<div class="alert alert-danger">Database error for inventory report: ' . $conn->error . '</div>';
    }
}
// Example: Top Customers Report
elseif ($report_type === 'customers') {
    $sql = "SELECT u.First_Name, u.Last_Name, u.Email_Address, COUNT(o.OrderID) AS TotalOrders, SUM(oi.Quantity * oi.Price_at_Purchase) AS TotalSpent
            FROM USERS u
            JOIN CUSTOMERS c ON u.UserID = c.UserID
            LEFT JOIN ORDERS o ON u.UserID = o.UserID
            LEFT JOIN ORDERITEMS oi ON o.OrderID = oi.OrderID
            GROUP BY u.UserID
            ORDER BY TotalSpent DESC
            LIMIT 10"; // Top 10 customers
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    } else {
        $message = '<div class="alert alert-danger">Database error for customer report: ' . $conn->error . '</div>';
    }
}


require_once '../includes/header.php';
?>

<h2 class="mb-4">Reports</h2>

<?php echo $message; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        Generate Report
    </div>
    <div class="card-body">
        <form action="reports.php" method="POST">
            <div class="mb-3">
                <label for="report_type" class="form-label">Select Report Type</label>
                <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                    <option value="sales" <?php echo ($report_type === 'sales') ? 'selected' : ''; ?>>Sales Report</option>
                    <option value="inventory" <?php echo ($report_type === 'inventory') ? 'selected' : ''; ?>>Inventory Report</option>
                    <option value="customers" <?php echo ($report_type === 'customers') ? 'selected' : ''; ?>>Top Customers Report</option>
                </select>
            </div>
            <?php if ($report_type === 'sales'): ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-info">Generate Sales Report</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($report_type === 'sales'): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            Sales Report (<?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>)
        </div>
        <div class="card-body">
            <?php if (!empty($report_data)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Sales</th>
                                <th>Total Items Sold</th>
                                <th>Total Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['SaleDate']); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($row['TotalSales'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($row['TotalItemsSold']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TotalOrders']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No sales data for the selected period.</p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($report_type === 'inventory'): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            Inventory Report
        </div>
        <div class="card-body">
            <?php if (!empty($report_data)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Product Type</th>
                                <th>Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['Product_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ProductType']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Nr_books']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No products found for inventory report.</p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($report_type === 'customers'): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            Top Customers Report
        </div>
        <div class="card-body">
            <?php if (!empty($report_data)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Email_Address']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TotalOrders'] ?? 0); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($row['TotalSpent'] ?? 0, 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No customer data found for this report.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
