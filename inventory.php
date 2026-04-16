<?php
// inventory.php

// --- Database Configuration ---
$host = 'localhost';
$dbname = 'inventory_db';
$user = 'root'; // Update with your MySQL user
$pass = '';     // Update with your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Note: Do not expose raw exception in production
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// --- Create Data Table if not exists ---
$pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// --- Handle Form Submission ---
$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = (float)($_POST['price'] ?? 0.0);

    if ($name !== '' && $quantity >= 0 && $price >= 0) {
        $stmt = $pdo->prepare('INSERT INTO products (name, quantity, price) VALUES (?, ?, ?)');
        if ($stmt->execute([$name, $quantity, $price])) {
            $message = 'Product added successfully!';
        } else {
            $message = 'Failed to add product.';
            $isError = true;
        }
    } else {
        $message = 'Invalid input. Please check your data.';
        $isError = true;
    }
}

// --- Fetch Products for Dashboard ---
$stmt = $pdo->query('SELECT id, name, quantity, price, created_at FROM products ORDER BY id DESC');
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Inventory System</title>
    <style>
        :root {
            --primary-color: #3b82f6; /* Blue */
            --primary-hover: #2563eb;
            --bg-color: #f3f4f6;
            --text-color: #1f2937;
            --border-color: #e5e7eb;
            --card-bg: #ffffff;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .container {
            max-width: 1100px;
            width: 100%;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            padding: 2.5rem;
            box-sizing: border-box;
        }

        @media (max-width: 860px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        h1, h2 {
            margin-top: 0;
            font-weight: 600;
            color: #111827;
        }

        .form-section, .table-section {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #374151;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.2s ease-in-out;
            background-color: #f9fafb;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            margin-top: 1rem;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            text-align: left;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #4b5563;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.75em;
            font-size: 0.85em;
            font-weight: 600;
            border-radius: 9999px;
            background-color: #dbeafe;
            color: #1e40af;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
            background: #f9fafb;
            border-radius: 8px;
            border: 2px dashed #d1d5db;
        }
        
        /* Subtle transition for table rows */
        tbody tr {
            transition: background-color 150ms ease-in-out;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Form Section -->
    <div class="form-section">
        <h2>Add Product</h2>
        
        <?php if ($message): ?>
            <div class="alert <?= $isError ? 'alert-error' : 'alert-success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required placeholder="e.g., Wireless Mouse">
            </div>

            <div class="form-group">
                <label for="quantity">Quantity in Stock</label>
                <input type="number" id="quantity" name="quantity" required min="0" placeholder="0">
            </div>

            <div class="form-group">
                <label for="price">Price per Unit ($)</label>
                <input type="number" id="price" name="price" required min="0" step="0.01" placeholder="0.00">
            </div>

            <button type="submit">Save Product</button>
        </form>
    </div>

    <!-- Table Section -->
    <div class="table-section">
        <h2>Inventory Dashboard</h2>
        
        <?php if (count($products) > 0): ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><span class="badge">#<?= htmlspecialchars($product['id']) ?></span></td>
                                <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['quantity']) ?></td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg style="margin: 0 auto 1rem; width: 48px; height: 48px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <p>No products found. Add your first product to see it here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
