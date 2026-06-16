<?php
require_once '../config/db.php';

$emoji_map = [
    'Rice Meals'=>'🍚','Soups and Stews'=>'🍲','Grilled Dishes'=>'🥩',
    'Fried Dishes'=>'🍳','Pasta and Noodles'=>'🍜','Beverages'=>'☕',
    'Desserts'=>'🍮','Snacks and Appetizers'=>'🥟','Breakfast Meals'=>'🍳',
    'Seafood Dishes'=>'🐟','Vegetable Dishes'=>'🥦','Sandwiches and Burgers'=>'🥪',
    'Kakanin'=>'🍡','Pulutan'=>'🍖','Sizzling Dishes'=>'🔥',
    'Pork Specialties'=>'🥓','Chicken Specialties'=>'🍗',
    'Combo Meals'=>'🍱','Add-ons and Extras'=>'➕','Seasonal Specials'=>'⭐',
];

$categories = $conn->query("SELECT * FROM Category ORDER BY category_name");
$items_res  = $conn->query("
    SELECT mi.item_id, mi.item_name, mi.description, mi.price,
           mi.availability, mi.item_type, cat.category_id, cat.category_name
    FROM Menu_Item mi
    INNER JOIN Category cat ON mi.category_id = cat.category_id
    ORDER BY cat.category_name, mi.item_name
");
$items = [];
while ($r = $items_res->fetch_assoc()) $items[] = $r;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Menu — Neychurlava Food-House</title>
<link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="c-nav">
  <a href="/neychurlava/customer/landing.php" class="c-nav-brand">
    <span>🍽️</span> Neychurlava
  </a>
  <div class="c-nav-links">
    <a href="/neychurlava/customer/landing.php">Home</a>
    <a href="/neychurlava/customer/menu.php" class="active">Menu</a>
    <a href="/neychurlava/customer/track.php">Track Order</a>
  </div>
  <div class="c-nav-actions">
    <button class="c-cart-btn" onclick="openCart()">
      🛒 Cart <span class="c-cart-badge" style="display:none">0</span>
    </button>
  </div>
</nav>

<!-- CART OVERLAY + DRAWER -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-head">
    <h3>🛒 Your Cart</h3>
    <button class="cart-close" onclick="closeCart()">×</button>
  </div>
  <div class="cart-items" id="cartItems"></div>
  <div class="cart-footer">
    <div class="cart-total">
      <span class="label">Total</span>
      <span class="amount" id="cartTotalAmt">₱0.00</span>
    </div>
    <button class="btn btn-primary btn-full" onclick="goCheckout()">
      Proceed to Checkout →
    </button>
  </div>
</div>

<!-- MENU SECTION -->
<section class="c-menu-section">
  <div class="sec-hdr">
    <h2>Our Menu</h2>
    <p>Fresh Filipino dishes cooked to order — add anything to your cart.</p>
  </div>

  <!-- CATEGORY TABS -->
  <div class="cat-tabs">
    <button class="cat-tab active" onclick="filterCat('all',this)">🍽️ All</button>
    <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
    <button class="cat-tab" data-cat="<?= $cat['category_id'] ?>"
      onclick="filterCat('<?= $cat['category_id'] ?>',this)">
      <?= ($emoji_map[$cat['category_name']] ?? '🍴') . ' ' . htmlspecialchars($cat['category_name']) ?>
    </button>
    <?php endwhile; ?>
  </div>

  <!-- MENU GRID -->
  <div class="menu-grid" id="menuGrid">
  <?php foreach ($items as $r):
    $em  = $emoji_map[$r['category_name']] ?? '🍽️';
    $avl = $r['availability'] === 'Available';
    $desc = $r['description'] ? substr($r['description'], 0, 80).'…' : '';
  ?>
    <div class="menu-card <?= $avl?'':'unavailable' ?>"
         data-cat="<?= $r['category_id'] ?>">
      <div class="menu-card-img"><?= $em ?></div>
      <div class="menu-card-body">
        <div class="menu-card-cat">
          <?= htmlspecialchars($r['category_name']) ?>
          <?php if (!$avl): ?><span class="unavail-badge">Unavailable</span><?php endif; ?>
        </div>
        <div class="menu-card-name"><?= htmlspecialchars($r['item_name']) ?></div>
        <?php if ($desc): ?>
        <div class="menu-card-desc"><?= htmlspecialchars($desc) ?></div>
        <?php endif; ?>
        <div class="menu-card-footer">
          <div class="menu-card-price">₱<?= number_format($r['price'],2) ?></div>
          <?php if ($avl): ?>
          <button class="add-btn" data-item-id="<?= $r['item_id'] ?>"
            onclick="addToCart(
              <?= $r['item_id'] ?>,
              '<?= htmlspecialchars(addslashes($r['item_name'])) ?>',
              <?= $r['price'] ?>,
              '<?= $em ?>'
            )">+</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</section>

<footer class="c-footer">
  <p>🍽️ Neychurlava Food-House · Abuyog, Leyte</p>
</footer>

<script src="/neychurlava/assets/js/cart.js"></script>
<script>
function filterCat(catId, btn) {
  document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.menu-card').forEach(card => {
    card.style.display = (catId === 'all' || card.dataset.cat === String(catId)) ? '' : 'none';
  });
}
function goCheckout() {
  if (cartCount() === 0) { alert('Please add items to your cart first!'); return; }
  window.location = '/neychurlava/customer/checkout.php';
}
</script>
</body></html>
