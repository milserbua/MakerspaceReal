<?php
/**
 * LDAP Directory Explorer
 * (C) 2024
 */
 
$ldap_host = "10.10.80.42";
$base_dn   = "DC=SYNCHTLINN,DC=local";
$base_dn_lehrer = "OU=Lehrer,DC=SYNCHTLINN,DC=local";
 
$result  = null;
$error   = null;
$entries = null;
$modus   = $_POST['modus'] ?? 'user';
 
if (isset($_POST['suchen'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $suche    = trim($_POST['suche']) ?: $username;
    $modus    = $_POST['modus'] ?? 'user';
 
    $ds = @ldap_connect($ldap_host, 389);
    if (!$ds) {
        $error = "Verbindung fehlgeschlagen ($ldap_host)";
    } else {
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
 
        $bind_user = $username . "@SYNCHTLINN.local";
        if (@ldap_bind($ds, $bind_user, $password)) {
 
            if ($modus === 'gruppe') {
                $filter = "(&(objectClass=group)(|(cn=*$suche*)(distinguishedName=*$suche*)))";
                $attributes = ["cn", "description", "member", "distinguishedName", "groupType", "mail"];
                $search = @ldap_search($ds, $base_dn, $filter, $attributes);
 
            } elseif ($modus === 'lehrer') {
                // Lehrersuche: nur im Lehrer-OU oder mit Lehrer-Gruppenfilter
                $filter = "(&(objectClass=user)(|(sAMAccountName=*$suche*)(givenName=*$suche*)(sn=*$suche*)(displayName=*$suche*)(distinguishedName=*$suche*)))";
                $attributes = [
                    "sAMAccountName", "displayName", "cn", "mail",
                    "memberOf", "distinguishedName", "department",
                    "title", "telephoneNumber", "givenName", "sn",
                    "userPrincipalName", "objectClass", "description"
                ];
                // Erst im Lehrer-OU suchen, Fallback auf Base-DN mit Lehrer-Filter
                $search = @ldap_search($ds, $base_dn_lehrer, $filter, $attributes);
                if (!$search) {
                    // Fallback: Alle User mit Abteilung oder Gruppe "Lehrer"
                    $filter_fallback = "(&(objectClass=user)(|(department=Lehrer*)(memberOf=CN=Lehrer*)(|(sAMAccountName=*$suche*)(givenName=*$suche*)(sn=*$suche*)(displayName=*$suche*))))";
                    $search = @ldap_search($ds, $base_dn, $filter_fallback, $attributes);
                }
 
            } else {
                // Benutzersuche (nicht Lehrer)
                $filter = "(&(objectClass=user)(!(distinguishedName=*OU=Lehrer*))(|(sAMAccountName=*$suche*)(givenName=*$suche*)(sn=*$suche*)(displayName=*$suche*)(distinguishedName=*$suche*)(memberOf=*$suche*)))";
                $attributes = [
                    "sAMAccountName", "displayName", "cn", "mail",
                    "memberOf", "distinguishedName", "department",
                    "title", "telephoneNumber", "givenName", "sn",
                    "userPrincipalName", "objectClass", "description"
                ];
                $search = @ldap_search($ds, $base_dn, $filter, $attributes);
            }
 
            if ($search) {
                $entries = ldap_get_entries($ds, $search);
            } else {
                $error = "Suche fehlgeschlagen: " . ldap_error($ds);
            }
        } else {
            $error = "Anmeldung fehlgeschlagen: " . ldap_error($ds);
        }
        ldap_unbind($ds);
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directory Explorer – SYNCHTLINN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
        :root {
            --blue-50:  #eff6ff;
            --blue-100: #dbeafe;
            --blue-200: #bfdbfe;
            --blue-400: #60a5fa;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-900: #1e3a8a;
 
            --slate-50:  #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-900: #0f172a;
 
            --white: #ffffff;
            --error: #dc2626;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
 
            --radius-sm: 6px;
            --radius: 10px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04);
            --shadow: 0 4px 12px rgba(15,23,42,.06), 0 2px 6px rgba(15,23,42,.04);
            --shadow-md: 0 8px 24px rgba(15,23,42,.08), 0 4px 12px rgba(15,23,42,.04);
        }
 
        html { font-size: 15px; }
 
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--slate-50);
            color: var(--slate-700);
            min-height: 100vh;
            padding: 0 0 60px;
        }
 
        /* ── Top bar ────────────────────────────────── */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--slate-200);
            padding: 0 32px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topbar-icon {
            width: 32px; height: 32px;
            background: var(--blue-600);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-size: 16px;
        }
        .topbar-name {
            font-weight: 700;
            font-size: 16px;
            color: var(--slate-900);
            letter-spacing: -0.3px;
        }
        .topbar-domain {
            font-size: 12px;
            color: var(--slate-400);
            font-weight: 400;
        }
        .topbar-badge {
            font-size: 11px;
            font-weight: 600;
            color: var(--blue-600);
            background: var(--blue-50);
            border: 1px solid var(--blue-200);
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
 
        /* ── Page layout ────────────────────────────── */
        .page {
            max-width: 820px;
            margin: 0 auto;
            padding: 40px 24px 0;
        }
 
        /* ── Section header ─────────────────────────── */
        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .section-sub {
            font-size: 14px;
            color: var(--slate-400);
            margin-bottom: 28px;
        }
 
        /* ── Card ───────────────────────────────────── */
        .card {
            background: var(--white);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 28px;
            margin-bottom: 24px;
        }
 
        /* ── Mode tabs ──────────────────────────────── */
        .mode-tabs {
            display: flex;
            gap: 6px;
            background: var(--slate-100);
            padding: 5px;
            border-radius: var(--radius);
            margin-bottom: 24px;
        }
        .tab-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 12px;
            border: none;
            border-radius: 7px;
            background: transparent;
            color: var(--slate-500);
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .tab-btn:hover { color: var(--slate-700); background: rgba(255,255,255,.6); }
        .tab-btn.active {
            background: var(--white);
            color: var(--blue-600);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        .tab-icon { font-size: 16px; line-height: 1; }
 
        /* ── Form ───────────────────────────────────── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        .form-row.three { grid-template-columns: 1fr 1fr 1fr; }
 
        @media (max-width: 600px) {
            .form-row, .form-row.three { grid-template-columns: 1fr; }
        }
 
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 7px;
        }
        .field input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--slate-200);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 14px;
            color: var(--slate-900);
            background: var(--white);
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }
        .field input::placeholder { color: var(--slate-300); }
        .field input:focus {
            border-color: var(--blue-400);
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }
 
        .btn-search {
            width: 100%;
            padding: 12px;
            background: var(--blue-600);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            letter-spacing: 0.2px;
        }
        .btn-search:hover { background: var(--blue-700); box-shadow: 0 4px 12px rgba(37,99,235,.25); }
        .btn-search:active { transform: translateY(1px); }
 
        /* ── Error ──────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error);
            padding: 13px 16px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .alert-icon { font-size: 15px; flex-shrink: 0; margin-top: 1px; }
 
        /* ── Results ────────────────────────────────── */
        .results-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--slate-200);
        }
        .results-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--slate-700);
        }
        .results-count {
            font-size: 12px;
            font-weight: 600;
            color: var(--blue-600);
            background: var(--blue-50);
            border: 1px solid var(--blue-100);
            padding: 2px 10px;
            border-radius: 20px;
        }
 
        /* ── Result card ────────────────────────────── */
        .result-card {
            background: var(--white);
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 14px;
            box-shadow: var(--shadow-sm);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .result-card:hover {
            border-color: var(--blue-300);
            box-shadow: var(--shadow);
        }
 
        .result-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 22px 16px;
            border-bottom: 1px solid var(--slate-100);
        }
        .avatar {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }
        .avatar-user  { background: var(--blue-50);  color: var(--blue-600); }
        .avatar-lehrer { background: #f0fdf4; color: #16a34a; }
        .avatar-gruppe { background: #faf5ff; color: #7c3aed; }
 
        .result-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.2px;
            line-height: 1.2;
        }
        .result-sub {
            font-size: 12px;
            color: var(--slate-400);
            margin-top: 2px;
        }
 
        .result-body {
            padding: 16px 22px 18px;
        }
 
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .data-table tr td {
            padding: 7px 0;
            border-bottom: 1px solid var(--slate-100);
            vertical-align: top;
            line-height: 1.5;
        }
        .data-table tr:last-child td { border-bottom: none; }
 
        .dt-label {
            width: 130px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--slate-400);
            padding-right: 12px;
        }
        .dt-value { color: var(--slate-700); }
 
        .dn-value {
            font-family: 'DM Mono', monospace;
            font-size: 10.5px;
            color: var(--slate-400);
            word-break: break-all;
            line-height: 1.6;
        }
 
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            margin: 2px 4px 2px 0;
            line-height: 1.6;
        }
        .pill-group {
            background: var(--blue-50);
            color: var(--blue-600);
            border: 1px solid var(--blue-100);
        }
        .pill-member {
            background: var(--slate-100);
            color: var(--slate-500);
            border: 1px solid var(--slate-200);
        }
 
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--slate-400);
        }
        .empty-state-icon { font-size: 36px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }
 
        .divider { border: none; border-top: 1px solid var(--slate-200); margin: 8px 0 14px; }
    </style>
