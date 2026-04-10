<?php
// ─────────────────────────────────────────
//  LuxeBags — Admin Dashboard
//  Visit: yoursite.com/admin.php
//  Password protected
// ─────────────────────────────────────────

require_once 'config.php';

session_start();

// ── Handle login / logout ─────────────────
if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['luxe_admin'] = true;
    } else {
        $loginError = 'Incorrect password. Please try again.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Handle order status update ────────────
if (isset($_POST['update_order']) && $_SESSION['luxe_admin']) {
    $orderId   = sanitize($_POST['order_id']);
    $newStatus = sanitize($_POST['new_status']);
    $orders    = read_json(ORDERS_FILE);
    foreach ($orders as &$o) {
        if ($o['id'] === $orderId) { $o['status'] = $newStatus; break; }
    }
    write_json(ORDERS_FILE, $orders);
    header('Location: admin.php?tab=orders&updated=1');
    exit;
}

// ── Handle mark message as read ──────────
if (isset($_POST['mark_read']) && $_SESSION['luxe_admin']) {
    $msgId    = sanitize($_POST['msg_id']);
    $messages = read_json(MESSAGES_FILE);
    foreach ($messages as &$m) {
        if ($m['id'] === $msgId) { $m['status'] = 'read'; break; }
    }
    write_json(MESSAGES_FILE, $messages);
    header('Location: admin.php?tab=messages');
    exit;
}

$isLoggedIn = !empty($_SESSION['luxe_admin']);
$tab        = $_GET['tab'] ?? 'orders';
$orders     = $isLoggedIn ? array_reverse(read_json(ORDERS_FILE))   : [];
$messages   = $isLoggedIn ? array_reverse(read_json(MESSAGES_FILE)) : [];
$unreadCount = count(array_filter($messages, fn($m) => $m['status'] === 'unread'));

$statusColors = [
    'pending'   => '#c9a84c',
    'confirmed' => '#4c9fc9',
    'shipped'   => '#9c4cc9',
    'delivered' => '#4caf7a',
    'cancelled' => '#e05252',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>LuxeBags Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --black: #0a0a0a; --off-white: #f5f0ea;
      --gold: #c9a84c; --gold-light: #e8c96a; --charcoal: #1c1c1e;
      --muted: #6b6660; --card: #161614; --border: rgba(201,168,76,0.2);
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--black); color: var(--off-white); min-height: 100vh; }

    /* LOGIN */
    .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(ellipse at center, #1a1608 0%, #0a0a0a 70%); }
    .login-box { background: var(--card); border: 1px solid var(--border); padding: 3rem 2.5rem; width: 360px; text-align: center; }
    .login-logo { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; letter-spacing: 0.12em; color: var(--gold); margin-bottom: 0.3rem; }
    .login-sub { font-size: 0.78rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-bottom: 2rem; }
    .login-box input { width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--off-white); padding: 0.9rem 1rem; font-family: 'DM Sans', sans-serif; font-size: 0.9rem; outline: none; margin-bottom: 1rem; text-align: center; letter-spacing: 0.1em; }
    .login-box input:focus { border-color: var(--gold); }
    .login-btn { width: 100%; background: var(--gold); color: var(--black); border: none; padding: 0.9rem; font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.2em; text-transform: uppercase; cursor: pointer; transition: background 0.2s; }
    .login-btn:hover { background: var(--gold-light); }
    .login-error { color: #e05252; font-size: 0.82rem; margin-bottom: 1rem; }

    /* ADMIN LAYOUT */
    .admin-layout { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
    .sidebar { background: var(--card); border-right: 1px solid var(--border); padding: 2rem 0; }
    .sidebar-logo { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: 0.1em; color: var(--gold); padding: 0 1.5rem; margin-bottom: 0.3rem; }
    .sidebar-sub { font-size: 0.68rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); padding: 0 1.5rem; margin-bottom: 2rem; }
    .sidebar-nav { list-style: none; }
    .sidebar-nav li a { display: flex; align-items: center; gap: 0.8rem; padding: 0.85rem 1.5rem; color: var(--muted); text-decoration: none; font-size: 0.82rem; letter-spacing: 0.1em; text-transform: uppercase; transition: all 0.2s; border-left: 3px solid transparent; }
    .sidebar-nav li a:hover { color: var(--off-white); background: rgba(255,255,255,0.03); }
    .sidebar-nav li a.active { color: var(--gold); border-left-color: var(--gold); background: rgba(201,168,76,0.06); }
    .badge-count { background: var(--gold); color: var(--black); font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 10px; }
    .sidebar-footer { padding: 1.5rem; margin-top: auto; border-top: 1px solid var(--border); }
    .logout-link { color: var(--muted); text-decoration: none; font-size: 0.78rem; letter-spacing: 0.1em; text-transform: uppercase; transition: color 0.2s; }
    .logout-link:hover { color: #e05252; }

    /* MAIN CONTENT */
    .main { padding: 2.5rem 3rem; overflow-x: auto; }
    .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 0.08em; color: var(--off-white); margin-bottom: 0.3rem; }
    .page-sub { font-size: 0.82rem; color: var(--muted); margin-bottom: 2rem; }

    /* STATS */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2rem; margin-bottom: 2.5rem; }
    .stat-card { background: var(--card); border: 1px solid var(--border); padding: 1.5rem; border-top: 3px solid var(--gold); }
    .stat-label { font-size: 0.72rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.5rem; }
    .stat-val { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; letter-spacing: 0.06em; color: var(--gold); }
    .stat-sub { font-size: 0.75rem; color: var(--muted); margin-top: 0.3rem; }

    /* TABLE */
    .table-wrap { background: var(--card); border: 1px solid var(--border); overflow-x: auto; }
    .table-header { padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .table-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 0.1em; }
    table { width: 100%; border-collapse: collapse; }
    th { font-size: 0.7rem; letter-spacing: 0.2em; text-transform: uppercase; color: var(--muted); padding: 1rem 1.2rem; text-align: left; border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); }
    td { padding: 1rem 1.2rem; font-size: 0.85rem; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: top; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255,255,255,0.02); }

    .status-badge { display: inline-block; padding: 0.25rem 0.7rem; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; }
    .order-num { font-family: 'Bebas Neue', sans-serif; font-size: 1rem; letter-spacing: 0.1em; color: var(--gold); }
    .customer-name { font-weight: 500; }
    .customer-email { font-size: 0.75rem; color: var(--muted); }

    /* Status form */
    .status-form { display: flex; gap: 0.5rem; align-items: center; }
    .status-select { background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--off-white); padding: 0.3rem 0.6rem; font-size: 0.75rem; font-family: 'DM Sans', sans-serif; cursor: pointer; outline: none; }
    .update-btn { background: var(--gold); color: var(--black); border: none; padding: 0.3rem 0.8rem; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background 0.2s; }
    .update-btn:hover { background: var(--gold-light); }

    /* Cart items in table */
    .cart-items-list { font-size: 0.78rem; color: var(--muted); }
    .cart-items-list span { display: block; }

    /* Messages */
    .msg-card { background: var(--card); border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1rem; border-left: 3px solid var(--gold); }
    .msg-card.read { border-left-color: rgba(255,255,255,0.1); opacity: 0.7; }
    .msg-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; flex-wrap: wrap; gap: 0.5rem; }
    .msg-name { font-weight: 600; font-size: 0.95rem; }
    .msg-email { font-size: 0.78rem; color: var(--muted); }
    .msg-date { font-size: 0.75rem; color: var(--muted); }
    .msg-body { font-size: 0.88rem; color: var(--off-white); line-height: 1.65; background: rgba(255,255,255,0.03); padding: 1rem; margin-bottom: 0.8rem; }
    .msg-phone { font-size: 0.78rem; color: var(--muted); }
    .read-btn { background: transparent; border: 1px solid var(--border); color: var(--muted); padding: 0.3rem 0.9rem; font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all 0.2s; }
    .read-btn:hover { border-color: var(--gold); color: var(--gold); }

    .empty-state { text-align: center; padding: 4rem; color: var(--muted); }
    .empty-state p { font-size: 0.9rem; }
    .success-bar { background: rgba(76,175,122,0.15); border: 1px solid rgba(76,175,122,0.3); color: #4caf7a; padding: 0.8rem 1.2rem; font-size: 0.82rem; margin-bottom: 1.5rem; }
  </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- LOGIN SCREEN -->
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">LuxeBags</div>
    <div class="login-sub">Admin Dashboard</div>
    <?php if (!empty($loginError)): ?>
      <p class="login-error"><?= $loginError ?></p>
    <?php endif; ?>
    <form method="POST">
      <input type="password" name="password" placeholder="Enter admin password" autofocus/>
      <button type="submit" class="login-btn">Login</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ADMIN DASHBOARD -->
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">LuxeBags</div>
    <div class="sidebar-sub">Admin Panel</div>
    <ul class="sidebar-nav">
      <li><a href="admin.php?tab=orders" class="<?= $tab==='orders'?'active':'' ?>">📦 Orders (<?= count($orders) ?>)</a></li>
      <li><a href="admin.php?tab=messages" class="<?= $tab==='messages'?'active':'' ?>">
        💬 Messages
        <?php if ($unreadCount > 0): ?><span class="badge-count"><?= $unreadCount ?></span><?php endif; ?>
      </a></li>
      <li><a href="index.html">🌐 View Website</a></li>
    </ul>
    <div class="sidebar-footer">
      <a href="admin.php?logout=1" class="logout-link">⇠ Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <?php if (isset($_GET['updated'])): ?>
      <div class="success-bar">✅ Order status updated successfully.</div>
    <?php endif; ?>

    <?php if ($tab === 'orders'): ?>
    <!-- ORDERS TAB -->
    <?php
      $pending   = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
      $delivered = count(array_filter($orders, fn($o) => $o['status'] === 'delivered'));
      $totalRev  = array_sum(array_map(fn($o) => (float) preg_replace('/[^0-9.]/', '', $o['total']), $orders));
    ?>
    <div class="page-title">Orders</div>
    <p class="page-sub">All customer orders submitted through the checkout page.</p>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-val"><?= count($orders) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-val"><?= $pending ?></div>
        <div class="stat-sub">Awaiting confirmation</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Delivered</div>
        <div class="stat-val"><?= $delivered ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Revenue</div>
        <div class="stat-val">KSh <?= number_format($totalRev) ?></div>
      </div>
    </div>

    <div class="table-wrap">
      <div class="table-header">
        <span class="table-title">All Orders</span>
      </div>
      <?php if (empty($orders)): ?>
        <div class="empty-state"><p>No orders yet. They will appear here once customers checkout.</p></div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Delivery</th>
            <th>Items</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
          <tr>
            <td><span class="order-num"><?= $order['orderNumber'] ?></span></td>
            <td>
              <div class="customer-name"><?= $order['customer']['fullName'] ?></div>
              <div class="customer-email"><?= $order['customer']['email'] ?></div>
              <div class="customer-email"><?= $order['customer']['phone'] ?></div>
            </td>
            <td>
              <div><?= $order['delivery']['address'] ?></div>
              <div class="customer-email"><?= $order['delivery']['city'] ?>, <?= $order['delivery']['county'] ?></div>
            </td>
            <td>
              <div class="cart-items-list">
                <?php foreach ($order['cart'] as $item): ?>
                  <span>• <?= $item['name'] ?> ×<?= $item['qty'] ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td style="font-weight:600;color:var(--gold)">KSh <?= $order['total'] ?></td>
            <td><?= ucfirst($order['payment']['method']) ?>
              <?php if ($order['payment']['method'] === 'mpesa' && $order['payment']['mpesaPhone']): ?>
                <div class="customer-email"><?= $order['payment']['mpesaPhone'] ?></div>
              <?php endif; ?>
            </td>
            <td class="customer-email"><?= $order['placedAt'] ?></td>
            <td>
              <?php $color = $statusColors[$order['status']] ?? '#999'; ?>
              <span class="status-badge" style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>44">
                <?= ucfirst($order['status']) ?>
              </span>
              <form method="POST" class="status-form" style="margin-top:0.5rem">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>"/>
                <input type="hidden" name="update_order" value="1"/>
                <select name="new_status" class="status-select">
                  <option value="pending"   <?= $order['status']==='pending'  ?'selected':'' ?>>Pending</option>
                  <option value="confirmed" <?= $order['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                  <option value="shipped"   <?= $order['status']==='shipped'  ?'selected':'' ?>>Shipped</option>
                  <option value="delivered" <?= $order['status']==='delivered'?'selected':'' ?>>Delivered</option>
                  <option value="cancelled" <?= $order['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
                <button type="submit" class="update-btn">Update</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <?php elseif ($tab === 'messages'): ?>
    <!-- MESSAGES TAB -->
    <div class="page-title">Messages</div>
    <p class="page-sub">Contact form submissions from your website visitors.</p>

    <?php if (empty($messages)): ?>
      <div class="empty-state"><p>No messages yet.</p></div>
    <?php else: ?>
      <?php foreach ($messages as $msg): ?>
      <div class="msg-card <?= $msg['status'] === 'read' ? 'read' : '' ?>">
        <div class="msg-header">
          <div>
            <div class="msg-name"><?= $msg['fullName'] ?> <?= $msg['status']==='unread' ? '<span style="background:var(--gold);color:#000;font-size:0.65rem;padding:0.1rem 0.4rem;font-weight:700;letter-spacing:0.1em">NEW</span>' : '' ?></div>
            <div class="msg-email"><?= $msg['email'] ?> <?= $msg['phone'] ? '· ' . $msg['phone'] : '' ?></div>
          </div>
          <div class="msg-date"><?= $msg['receivedAt'] ?></div>
        </div>
        <div class="msg-body"><?= nl2br($msg['message']) ?></div>
        <?php if ($msg['status'] === 'unread'): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="msg_id" value="<?= $msg['id'] ?>"/>
          <input type="hidden" name="mark_read" value="1"/>
          <button type="submit" class="read-btn">✓ Mark as Read</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php endif; ?>
  </main>
</div>
<?php endif; ?>

</body>
</html>
