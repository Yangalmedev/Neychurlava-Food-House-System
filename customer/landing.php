<?php
require_once '../config/db.php';
// Fetch 6 popular items for the landing page
$popular = $conn->query("
    SELECT mi.item_id, mi.item_name, mi.price, mi.availability, cat.category_name
    FROM Menu_Item mi
    INNER JOIN Category cat ON mi.category_id = cat.category_id
    WHERE mi.availability = 'Available'
    ORDER BY mi.item_id ASC
    LIMIT 6
");
$emoji_map = [
    'Rice Meals'=>'🍚','Soups and Stews'=>'🍲','Grilled Dishes'=>'🥩',
    'Fried Dishes'=>'🍳','Pasta and Noodles'=>'🍜','Beverages'=>'☕',
    'Desserts'=>'🍮','Snacks and Appetizers'=>'🥟','Breakfast Meals'=>'🍳',
    'Seafood Dishes'=>'🐟','Vegetable Dishes'=>'🥦','Sandwiches and Burgers'=>'🥪',
    'Kakanin'=>'🍡','Pulutan'=>'🍖','Sizzling Dishes'=>'🔥',
    'Pork Specialties'=>'🥓','Chicken Specialties'=>'🍗',
    'Combo Meals'=>'🍱','Add-ons and Extras'=>'➕','Seasonal Specials'=>'⭐',
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Neychurlava Food-House — Order Online</title>
<link rel="stylesheet" href="/neychurlava/assets/css/customer.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="c-nav">
  <a href="/neychurlava/customer/landing.php" class="c-nav-brand">
    <span>🍽️</span> Neychurlava
  </a>
  <div class="c-nav-links">
    <a href="/neychurlava/customer/landing.php" class="active">Home</a>
    <a href="/neychurlava/customer/menu.php">Menu</a>
    <a href="/neychurlava/customer/track.php">Track Order</a>
  </div>
  <div class="c-nav-actions">
    <button class="c-cart-btn" onclick="window.location='/neychurlava/customer/menu.php'">
      🛒 Cart <span class="c-cart-badge" style="display:none">0</span>
    </button>
  </div>
</nav>

<!-- HERO -->
<section class="c-hero">
  <div class="c-hero-content">
    <div class="c-hero-tag">⚡ Fast. Fresh. Delicious.</div>
    <h1>Order Fresh,<br><span>Eat Happy</span> 🍽️</h1>
    <p>Real Filipino food from Neychurlava Food-House — delivered to your door or ready for pickup in minutes.</p>
    <div class="c-hero-actions">
      <a href="/neychurlava/customer/menu.php" class="btn btn-primary">🛒 Order Now</a>
      <a href="#how" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)">How it works</a>
    </div>
  </div>
  <div class="c-hero-visual">
    <?php
    $hero_foods = ['🍚 Rice Meals','🍜 Noodles','🥩 Grilled','🍗 Chicken','🐟 Seafood','🍮 Desserts'];
    foreach ($hero_foods as $f): [$em,$lab] = explode(' ',$f,2); ?>
    <div class="c-food-card">
      <div class="emoji"><?= $em ?></div>
      <span><?= $lab ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- BANNERS -->
<section class="c-banners">
  <h2>Why Order From Us?</h2>
  <p class="c-banners-sub">Hot food, straight from our kitchen — your way.</p>
  <div class="c-banner-grid">
    <div class="c-banner coral">
      <div class="b-icon">🚴</div>
      <h3>Fast Delivery</h3>
      <p>We deliver to nearby barangays in Abuyog. Cash on Delivery — no cards, no hassle. Pay when it arrives.</p>
    </div>
    <div class="c-banner mint">
      <div class="b-icon">🥡</div>
      <h3>Quick Takeout</h3>
      <p>Order ahead and pick up at the counter. Skip the wait — your food is ready when you arrive.</p>
    </div>
    <div class="c-banner gold">
      <div class="b-icon">🍳</div>
      <h3>Fresh Daily</h3>
      <p>Every dish is cooked fresh to order using quality local ingredients. No reheated food, ever.</p>
    </div>
  </div>
</section>

<!-- POPULAR ITEMS -->
<section class="c-menu-section" style="padding-bottom:0">
  <div class="sec-hdr">
    <h2>Popular Items</h2>
    <p>Our customers' most-loved dishes — ready to order right now.</p>
  </div>
  <div class="menu-grid">
  <?php while ($r = $popular->fetch_assoc()):
    $em = $emoji_map[$r['category_name']] ?? '🍽️';
  ?>
    <div class="menu-card">
      <div class="menu-card-img"><?= $em ?></div>
      <div class="menu-card-body">
        <div class="menu-card-cat"><?= htmlspecialchars($r['category_name']) ?></div>
        <div class="menu-card-name"><?= htmlspecialchars($r['item_name']) ?></div>
        <div class="menu-card-footer">
          <div class="menu-card-price">₱<?= number_format($r['price'],2) ?></div>
          <a href="/neychurlava/customer/menu.php" class="add-btn" title="Go to menu">+</a>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
  </div>
  <div style="text-align:center;padding:40px 0">
    <a href="/neychurlava/customer/menu.php" class="btn btn-primary" style="font-size:1.05rem;padding:16px 40px">
      View Full Menu 🍜
    </a>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section" id="how">
  <h2>How to Order</h2>
  <p>Three simple steps and your food is on the way.</p>
  <div class="how-grid">
    <div class="how-step">
      <div class="how-num">🍜</div>
      <h3>1. Browse the Menu</h3>
      <p>Choose from our full menu of Filipino dishes. Add anything you want to your cart.</p>
    </div>
    <div class="how-step">
      <div class="how-num">📋</div>
      <h3>2. Choose Delivery or Takeout</h3>
      <p>Pick delivery to your address or takeout for counter pickup. Fill in the details.</p>
    </div>
    <div class="how-step">
      <div class="how-num">✅</div>
      <h3>3. Confirm & Wait</h3>
      <p>Place your order and get a confirmation code. Track your order status anytime.</p>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="c-footer">
  <p>🍽️ <strong>Neychurlava Food-House</strong> · Abuyog, Leyte · Open daily</p>
  <p style="margin-top:6px">Delivery within Abuyog and nearby barangays · Cash only</p>
</footer>

<script src="/neychurlava/assets/js/cart.js"></script>
</body></html>
