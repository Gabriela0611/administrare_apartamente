<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/sessions';
    session_save_path($sessionDir);
    session_start();
}

if (!empty($_SESSION['user_logged_in'])) {
    header("Location: dashboard.php");
    exit;
}

include "config/db.php";

function e_land($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$totalApartamente = 0;
$totalChiriasi   = 0;
$totalLibere     = 0;
$apartamente     = [];

$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM apartamente");
if ($r) $totalApartamente = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);

$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM chiriasi");
if ($r) $totalChiriasi = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);

$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM apartamente WHERE status = 'liber'");
if ($r) $totalLibere = (int)(mysqli_fetch_assoc($r)['total'] ?? 0);

$r = mysqli_query($conn, "SELECT * FROM apartamente ORDER BY id DESC LIMIT 6");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $apartamente[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ApartaGest – Administrare apartamente moderne</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body.lp { background: #fff; }

    /* All landing sections sit to the right of the rail */
    .lp-wrap { margin-left: var(--menu-rail); }

    /* ── HERO ────────────────────────────────────────── */
    .lp-hero {
      min-height: 100vh; display: flex; align-items: center;
      position: relative; overflow: hidden;
      background: linear-gradient(135deg,#0f172a 0%,#1e3a8a 45%,#2563eb 75%,#3b82f6 100%);
    }
    .lp-hero::before {
      content: ''; position: absolute; inset: 0;
      background:
        radial-gradient(ellipse at 72% 48%, rgba(99,102,241,.3) 0%, transparent 60%),
        radial-gradient(ellipse at 18% 80%, rgba(59,130,246,.2) 0%, transparent 50%);
    }
    .lp-blob {
      position: absolute; border-radius: 50%; background: rgba(255,255,255,.07);
    }
    .lp-blob-1 { width: 460px; height: 460px; top: -120px; right: -100px; }
    .lp-blob-2 { width: 220px; height: 220px; bottom: 60px; left: 8%; }
    .lp-blob-3 { width: 110px; height: 110px; top: 35%; right: 18%; }

    .lp-hero-inner {
      position: relative; max-width: 1100px; margin: 0 auto;
      padding: 80px 48px; width: 100%;
      display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center;
    }
    .lp-hero-title {
      font-size: clamp(36px,5vw,60px); font-weight: 800; line-height: 1.1;
      color: #fff; margin: 0 0 20px;
    }
    .lp-hero-title span {
      background: linear-gradient(90deg,#93c5fd,#c4b5fd);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .lp-hero-sub {
      font-size: 18px; color: rgba(255,255,255,.75); margin: 0 0 38px; line-height: 1.7;
    }
    .lp-hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }

    .btn-hero {
      min-height: 52px; padding: 0 28px; border-radius: 12px; border: none;
      display: inline-flex; align-items: center; gap: 8px;
      font: inherit; font-size: 16px; font-weight: 700; cursor: pointer;
      text-decoration: none; transition: transform .2s, box-shadow .2s;
    }
    .btn-hero:hover { transform: translateY(-2px); }
    .btn-hero-white { background: #fff; color: var(--primary); }
    .btn-hero-white:hover { box-shadow: 0 12px 32px rgba(0,0,0,.18); color: var(--primary-dark); }
    .btn-hero-ghost {
      background: rgba(255,255,255,.12); color: #fff;
      border: 1px solid rgba(255,255,255,.3); backdrop-filter: blur(4px);
    }
    .btn-hero-ghost:hover { background: rgba(255,255,255,.22); color: #fff; }

    /* Floating preview cards */
    .lp-preview-cards { display: grid; gap: 14px; }
    .lp-preview-card {
      background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
      border-radius: 16px; padding: 16px 20px; backdrop-filter: blur(8px);
      display: flex; align-items: center; gap: 16px; color: #fff;
      animation: lp-float 6s ease-in-out infinite;
    }
    .lp-preview-card:nth-child(2) { animation-delay: -2s; margin-left: 20px; }
    .lp-preview-card:nth-child(3) { animation-delay: -4s; }
    @keyframes lp-float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
    .lp-pc-icon {
      width: 48px; height: 48px; border-radius: 12px;
      background: rgba(255,255,255,.15); display: flex;
      align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
    }
    .lp-pc-info strong { display: block; font-size: 14px; font-weight: 700; }
    .lp-pc-info span   { font-size: 12px; opacity: .7; }
    .lp-pc-badge {
      margin-left: auto; white-space: nowrap; padding: 4px 10px;
      border-radius: 20px; font-size: 12px; font-weight: 700;
    }
    .badge-g { background:rgba(4,120,87,.3); color:#6ee7b7; border:1px solid rgba(110,231,183,.3); }
    .badge-b { background:rgba(37,99,235,.3); color:#93c5fd; border:1px solid rgba(147,197,253,.3); }

    /* ── STATS ───────────────────────────────────────── */
    .lp-stats { background: var(--primary); padding: 56px 48px; }
    .lp-stats-grid {
      max-width: 900px; margin: 0 auto;
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: 32px; text-align: center;
    }
    .lp-stat-num { display: block; font-size: 54px; font-weight: 800; line-height: 1; color: #fff; }
    .lp-stat-lbl { display: block; font-size: 15px; color: rgba(255,255,255,.75); margin-top: 8px; }

    /* ── SECTIONS COMMON ─────────────────────────────── */
    .lp-section { padding: 96px 48px; }
    .lp-section.alt { background: var(--bg); }

    .lp-sec-head { text-align: center; margin-bottom: 56px; }
    .lp-tag {
      display: inline-block; padding: 6px 14px; background: #dbeafe;
      color: var(--primary); border-radius: 20px; font-size: 13px;
      font-weight: 700; margin-bottom: 14px; text-transform: uppercase; letter-spacing: .05em;
    }
    .lp-sec-head h2 { font-size: clamp(28px,4vw,42px); font-weight: 800; margin: 0 0 12px; color: var(--text); }
    .lp-sec-head p  { font-size: 17px; color: var(--muted); max-width: 520px; margin: 0 auto; }

    /* ── FEATURES ────────────────────────────────────── */
    .lp-feat-grid {
      max-width: 1100px; margin: 0 auto;
      display: grid; grid-template-columns: repeat(auto-fit,minmax(290px,1fr)); gap: 22px;
    }
    .lp-feat-card {
      padding: 30px 26px; border: 1px solid var(--line); border-radius: 16px;
      background: #fff; transition: transform .25s, box-shadow .25s, border-color .25s;
      opacity: 0; transform: translateY(28px);
    }
    .lp-feat-card.visible {
      opacity: 1; transform: translateY(0);
      transition: opacity .5s ease, transform .5s ease, box-shadow .25s, border-color .25s;
    }
    .lp-feat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 48px rgba(37,99,235,.1); border-color: #bfdbfe; }
    .lp-feat-icon { width: 52px; height: 52px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 18px; }
    .lp-feat-card h3 { font-size: 18px; font-weight: 700; margin: 0 0 10px; color: var(--text); }
    .lp-feat-card p  { font-size: 14px; color: var(--muted); margin: 0; line-height: 1.65; }

    /* ── APARTMENTS ──────────────────────────────────── */
    .lp-apts-grid {
      max-width: 1100px; margin: 0 auto;
      display: grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap: 20px;
    }
    .lp-apt-card {
      background: #fff; border-radius: 16px; overflow: hidden;
      border: 1px solid var(--line); transition: transform .25s, box-shadow .25s;
      opacity: 0; transform: translateY(28px);
    }
    .lp-apt-card.visible {
      opacity: 1; transform: translateY(0);
      transition: opacity .5s ease, transform .5s ease, box-shadow .25s;
    }
    .lp-apt-card:hover { transform: translateY(-4px); box-shadow: 0 20px 48px rgba(0,0,0,.08); }
    .lp-apt-thumb {
      height: 160px; display: flex; align-items: center; justify-content: center;
      font-size: 52px; position: relative;
    }
    .lp-apt-badge { position: absolute; top: 12px; right: 12px; }
    .lp-apt-body { padding: 18px 20px 22px; }
    .lp-apt-body h3 { font-size: 15px; font-weight: 700; margin: 0 0 12px; color: var(--text); }
    .lp-apt-meta { display: flex; gap: 14px; margin-bottom: 14px; }
    .lp-apt-meta-i { display: flex; align-items: center; gap: 5px; font-size: 13px; color: var(--muted); font-weight: 600; }
    .lp-apt-price { font-size: 20px; font-weight: 800; color: var(--primary); }
    .lp-apt-price small { font-size: 13px; color: var(--muted); font-weight: 500; }
    .lp-empty-apts { grid-column:1/-1; text-align:center; padding: 60px 20px; color: var(--muted); }
    .lp-apts-footer { text-align: center; margin-top: 40px; }
    .g1{background:linear-gradient(135deg,#667eea,#764ba2)}
    .g2{background:linear-gradient(135deg,#f093fb,#f5576c)}
    .g3{background:linear-gradient(135deg,#4facfe,#00f2fe)}
    .g4{background:linear-gradient(135deg,#43e97b,#38f9d7)}
    .g5{background:linear-gradient(135deg,#fa709a,#fee140)}
    .g6{background:linear-gradient(135deg,#a18cd1,#fbc2eb)}

    /* ── HOW IT WORKS ────────────────────────────────── */
    .lp-steps {
      max-width: 860px; margin: 0 auto;
      display: grid; grid-template-columns: repeat(3,1fr);
      gap: 40px; position: relative;
    }
    .lp-steps::before {
      content: ''; position: absolute; top: 28px;
      left: calc(16.67% + 14px); right: calc(16.67% + 14px);
      height: 2px; background: linear-gradient(90deg,var(--primary),#818cf8);
    }
    .lp-step { text-align: center; opacity: 0; transform: translateY(28px); }
    .lp-step.visible { opacity: 1; transform: translateY(0); transition: opacity .5s ease, transform .5s ease; }
    .lp-step-num {
      width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 20px;
      background: linear-gradient(135deg,var(--primary),#818cf8);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 20px; font-weight: 800; position: relative; z-index: 1;
    }
    .lp-step h3 { font-size: 18px; font-weight: 700; margin: 0 0 10px; color: var(--text); }
    .lp-step p  { font-size: 14px; color: var(--muted); margin: 0; line-height: 1.65; }

    /* ── CTA ─────────────────────────────────────────── */
    .lp-cta {
      padding: 96px 48px; text-align: center;
      background: linear-gradient(135deg,#0f172a,#1e3a8a);
    }
    .lp-cta h2 { font-size: clamp(28px,4vw,44px); font-weight: 800; color: #fff; margin: 0 0 14px; }
    .lp-cta p  { font-size: 17px; color: rgba(255,255,255,.7); margin: 0 0 36px; }
    .lp-cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

    /* ── FOOTER ──────────────────────────────────────── */
    .lp-footer {
      background: #0f172a; padding: 36px 48px;
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 16px; border-top: 1px solid rgba(255,255,255,.08);
    }
    .lp-footer-brand { font-size: 16px; font-weight: 700; color: #93c5fd; }
    .lp-footer-links { display: flex; gap: 24px; flex-wrap: wrap; }
    .lp-footer-links a { color: rgba(255,255,255,.45); font-size: 14px; transition: color .18s; }
    .lp-footer-links a:hover { color: rgba(255,255,255,.9); }
    .lp-footer-copy { color: rgba(255,255,255,.3); font-size: 13px; }

    /* ── RESPONSIVE ──────────────────────────────────── */
    @media(max-width:768px){
      .lp-wrap { margin-left: 0; }
      .lp-hero-inner { grid-template-columns: 1fr; padding: 32px 20px 50px; gap: 40px; }
      .lp-hero-title { font-size: 36px; }
      .lp-stats { padding: 48px 20px; }
      .lp-stats-grid { grid-template-columns: 1fr; gap: 28px; }
      .lp-section { padding: 60px 20px; }
      .lp-steps { grid-template-columns: 1fr; gap: 32px; }
      .lp-steps::before { display: none; }
      .lp-cta { padding: 64px 20px; }
      .lp-footer { padding: 28px 20px; flex-direction: column; text-align: center; }
      .lp-footer-links { justify-content: center; }
    }
  </style>
</head>
<body class="lp">

<!-- SIDEBAR (same hover-rail as the app) -->
<aside class="app-menu">
  <div class="menu-brand">
    <span class="menu-brand-icon">&#127962;</span>
    <span class="menu-brand-text">ApartaGest</span>
  </div>

  <nav class="menu-nav">
    <a href="#hero" title="Acas&#259;">
      <span class="menu-icon">&#127968;</span>
      <span class="menu-label">Acas&#259;</span>
    </a>
    <a href="#features" title="Func&#539;ionalit&#259;&#539;i">
      <span class="menu-icon">&#11088;</span>
      <span class="menu-label">Func&#539;ionalit&#259;&#539;i</span>
    </a>
    <a href="#apartments" title="Apartamente">
      <span class="menu-icon">&#127962;</span>
      <span class="menu-label">Apartamente</span>
    </a>
    <a href="#how" title="Cum func&#539;ioneaz&#259;">
      <span class="menu-icon">&#128203;</span>
      <span class="menu-label">Cum func&#539;ioneaz&#259;</span>
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

<!-- ALL CONTENT SHIFTED RIGHT OF THE RAIL -->
<div class="lp-wrap">

  <!-- HERO -->
  <section class="lp-hero" id="hero">
    <div class="lp-blob lp-blob-1"></div>
    <div class="lp-blob lp-blob-2"></div>
    <div class="lp-blob lp-blob-3"></div>

    <div class="lp-hero-inner">
      <div>
        <h1 class="lp-hero-title">
          Gestioneaz&#259;-&#539;i<br>
          <span>apartamentele</span><br>
          cu u&#537;urin&#539;&#259;
        </h1>
        <p class="lp-hero-sub">
          Platform&#259; modern&#259; pentru proprietari &#537;i chiriași.
          Facturi, contracte, mentenan&#539;&#259; — totul &#238;ntr-un singur loc.
        </p>
        <div class="lp-hero-btns">
          <a href="#apartments" class="btn-hero btn-hero-white">&#128269; Exploreaz&#259; apartamente</a>
          <a href="register.php" class="btn-hero btn-hero-ghost">Creeaz&#259; cont &#8594;</a>
        </div>
      </div>

      <div>
        <div class="lp-preview-cards">
          <div class="lp-preview-card">
            <div class="lp-pc-icon">&#127968;</div>
            <div class="lp-pc-info">
              <strong>Str. Libert&#259;&#539;ii 12, Ap. 4</strong>
              <span>3 camere &middot; 78 m&sup2;</span>
            </div>
            <span class="lp-pc-badge badge-g">Liber</span>
          </div>
          <div class="lp-preview-card">
            <div class="lp-pc-icon">&#128196;</div>
            <div class="lp-pc-info">
              <strong>Factur&#259; #2024-115</strong>
              <span>Chirie decembrie &middot; 1 200 lei</span>
            </div>
            <span class="lp-pc-badge badge-b">Pl&#259;tit&#259;</span>
          </div>
          <div class="lp-preview-card">
            <div class="lp-pc-icon">&#128296;</div>
            <div class="lp-pc-info">
              <strong>Cerere mentenan&#539;&#259;</strong>
              <span>Instala&#539;ii &middot; Prioritate medie</span>
            </div>
            <span class="lp-pc-badge badge-g">Rezolvat</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- STATS -->
  <section class="lp-stats" id="lpStats">
    <div class="lp-stats-grid">
      <div>
        <span class="lp-stat-num" data-target="<?= e_land($totalApartamente) ?>">0</span>
        <span class="lp-stat-lbl">Apartamente &#238;nregistrate</span>
      </div>
      <div>
        <span class="lp-stat-num" data-target="<?= e_land($totalChiriasi) ?>">0</span>
        <span class="lp-stat-lbl">Chiriași activi</span>
      </div>
      <div>
        <span class="lp-stat-num" data-target="<?= e_land($totalLibere) ?>">0</span>
        <span class="lp-stat-lbl">Apartamente disponibile</span>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="lp-section" id="features">
    <div class="lp-sec-head">
      <span class="lp-tag">Func&#539;ionalit&#259;&#539;i</span>
      <h2>Tot ce ai nevoie &#238;ntr-un singur loc</h2>
      <p>De la facturi la contracte, gestionezi totul simplu &#537;i rapid.</p>
    </div>
    <div class="lp-feat-grid">
      <div class="lp-feat-card" style="transition-delay:.00s">
        <div class="lp-feat-icon" style="background:#dbeafe">&#128176;</div>
        <h3>Facturi &amp; Pl&#259;&#539;i</h3>
        <p>Genereaz&#259; facturi lunare, urm&#259;re&#537;te pl&#259;&#539;ile &#537;i restan&#539;ele. Notific&#259;ri pentru termenele dep&#259;&#537;ite.</p>
      </div>
      <div class="lp-feat-card" style="transition-delay:.10s">
        <div class="lp-feat-icon" style="background:#d1fae5">&#128203;</div>
        <h3>Contracte digitale</h3>
        <p>Creeaz&#259; &#537;i gestioneaz&#259; contracte de &#238;nchiriere. Urm&#259;re&#537;te datele de expirare &#537;i re&#238;nnoiri.</p>
      </div>
      <div class="lp-feat-card" style="transition-delay:.20s">
        <div class="lp-feat-icon" style="background:#fef3c7">&#128296;</div>
        <h3>Cereri de mentenan&#539;&#259;</h3>
        <p>Chiriașii raporteaz&#259; probleme direct din aplica&#539;ie. Urm&#259;re&#537;ti statusul &#238;n timp real.</p>
      </div>
      <div class="lp-feat-card" style="transition-delay:.30s">
        <div class="lp-feat-icon" style="background:#f3e8ff">&#128202;</div>
        <h3>Rapoarte &amp; Statistici</h3>
        <p>Vizualizezi venituri lunare, rata de ocupare &#537;i situa&#539;ia financiar&#259; dintr-o privire.</p>
      </div>
      <div class="lp-feat-card" style="transition-delay:.40s">
        <div class="lp-feat-icon" style="background:#fee2e2">&#128101;</div>
        <h3>Gestionare chiriași</h3>
        <p>Profil complet pentru fiecare chiriaș, cu documente, date de contact &#537;i istoricul contractelor.</p>
      </div>
      <div class="lp-feat-card" style="transition-delay:.50s">
        <div class="lp-feat-icon" style="background:#e0f2fe">&#128274;</div>
        <h3>Acces bazat pe rol</h3>
        <p>Proprietari, chiriași &#537;i administratori au acces personalizat la func&#539;iile relevante lor.</p>
      </div>
    </div>
  </section>

  <!-- APARTMENTS -->
  <section class="lp-section alt" id="apartments">
    <div class="lp-sec-head">
      <span class="lp-tag">Apartamente</span>
      <h2>Apartamente disponibile</h2>
      <p>Exploreaz&#259; lista de apartamente din platform&#259;.</p>
    </div>
    <div class="lp-apts-grid">
      <?php
      $grads = ['g1','g2','g3','g4','g5','g6'];
      $icons = ['&#127968;','&#127969;','&#127962;','&#128136;','&#128138;','&#127957;'];
      if (empty($apartamente)): ?>
        <div class="lp-empty-apts">
          <p style="font-size:52px;margin:0 0 14px">&#127960;</p>
          <p style="font-size:17px;font-weight:700;margin:0 0 6px;color:var(--text)">Niciun apartament &#238;nregistrat &#238;nc&#259;</p>
          <p style="margin:0">&#206;nregistreaz&#259;-te &#537;i adaug&#259; primul apartament.</p>
        </div>
      <?php else: foreach ($apartamente as $i => $apt):
        $sc       = $apt['status'] === 'liber' ? 'status-free' : 'status-occupied';
        $gradClass= $grads[$i % 6];
        $icon     = $icons[$i % 6];
      ?>
        <div class="lp-apt-card" style="transition-delay:<?= $i * 0.08 ?>s">
          <div class="lp-apt-thumb <?= e_land($gradClass) ?>">
            <?= $icon ?>
            <div class="lp-apt-badge">
              <span class="status-pill <?= e_land($sc) ?>"><?= e_land(ucfirst($apt['status'])) ?></span>
            </div>
          </div>
          <div class="lp-apt-body">
            <h3><?= e_land($apt['adresa']) ?></h3>
            <div class="lp-apt-meta">
              <div class="lp-apt-meta-i">&#128716; <?= e_land($apt['numar_camere']) ?> camere</div>
              <div class="lp-apt-meta-i">&#128207; <?= e_land($apt['suprafata']) ?> m&sup2;</div>
            </div>
            <div class="lp-apt-price"><?= e_land($apt['chirie']) ?> lei <small>/ lun&#259;</small></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <div class="lp-apts-footer">
      <a href="login.php" class="button button-primary" style="min-height:48px;padding:0 28px;font-size:16px">
        Vezi toate apartamentele &#8594;
      </a>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="lp-section" id="how">
    <div class="lp-sec-head">
      <span class="lp-tag">Cum func&#539;ioneaz&#259;</span>
      <h2>Simplu &#238;n 3 pa&#537;i</h2>
      <p>E&#537;ti opera&#539;ional &#238;n c&#226;teva minute.</p>
    </div>
    <div class="lp-steps">
      <div class="lp-step" style="transition-delay:.00s">
        <div class="lp-step-num">1</div>
        <h3>Creeaz&#259; un cont</h3>
        <p>&#206;nregistreaz&#259;-te ca proprietar sau chiriaș. Procesul dureaz&#259; mai pu&#539;in de 2 minute.</p>
      </div>
      <div class="lp-step" style="transition-delay:.15s">
        <div class="lp-step-num">2</div>
        <h3>Adaug&#259; apartamentele</h3>
        <p>Introdu detaliile propriet&#259;&#539;ilor tale — adres&#259;, suprafa&#539;&#259;, chirie — &#537;i lega&#539;i chiriașii.</p>
      </div>
      <div class="lp-step" style="transition-delay:.30s">
        <div class="lp-step-num">3</div>
        <h3>Gestioneaz&#259; totul</h3>
        <p>Facturi, mentenan&#539;&#259;, contracte — totul controlat din dashboard-ul t&#259;u personalizat.</p>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="lp-cta">
    <h2>Gata s&#259; &#238;ncepi?</h2>
    <p>Gratuit, rapid &#537;i u&#537;or de folosit.</p>
    <div class="lp-cta-btns">
      <a href="register.php" class="btn-hero btn-hero-white">&#128640; Creeaz&#259; cont gratuit</a>
      <a href="login.php"    class="btn-hero btn-hero-ghost">Am deja cont</a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="lp-footer">
    <span class="lp-footer-brand">&#127962; ApartaGest</span>
    <div class="lp-footer-links">
      <a href="#features">Func&#539;ionalit&#259;&#539;i</a>
      <a href="#apartments">Apartamente</a>
      <a href="login.php">Login</a>
      <a href="register.php">&#206;nregistrare</a>
    </div>
    <span class="lp-footer-copy">&copy; <?= date('Y') ?> ApartaGest</span>
  </footer>

</div><!-- /.lp-wrap -->

<script>
(function () {
  'use strict';

  /* ── Smooth-scroll anchor links ── */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var target = document.querySelector(a.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 20, behavior: 'smooth' });
    });
  });

  /* ── Animated counters ── */
  function runCounters() {
    document.querySelectorAll('[data-target]').forEach(function (el) {
      var target = parseInt(el.dataset.target, 10) || 0;
      if (target === 0) { el.textContent = '0'; return; }
      var start = performance.now(), dur = 1800;
      (function tick(now) {
        var t = Math.min((now - start) / dur, 1);
        el.textContent = Math.round((1 - Math.pow(1 - t, 3)) * target);
        if (t < 1) requestAnimationFrame(tick);
      })(start);
    });
  }

  /* ── Intersection Observer: scroll animations + counter trigger ── */
  var statsSection = document.getElementById('lpStats');
  var countersActive = false;

  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('visible');
      if (entry.target === statsSection && !countersActive) {
        countersActive = true;
        runCounters();
      }
      io.unobserve(entry.target);
    });
  }, { threshold: 0.15 });

  document.querySelectorAll('.lp-feat-card, .lp-apt-card, .lp-step').forEach(function (el) {
    io.observe(el);
  });
  io.observe(statsSection);
})();
</script>
</body>
</html>
