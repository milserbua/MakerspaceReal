<?php
$ldap_host = "10.10.80.42";
$base_dn   = "DC=SYNCHTLINN,DC=local";

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
        $error = "Verbindung zu $ldap_host fehlgeschlagen.";
    } else {
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $bind_user = $username . "@SYNCHTLINN.local";
        if (@ldap_bind($ds, $bind_user, $password)) {

            if ($modus === 'gruppe') {
                // Gruppensuche: CN oder DN
                $filter = "(&(objectClass=group)(|(cn=*$suche*)(distinguishedName=*$suche*)))";
                $attributes = ["cn", "description", "member", "distinguishedName", "groupType", "mail"];
                $search = @ldap_search($ds, $base_dn, $filter, $attributes);
            } else {
                // Usersuche: Vorname, Nachname, sAMAccountName, DN, Anzeigename, Gruppenmitgliedschaft
                $filter = "(&(objectClass=user)(|(sAMAccountName=*$suche*)(givenName=*$suche*)(sn=*$suche*)(displayName=*$suche*)(distinguishedName=*$suche*)(memberOf=*$suche*)))";
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
            $error = "Bind fehlgeschlagen: " . ldap_error($ds);
        }
        ldap_unbind($ds);
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>LDAP Explorer</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f0f1a; color: #e0e0ff; min-height: 100vh; padding: 40px 20px; }
        h1 { color: #a080ff; margin-bottom: 6px; font-size: 24px; }
        p.sub { color: #6060a0; margin-bottom: 30px; font-size: 13px; }

        .card { background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 12px; padding: 24px; max-width: 600px; margin: 0 auto 30px; }

        label { display: block; font-size: 12px; color: #8080c0; margin-bottom: 6px; letter-spacing: 1px; }
        input[type=text], input[type=password] {
            width: 100%; padding: 10px 14px; background: #0f0f1a;
            border: 1px solid #3a3a5a; border-radius: 8px; color: #e0e0ff;
            font-size: 14px; margin-bottom: 16px;
        }
        input:focus { outline: none; border-color: #7050c0; }

        .hint { font-size: 11px; color: #5050a0; margin-top: -12px; margin-bottom: 16px; }

        .toggle-row { display: flex; gap: 10px; margin-bottom: 20px; }
        .toggle-btn {
            flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #3a3a5a;
            background: #0f0f1a; color: #8080c0; font-size: 13px; font-weight: bold;
            cursor: pointer; text-align: center; transition: all 0.2s;
        }
        .toggle-btn.active { background: #2a1a4a; border-color: #7050c0; color: #c0a0ff; }

        button[type=submit] {
            width: 100%; padding: 12px; background: linear-gradient(to right, #7050c0, #5030a0);
            border: none; border-radius: 8px; color: white; font-size: 15px;
            font-weight: bold; cursor: pointer;
        }
        button[type=submit]:hover { background: linear-gradient(to right, #8060d0, #6040b0); }

        .error { background: #2a1a1a; border: 1px solid #ff4040; border-radius: 8px;
                 padding: 14px; color: #ff8080; max-width: 600px; margin: 0 auto 20px; }

        .result-card { background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 12px;
                       padding: 24px; max-width: 600px; margin: 0 auto 16px; }
        .result-card h2 { color: #a080ff; margin-bottom: 16px; font-size: 16px; border-bottom: 1px solid #2a2a4a; padding-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        td { padding: 7px 10px; border-bottom: 1px solid #1f1f38; vertical-align: top; }
        td:first-child { color: #8080c0; width: 180px; white-space: nowrap; font-weight: bold; }
        td:last-child { color: #c0c0ff; word-break: break-all; }
        tr:last-child td { border-bottom: none; }

        .group-tag { display: inline-block; background: #2a1a4a; border: 1px solid #5030a0;
                     border-radius: 6px; padding: 3px 8px; margin: 2px; font-size: 11px; color: #c0a0ff; }
        .member-tag { display: inline-block; background: #1a2a1a; border: 1px solid #306030;
                      border-radius: 6px; padding: 3px 8px; margin: 2px; font-size: 11px; color: #80c080; }

        .none { color: #4a4a6a; font-style: italic; }
        .count { color: #6060a0; font-size: 12px; margin-bottom: 12px; }
    </style>
</head>
<body>

<div style="max-width:600px;margin:0 auto 20px;">
    <h1>LDAP Explorer</h1>
    <p class="sub">Melde dich mit deinem Schulaccount an und durchsuche das Active Directory.</p>
</div>

<div class="card">
    <form method="POST">
        <label>DEIN USERNAME (zum Anmelden)</label>
        <input type="text" name="username" placeholder="z.B. max.mustermann" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

        <label>PASSWORT</label>
        <input type="password" name="password" required>

        <label>SUCHMODUS</label>
        <div class="toggle-row">
            <div class="toggle-btn <?= ($modus === 'user') ? 'active' : '' ?>" onclick="setModus('user')">👤 User</div>
            <div class="toggle-btn <?= ($modus === 'gruppe') ? 'active' : '' ?>" onclick="setModus('gruppe')">👥 Gruppe</div>
        </div>
        <input type="hidden" name="modus" id="modus" value="<?= htmlspecialchars($modus) ?>">

        <label>SUCHBEGRIFF</label>
        <input type="text" name="suche" id="suche-input"
               placeholder="<?= $modus === 'gruppe' ? 'z.B. MakerAccess oder ADMIN' : 'z.B. vorname, nachname ...' ?>"
               value="<?= htmlspecialchars($_POST['suche'] ?? '') ?>">
        <p class="hint" id="suche-hint">
            <?= $modus === 'gruppe'
                ? 'Sucht in: Gruppenname (CN), DN'
                : 'Sucht in: Benutzername, Vorname, Nachname, Anzeigename, DN, Gruppenmitgliedschaft' ?>
        </p>

        <button type="submit" name="suchen"> Suchen</button>
    </form>
</div>

<script>
function setModus(m) {
    document.getElementById('modus').value = m;
    document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    if (m === 'gruppe') {
        document.getElementById('suche-input').placeholder = 'z.B. MakerAccess oder ADMIN';
        document.getElementById('suche-hint').textContent = 'Sucht in: Gruppenname (CN), DN';
    } else {
        document.getElementById('suche-input').placeholder = 'z.B. Heike, Abart, h.abart ...';
        document.getElementById('suche-hint').textContent = 'Sucht in: Benutzername, Vorname, Nachname, Anzeigename, DN, Gruppenmitgliedschaft';
    }
}
</script>

<?php if ($error): ?>
    <div class="error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($entries !== null): ?>
    <?php if ($entries['count'] === 0): ?>
        <div class="error">Nichts gefunden.</div>
    <?php else: ?>
        <p class="count" style="max-width:600px;margin:0 auto 12px;color:#6060a0;">
            <?= $entries['count'] ?> Ergebnis(se) gefunden
        </p>

        <?php if ($modus === 'gruppe'): ?>
            <?php for ($i = 0; $i < $entries['count']; $i++):
                $e = $entries[$i];
                $memberCount = isset($e['member']) ? $e['member']['count'] : 0;
            ?>
            <div class="result-card">
                <h2>👥 <?= htmlspecialchars($e['cn'][0] ?? '–') ?></h2>
                <table>
                    <tr><td>Gruppenname</td><td><?= htmlspecialchars($e['cn'][0] ?? '–') ?></td></tr>
                    <tr><td>Beschreibung</td><td><?= htmlspecialchars($e['description'][0] ?? '–') ?></td></tr>
                    <tr><td>E-Mail</td><td><?= htmlspecialchars($e['mail'][0] ?? '–') ?></td></tr>
                    <tr><td>DN</td><td><?= htmlspecialchars($e['distinguishedname'][0] ?? '–') ?></td></tr>
                    <tr>
                        <td>Mitglieder (<?= $memberCount ?>)</td>
                        <td>
                            <?php if ($memberCount > 0): ?>
                                <?php for ($m = 0; $m < $memberCount; $m++):
                                    preg_match('/CN=([^,]+)/', $e['member'][$m], $match);
                                    $label = $match[1] ?? $e['member'][$m];
                                ?>
                                    <span class="member-tag" title="<?= htmlspecialchars($e['member'][$m]) ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </span>
                                <?php endfor; ?>
                            <?php else: ?>
                                <span class="none">Keine Mitglieder</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endfor; ?>

        <?php else: ?>
            <?php for ($i = 0; $i < $entries['count']; $i++):
                $e = $entries[$i]; ?>
            <div class="result-card">
                <h2>👤 <?= htmlspecialchars($e['displayname'][0] ?? $e['cn'][0] ?? '–') ?></h2>
                <table>
                    <tr><td>sAMAccountName</td><td><?= htmlspecialchars($e['samaccountname'][0] ?? '–') ?></td></tr>
                    <tr><td>Anzeigename</td><td><?= htmlspecialchars($e['displayname'][0] ?? '–') ?></td></tr>
                    <tr><td>Vorname</td><td><?= htmlspecialchars($e['givenname'][0] ?? '–') ?></td></tr>
                    <tr><td>Nachname</td><td><?= htmlspecialchars($e['sn'][0] ?? '–') ?></td></tr>
                    <tr><td>E-Mail</td><td><?= htmlspecialchars($e['mail'][0] ?? '–') ?></td></tr>
                    <tr><td>UPN</td><td><?= htmlspecialchars($e['userprincipalname'][0] ?? '–') ?></td></tr>
                    <tr><td>Abteilung</td><td><?= htmlspecialchars($e['department'][0] ?? '–') ?></td></tr>
                    <tr><td>Titel</td><td><?= htmlspecialchars($e['title'][0] ?? '–') ?></td></tr>
                    <tr><td>DN</td><td><?= htmlspecialchars($e['distinguishedname'][0] ?? '–') ?></td></tr>
                    <tr>
                        <td>Gruppen (<?= isset($e['memberof']) ? $e['memberof']['count'] : 0 ?>)</td>
                        <td>
                            <?php if (!empty($e['memberof']['count'])): ?>
                                <?php for ($g = 0; $g < $e['memberof']['count']; $g++):
                                    preg_match('/CN=([^,]+)/', $e['memberof'][$g], $m);
                                    $label = $m[1] ?? $e['memberof'][$g];
                                ?>
                                    <span class="group-tag" title="<?= htmlspecialchars($e['memberof'][$g]) ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </span>
                                <?php endfor; ?>
                            <?php else: ?>
                                <span class="none">Keine Gruppen gefunden</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endfor; ?>
        <?php endif; ?>

    <?php endif; ?>
<?php endif; ?>

</body>
</html>