<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';
    session_save_path($sessionDir);
    session_start();
}

include "config/db.php";

function e_d($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: landing.php"); exit; }

$stmt = mysqli_prepare($conn, "SELECT * FROM apartamente WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$apt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$apt) { header("Location: landing.php"); exit; }

$imgs = !empty($apt['images']) ? json_decode($apt['images'], true) : [];
$statusClass = $apt['status'] === 'liber' ? 'status-free' : 'status-occupied';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e_d($apt['adresa']) ?> – ApartaGest</title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
  <style>
    body.det { background: var(--bg); }
    .det-wrap {
      width: calc(100% - var(--menu-rail) - 64px);
      margin: 0 32px 0 calc(var(--menu-rail) + 32px);
      padding: 52px 0 80px;
    }

    /* ── BACK LINK ── */
    .det-back {
      display: inline-flex; align-items: center; gap: 8px;
      font-size: 14px; font-weight: 700; color: var(--muted);
      margin-bottom: 28px; transition: color .18s;
    }
    .det-back:hover { color: var(--primary); }

    /* ── GALLERY ── */
    .det-gallery { border-radius: 8px; overflow: hidden; background: #000; margin-bottom: 14px; position: relative; box-shadow: var(--shadow-soft); }
    .det-main-img {
      width: 100%; height: min(520px, 44vw); object-fit: cover; display: block;
      transition: opacity .3s ease;
    }
    .det-main-placeholder {
      width: 100%; height: min(520px, 44vw); display: flex; align-items: center;
      justify-content: center; font-size: 80px;
      background: linear-gradient(135deg,#667eea,#764ba2);
    }
    .det-gallery-nav {
      position: absolute; top: 50%; transform: translateY(-50%);
      width: 44px; height: 44px; border-radius: 50%;
      background: rgba(0,0,0,.5); color: #fff; border: none;
      font-size: 22px; cursor: pointer; display: flex;
      align-items: center; justify-content: center; transition: background .18s;
    }
    .det-gallery-nav:hover { background: rgba(0,0,0,.8); }
    .det-gallery-prev { left: 14px; }
    .det-gallery-next { right: 14px; }
    .det-img-counter {
      position: absolute; bottom: 14px; right: 14px;
      background: rgba(0,0,0,.55); color: #fff; border-radius: 20px;
      padding: 4px 12px; font-size: 13px; font-weight: 700;
    }

    /* ── THUMBNAILS ── */
    .det-thumbs { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 36px; }
    .det-thumb {
      width: 100px; height: 68px; border-radius: 8px; overflow: hidden;
      cursor: pointer; border: 2px solid transparent;
      transition: border-color .18s, opacity .18s; opacity: .6; flex-shrink: 0;
    }
    .det-thumb.active { border-color: var(--primary); opacity: 1; }
    .det-thumb:hover  { opacity: 1; }
    .det-thumb img    { width: 100%; height: 100%; object-fit: cover; display: block; }

    /* ── INFO GRID ── */
    .det-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 380px); gap: 28px; align-items: start; }
    .det-info-card {
      background: var(--panel); border: 1px solid var(--line);
      border-radius: 8px; padding: 32px; box-shadow: var(--shadow-soft);
    }
    .det-title { font-size: 26px; font-weight: 800; margin: 0 0 6px; color: var(--text); }
    .det-status { margin-bottom: 24px; }
    .det-props { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
    .det-prop {
      min-height: 92px; padding: 16px; border: 1px solid var(--line); border-radius: 8px;
      background: #f9fafb;
    }
    .det-prop-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; margin-bottom: 4px; }
    .det-prop-value { font-size: 18px; font-weight: 700; color: var(--text); }

    .det-price-card {
      background: var(--primary); border-radius: 8px; padding: 32px;
      color: #fff; box-shadow: 0 12px 32px rgba(37,99,235,.3);
    }
    .det-price-label { font-size: 13px; opacity: .8; margin-bottom: 6px; }
    .det-price-value { font-size: 42px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
    .det-price-sub   { font-size: 14px; opacity: .7; margin-bottom: 28px; }
    .det-cta-btn {
      display: block; width: 100%; padding: 14px;
      background: #fff; color: var(--primary); border: none; border-radius: 10px;
      font: inherit; font-size: 15px; font-weight: 700; cursor: pointer;
      text-align: center; text-decoration: none;
      transition: opacity .18s, transform .18s;
    }
    .det-cta-btn:hover { opacity: .92; transform: translateY(-1px); color: var(--primary); }
    .det-cta-btn + .det-cta-btn {
      margin-top: 10px; background: rgba(255,255,255,.15);
      color: #fff; border: 1px solid rgba(255,255,255,.3);
    }
    .det-cta-btn + .det-cta-btn:hover { background: rgba(255,255,255,.25); }

    @media(max-width:768px){
      .det-wrap { width: min(100% - 32px, 1120px); margin: 0 auto; padding: 24px 0 60px; }
      .det-main-img, .det-main-placeholder { height: 260px; }
      .det-layout { grid-template-columns: 1fr; }
      .det-props { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body class="det">

<!-- SIDEBAR -->
<aside class="app-menu">
  <div class="menu-brand">
    <span class="menu-brand-icon">&#127962;</span>
    <span class="menu-brand-text">ApartaGest</span>
  </div>
  <nav class="menu-nav">
    <a href="landing.php" title="Acas&#259;">
      <span class="menu-icon">&#127968;</span>
      <span class="menu-label">Acas&#259;</span>
    </a>
    <a href="landing.php#features" title="Func&#539;ionalit&#259;&#539;i">
      <span class="menu-icon">&#11088;</span>
      <span class="menu-label">Func&#539;ionalit&#259;&#539;i</span>
    </a>
    <a href="landing.php#apartments" title="Apartamente">
      <span class="menu-icon">&#127962;</span>
      <span class="menu-label">Apartamente</span>
    </a>
    <div class="menu-group">
      <span class="menu-section-title">Cont</span>
      <a href="login.php" title="Intr&#259; &#238;n cont">
        <span class="menu-icon">&#128274;</span>
        <span class="menu-label">Intr&#259; &#238;n cont</span>
      </a>
      <a href="register.php" title="&#206;nregistrare">
        <span class="menu-icon">&#10024;</span>
        <span class="menu-label">&#206;nregistrare</span>
      </a>
    </div>
  </nav>
</aside>

<div class="det-wrap">
  <a href="landing.php#apartments" class="det-back">&#8592; &#206;napoi la apartamente</a>

  <!-- GALLERY -->
  <?php if (!empty($imgs)): ?>
    <div class="det-gallery" id="detGallery">
      <img class="det-main-img" id="detMainImg" src="<?= e_d($imgs[0]) ?>" alt="Apartament foto">
      <?php if (count($imgs) > 1): ?>
        <button class="det-gallery-nav det-gallery-prev" onclick="detSlide(-1)">&#8249;</button>
        <button class="det-gallery-nav det-gallery-next" onclick="detSlide(1)">&#8250;</button>
      <?php endif; ?>
      <span class="det-img-counter" id="detCounter">1 / <?= count($imgs) ?></span>
    </div>
    <div class="det-thumbs">
      <?php foreach ($imgs as $j => $img): ?>
        <div class="det-thumb <?= $j === 0 ? 'active' : '' ?>" onclick="detGoTo(<?= $j ?>)">
          <img src="<?= e_d($img) ?>" alt="Foto <?= $j+1 ?>" loading="lazy">
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="det-gallery" style="margin-bottom:36px">
      <div class="det-main-placeholder">&#127968;</div>
    </div>
  <?php endif; ?>

  <!-- INFO -->
  <div class="det-layout">
    <div class="det-info-card">
      <h1 class="det-title"><?= e_d($apt['adresa']) ?></h1>
      <div class="det-status">
        <span class="status-pill <?= e_d($statusClass) ?>"><?= e_d(ucfirst($apt['status'])) ?></span>
      </div>
      <div class="det-props">
        <div class="det-prop">
          <div class="det-prop-label">Num&#259;r apartament</div>
          <div class="det-prop-value"><?= e_d($apt['numar_apartament'] ?? '-') ?></div>
        </div>
        <div class="det-prop">
          <div class="det-prop-label">Etaj</div>
          <div class="det-prop-value"><?= e_d($apt['etaj'] ?? '-') ?></div>
        </div>
        <div class="det-prop">
          <div class="det-prop-label">Num&#259;r camere</div>
          <div class="det-prop-value">&#128716; <?= e_d($apt['numar_camere']) ?></div>
        </div>
        <div class="det-prop">
          <div class="det-prop-label">Suprafa&#539;&#259;</div>
          <div class="det-prop-value">&#128207; <?= e_d($apt['suprafata']) ?> m&sup2;</div>
        </div>
      </div>
      <?php if (!empty($apt['observatii'])): ?>
        <div class="det-prop">
          <div class="det-prop-label">Observa&#539;ii / descriere</div>
          <div class="det-prop-value"><?= e_d($apt['observatii']) ?></div>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <div class="det-price-card">
        <div class="det-price-label">Chirie lunar&#259;</div>
        <div class="det-price-value"><?= e_d($apt['chirie']) ?></div>
        <div class="det-price-sub">lei / lun&#259;</div>
        <a href="register.php" class="det-cta-btn">Creeaz&#259; cont pentru a contacta</a>
        <a href="login.php" class="det-cta-btn">Am deja cont</a>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($imgs)): ?>
<script>
(function () {
  var imgs    = <?= json_encode($imgs) ?>;
  var total   = imgs.length;
  var current = 0;

  function goTo(idx) {
    current = (idx + total) % total;
    var mainImg = document.getElementById('detMainImg');
    mainImg.style.opacity = '0';
    setTimeout(function () {
      mainImg.src = imgs[current];
      mainImg.style.opacity = '1';
    }, 150);
    document.getElementById('detCounter').textContent = (current + 1) + ' / ' + total;
    document.querySelectorAll('.det-thumb').forEach(function (t, i) {
      t.classList.toggle('active', i === current);
    });
  }

  window.detSlide = function (dir) { goTo(current + dir); };
  window.detGoTo  = function (idx) { goTo(idx); };
})();
</script>
<?php endif; ?>
</body>
</html>