</head>
<body>
 
<!-- Top Navigation -->
<header class="topbar">
    <div class="topbar-brand">
        <div class="topbar-icon">🗂</div>
        <div>
            <div class="topbar-name">Directory Explorer</div>
            <div class="topbar-domain">SYNCHTLINN.local</div>
        </div>
    </div>
    <span class="topbar-badge">ACTIVE DIRECTORY</span>
</header>
 
<main class="page">
 
    <p class="section-sub" style="margin-top:4px; margin-bottom:28px;">Verzeichnissuche für Benutzer, Lehrende und Gruppen</p>
 
    <div class="card">
        <?php if ($error): ?>
        <div class="alert">
            <span class="alert-icon">⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>
 
        <form method="POST">
            <!-- Mode tabs -->
            <div class="mode-tabs">
                <button type="button" class="tab-btn <?= ($modus === 'user')    ? 'active' : '' ?>" onclick="setModus('user')">
                    <span class="tab-icon">👤</span> Benutzer
                </button>
                <button type="button" class="tab-btn <?= ($modus === 'lehrer')  ? 'active' : '' ?>" onclick="setModus('lehrer')">
                    <span class="tab-icon">🎓</span> Lehrende
                </button>
                <button type="button" class="tab-btn <?= ($modus === 'gruppe')  ? 'active' : '' ?>" onclick="setModus('gruppe')">
                    <span class="tab-icon">👥</span> Gruppen
                </button>
            </div>
            <input type="hidden" name="modus" id="modus" value="<?= htmlspecialchars($modus) ?>">
 
            <!-- Credentials + search -->
            <div class="form-row three">
                <div class="field">
                    <label>Benutzername</label>
                    <input type="text" name="username" placeholder="z.B. k.muster"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label>Passwort</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="field">
                    <label>Suchbegriff <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--slate-300)">(optional)</span></label>
                    <input type="text" name="suche" placeholder="Name, Kürzel …"
                           value="<?= htmlspecialchars($_POST['suche'] ?? '') ?>">
                </div>
            </div>
 
            <button type="submit" name="suchen" class="btn-search">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Suchen
            </button>
        </form>
    </div>
 
    <script>
    function setModus(m) {
        document.getElementById('modus').value = m;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }
    </script>
 
    <?php if ($entries !== null): ?>
    <div>
        <div class="results-header">
            <span class="results-title">Suchergebnisse</span>
            <span class="results-count"><?= $entries['count'] ?> Treffer</span>
        </div>
 
        <?php if ($entries['count'] === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <p>Keine Einträge gefunden.</p>
        </div>
        <?php endif; ?>
 
        <?php for ($i = 0; $i < $entries['count']; $i++):
            $res = $entries[$i];
            $displayName = htmlspecialchars($res['displayname'][0] ?? $res['cn'][0] ?? 'Unbekannt');
            $sam  = htmlspecialchars($res['samaccountname'][0] ?? '');
            $avatarClass = ($modus === 'lehrer') ? 'avatar-lehrer' : ($modus === 'gruppe' ? 'avatar-gruppe' : 'avatar-user');
            $avatarIcon  = ($modus === 'lehrer') ? '🎓' : ($modus === 'gruppe' ? '👥' : '👤');
        ?>
        <div class="result-card">
            <div class="result-card-header">
                <div class="avatar <?= $avatarClass ?>"><?= $avatarIcon ?></div>
                <div>
                    <div class="result-name"><?= $displayName ?></div>
                    <?php if ($sam): ?>
                    <div class="result-sub"><?= $sam ?></div>
                    <?php endif; ?>
                </div>
            </div>
 
            <div class="result-body">
                <table class="data-table">
                <?php if ($modus === 'user' || $modus === 'lehrer'): ?>
 
                    <?php if (!empty($res['mail'][0])): ?>
                    <tr>
                        <td class="dt-label">E-Mail</td>
                        <td class="dt-value"><?= htmlspecialchars($res['mail'][0]) ?></td>
                    </tr>
                    <?php endif; ?>
 
                    <?php if (!empty($res['department'][0])): ?>
                    <tr>
                        <td class="dt-label">Abteilung</td>
                        <td class="dt-value"><?= htmlspecialchars($res['department'][0]) ?></td>
                    </tr>
                    <?php endif; ?>
 
                    <?php if (!empty($res['title'][0])): ?>
                    <tr>
                        <td class="dt-label">Titel</td>
                        <td class="dt-value"><?= htmlspecialchars($res['title'][0]) ?></td>
                    </tr>
                    <?php endif; ?>
 
                    <?php if (!empty($res['telephonenumber'][0])): ?>
                    <tr>
                        <td class="dt-label">Telefon</td>
                        <td class="dt-value"><?= htmlspecialchars($res['telephonenumber'][0]) ?></td>
                    </tr>
                    <?php endif; ?>
 
                    <tr>
                        <td class="dt-label">Gruppen</td>
                        <td class="dt-value">
                        <?php
                        if (!empty($res['memberof']) && $res['memberof']['count'] > 0) {
                            for ($j = 0; $j < $res['memberof']['count']; $j++) {
                                preg_match('/CN=([^,]+)/', $res['memberof'][$j], $m);
                                echo '<span class="pill pill-group">'.htmlspecialchars($m[1] ?? '...').'</span>';
                            }
                        } else {
                            echo '<span style="color:var(--slate-300);font-size:13px">Keine Gruppen</span>';
                        }
                        ?>
                        </td>
                    </tr>
 
                <?php else: /* Gruppe */ ?>
 
                    <?php if (!empty($res['description'][0])): ?>
                    <tr>
                        <td class="dt-label">Beschreibung</td>
                        <td class="dt-value"><?= htmlspecialchars($res['description'][0]) ?></td>
                    </tr>
                    <?php endif; ?>
 
                    <?php if (!empty($res['mail'][0])): ?>
                    <tr>
                        <td class="dt-label">E-Mail</td>
                        <td class="dt-value"><?= htmlspecialchars($res['mail'][0]) ?></td>
                    </tr>
                    <?php endif; ?>
 
                    <tr>
                        <td class="dt-label">Mitglieder</td>
                        <td class="dt-value">
                        <?php
                        $mCount = isset($res['member']) ? $res['member']['count'] : 0;
                        echo '<span style="color:var(--slate-500);font-size:12px;font-weight:600">' . $mCount . ' Mitglied' . ($mCount !== 1 ? 'er' : '') . '</span>';
                        if ($mCount > 0): ?>
                        <hr class="divider">
                        <?php for ($m = 0; $m < $mCount; $m++) {
                            preg_match('/CN=([^,]+)/', $res['member'][$m], $match);
                            echo '<span class="pill pill-member">'.htmlspecialchars($match[1] ?? '...').'</span>';
                        } endif; ?>
                        </td>
                    </tr>
 
                <?php endif; ?>
 
                    <tr>
                        <td class="dt-label">DN</td>
                        <td class="dn-value"><?= htmlspecialchars($res['distinguishedname'][0] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
 
</main>
</body>
</html>
 