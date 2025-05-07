            <div class="dashboard-card">
                <h3>Pending Orders</h3>
                <div class="dashboard-card-content">
                    <?php
                    $pendingOrders = getPendingOrders($pdo);
                    if (count($pendingOrders) > 0): ?>
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['OrderID']; ?></td>
                                        <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['OrderDate'])); ?></td>
                                        <td><?php echo $order['ItemCount']; ?></td>
                                        <td>RM<?php echo number_format($order['OrderTotalAmount'], 2); ?></td>
                                        <td>
                                            <a href="orderList.php?view=<?php echo $order['OrderID']; ?>" class="dashboard-link">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No pending orders</p>
                    <?php endif; ?>
                </div>
            </div> 