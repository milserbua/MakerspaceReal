<?php
session_start();
require 'db.php';

// ── LDAP Konfiguration ─────────────────────────────────────────────────────
$ldap_host      = "10.10.80.42";
$base_dn        = "DC=SYNCHTLINN,DC=local";
$required_group = "CN=MakerAccessAdmins,OU=ADMIN,DC=SYNCHTLINN,DC=local";

$nachricht = "";

if (isset($_POST['login_btn'])) {

    $user_input = trim($_POST['username'] ?? '');
    $pass_input = $_POST['password'] ?? '';

    // ══════════════════════════════════════════════════════════════════════
    // 1. LDAP-Login versuchen
    // ══════════════════════════════════════════════════════════════════════
    $ldap_success = false;
    $ds = @ldap_connect($ldap_host, 389);

    if ($ds) {
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $login_user = $user_input . "@SYNCHTLINN.local";

        if (@ldap_bind($ds, $login_user, $pass_input)) {

            // Gruppenprüfung
            $filter     = "(&(sAMAccountName=$user_input)(objectclass=person)(memberOf=$required_group))";
            $attributes = ["displayname", "memberof"];
            $search     = @ldap_search($ds, $base_dn, $filter, $attributes);

            // Fallback: anonymous bind
            if (!$search) {
                @ldap_bind($ds);
                $search = @ldap_search($ds, $base_dn, $filter, $attributes);
            }

            if ($search) {
                $results = ldap_get_entries($ds, $search);

                if ($results['count'] > 0) {
                    // ── LDAP LOGIN ERFOLGREICH ─────────────────────────
                    $display_name = $results[0]['displayname'][0] ?? $user_input;

                    $_SESSION['authenticated'] = true;
                    $_SESSION['username']      = $display_name;
                    $_SESSION['role']          = 'Admin'; // LDAP-Gruppe = Admin
                    $_SESSION['auth_method']   = 'ldap';

                    // Login in DB loggen (optional – nur wenn User in DB existiert)
                    try {
                        $stmt = $pdo->prepare("SELECT WerkBenutzerID FROM Werkstattbenutzer WHERE Username = :name");
                        $stmt->execute(['name' => $user_input]);
                        $user_db = $stmt->fetch();
                        if ($user_db) {
                            $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, 'Login erfolgreich (LDAP)')")
                                ->execute([$user_db['WerkBenutzerID']]);
                        }
                    } catch (Exception $e) { /* Logging optional, nicht kritisch */ }

                    ldap_unbind($ds);
                    header("Location: dashboard_admin_view.php");
                    exit;

                } else {
                    // Credentials OK, aber nicht in der Gruppe
                    $nachricht = "Login korrekt, aber keine Berechtigung für den Makerspace-Bereich.";
                    $ldap_success = true; // Auth war ok, nur keine Berechtigung → kein DB-Fallback
                }
            }
        }
        // LDAP-Bind fehlgeschlagen → still weiter zur DB
        ldap_unbind($ds);
    }

    // ══════════════════════════════════════════════════════════════════════
    // 2. DB-Login (Fallback – nur wenn LDAP nicht erfolgreich autorisiert hat)
    // ══════════════════════════════════════════════════════════════════════
    if (!$ldap_success && empty($nachricht)) {

        $sql  = "SELECT * FROM Werkstattbenutzer WHERE Username = :name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['name' => $user_input]);
        $user_db = $stmt->fetch();

        if ($user_db && password_verify($pass_input, $user_db['Passwort'])) {

            // ── DB LOGIN ERFOLGREICH ───────────────────────────────────
            $_SESSION['authenticated'] = true;
            $_SESSION['userid']        = $user_db['WerkBenutzerID'];
            $_SESSION['username']      = $user_db['Username'];
            $_SESSION['role']          = $user_db['Rolle'];
            $_SESSION['auth_method']   = 'db';

            $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, 'Login erfolgreich')")
                ->execute([$user_db['WerkBenutzerID']]);

            if ($user_db['Rolle'] === 'Admin') {
                header("Location: dashboard_admin_view.php");
            } else {
                header("Location: dashboard_benutzer_view.php");
            }
            exit;

        } else {
            // ── LOGIN FEHLGESCHLAGEN ───────────────────────────────────
            if ($user_db) {
                $pdo->prepare("INSERT INTO SystemLogs (WerkBenutzerID, Ereignis) VALUES (?, 'FEHLVERSUCH: Passwort falsch')")
                    ->execute([$user_db['WerkBenutzerID']]);
            }
            $nachricht = "Benutzername oder Passwort falsch.";
        }
    }
}

include 'login_view.php';
?>