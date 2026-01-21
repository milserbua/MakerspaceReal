-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 14. Jan 2026 um 10:30
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `makerspace`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `authentifizierungen`
--

CREATE TABLE `authentifizierungen` (
  `WerkBenutzerAuthID` int(11) NOT NULL,
  `WerkBenutzerID` int(11) NOT NULL,
  `TokenCode` varchar(255) NOT NULL,
  `Typ` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `benutzungserlaubnis`
--

CREATE TABLE `benutzungserlaubnis` (
  `ErlaubnisID` int(11) NOT NULL,
  `WerkBenutzerID` int(11) NOT NULL,
  `MaschineID` int(11) NOT NULL,
  `ErteiltAm` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `maschinenimwerkstattbereich`
--

CREATE TABLE `maschinenimwerkstattbereich` (
  `MaschineID` int(11) NOT NULL,
  `Bezeichnung` varchar(100) NOT NULL,
  `WerkBereichID` int(11) NOT NULL,
  `NotwendigeSchulungsID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `maschinenimwerkstattbereich`
--

INSERT INTO `maschinenimwerkstattbereich` (`MaschineID`, `Bezeichnung`, `WerkBereichID`, `NotwendigeSchulungsID`) VALUES
(1, '3D-Drucker', 1, 1),
(2, 'Laser-Cutter', 1, 2),
(4, 'Bohren & Fräsen', 1, 3);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `maschinenschulungen`
--

CREATE TABLE `maschinenschulungen` (
  `MaschinenSchulungsID` int(11) NOT NULL,
  `Bezeichnung` varchar(100) NOT NULL,
  `MaschinenGruppe` varchar(100) NOT NULL,
  `Beschreibung` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `maschinenschulungen`
--

INSERT INTO `maschinenschulungen` (`MaschinenSchulungsID`, `Bezeichnung`, `MaschinenGruppe`, `Beschreibung`) VALUES
(1, '3D-Drucker', '3D-Druck', 'Sicherheitsunterweisung'),
(2, 'Laser-Cutter', 'Laser', 'Sicherheitsunterweisung'),
(3, 'Bohren & Fräsen', 'Metall- / Holzbearbeitung', 'Sicherheitsunterweisung'),
(4, 'sonstiges', 'sonstiges', 'Sicherheitsunterweisung');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `systemlogs`
--

CREATE TABLE `systemlogs` (
  `LogID` int(11) NOT NULL,
  `WerkBenutzerID` int(11) NOT NULL,
  `Ereignis` varchar(255) NOT NULL,
  `Zeitpunkt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `systemlogs`
--

INSERT INTO `systemlogs` (`LogID`, `WerkBenutzerID`, `Ereignis`, `Zeitpunkt`) VALUES
(1, 1, 'Login erfolgreich', '2026-01-13 15:18:48'),
(2, 2, 'Login erfolgreich', '2026-01-13 15:19:38'),
(3, 1, 'Login erfolgreich', '2026-01-13 15:20:35'),
(4, 2, 'Login erfolgreich', '2026-01-13 15:35:13'),
(5, 3, 'Login erfolgreich', '2026-01-13 15:35:36'),
(6, 1, 'Login erfolgreich', '2026-01-13 15:35:55'),
(7, 1, 'Login erfolgreich', '2026-01-13 17:41:29'),
(8, 2, 'Login erfolgreich', '2026-01-13 17:42:59'),
(9, 1, 'Login erfolgreich', '2026-01-14 09:19:42');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `werkstattbenutzer`
--

CREATE TABLE `werkstattbenutzer` (
  `WerkBenutzerID` int(11) NOT NULL,
  `Vorname` varchar(50) NOT NULL,
  `Nachname` varchar(50) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Passwort` varchar(255) NOT NULL,
  `Rolle` varchar(20) DEFAULT 'Teilnehmer',
  `Klasse` varchar(50) DEFAULT NULL,
  `ErstelltAm` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `werkstattbenutzer`
--

INSERT INTO `werkstattbenutzer` (`WerkBenutzerID`, `Vorname`, `Nachname`, `Username`, `Passwort`, `Rolle`, `Klasse`, `ErstelltAm`) VALUES
(1, 'System', 'Administrator', 'admin', '$2y$10$XHMEB870qVI5XQIJEcTF/.dFIm3RWBf5Uo4YqhrADZqoRSHRRk5HO', 'Admin', NULL, '2026-01-12 07:31:02'),
(2, 'Karin', 'Gratzel', 'kgratzel', '$2y$10$xdHA.AFPYrrIZO4ptAfdxO3AN.1OFqGUN/99/G.JHvTOgxvkdbS8O', 'Mitglied', NULL, '2026-01-13 14:54:32'),
(3, 'Clemens', 'Eismayr', 'clemens', '$2y$10$62FjVx2mf1skaVS2HUtSJeakomCCjqub5j3R6WOg9jgIXrN7r4Jya', 'Mitglied', NULL, '2026-01-13 14:54:58');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `werkstattbenutzerschulungen`
--

CREATE TABLE `werkstattbenutzerschulungen` (
  `ID` int(11) NOT NULL,
  `WerkBenutzerID` int(11) NOT NULL,
  `MaschinenSchulungsID` int(11) NOT NULL,
  `AbschlussDatum` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `werkstattbereich`
--

CREATE TABLE `werkstattbereich` (
  `WerkBereichID` int(11) NOT NULL,
  `Bezeichnung` varchar(100) NOT NULL,
  `Ort` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `werkstattbereich`
--

INSERT INTO `werkstattbereich` (`WerkBereichID`, `Bezeichnung`, `Ort`) VALUES
(1, 'Hauptwerkstatt', 'Untergeschoss');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `werkstattsbereichauthentifizierungen`
--

CREATE TABLE `werkstattsbereichauthentifizierungen` (
  `WerkBereichAuthID` int(11) NOT NULL,
  `WerkBereichID` int(11) NOT NULL,
  `AuthArt` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `zutrittsberechtigungen`
--

CREATE TABLE `zutrittsberechtigungen` (
  `ZutrittID` int(11) NOT NULL,
  `WerkBenutzerID` int(11) NOT NULL,
  `WerkBereichID` int(11) NOT NULL,
  `StartDatum` date NOT NULL,
  `EndeDatum` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `authentifizierungen`
--
ALTER TABLE `authentifizierungen`
  ADD PRIMARY KEY (`WerkBenutzerAuthID`),
  ADD KEY `WerkBenutzerID` (`WerkBenutzerID`);

--
-- Indizes für die Tabelle `benutzungserlaubnis`
--
ALTER TABLE `benutzungserlaubnis`
  ADD PRIMARY KEY (`ErlaubnisID`),
  ADD KEY `WerkBenutzerID` (`WerkBenutzerID`),
  ADD KEY `MaschineID` (`MaschineID`);

--
-- Indizes für die Tabelle `maschinenimwerkstattbereich`
--
ALTER TABLE `maschinenimwerkstattbereich`
  ADD PRIMARY KEY (`MaschineID`),
  ADD KEY `WerkBereichID` (`WerkBereichID`),
  ADD KEY `NotwendigeSchulungsID` (`NotwendigeSchulungsID`);

--
-- Indizes für die Tabelle `maschinenschulungen`
--
ALTER TABLE `maschinenschulungen`
  ADD PRIMARY KEY (`MaschinenSchulungsID`);

--
-- Indizes für die Tabelle `systemlogs`
--
ALTER TABLE `systemlogs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `WerkBenutzerID` (`WerkBenutzerID`);

--
-- Indizes für die Tabelle `werkstattbenutzer`
--
ALTER TABLE `werkstattbenutzer`
  ADD PRIMARY KEY (`WerkBenutzerID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indizes für die Tabelle `werkstattbenutzerschulungen`
--
ALTER TABLE `werkstattbenutzerschulungen`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `WerkBenutzerID` (`WerkBenutzerID`),
  ADD KEY `MaschinenSchulungsID` (`MaschinenSchulungsID`);

--
-- Indizes für die Tabelle `werkstattbereich`
--
ALTER TABLE `werkstattbereich`
  ADD PRIMARY KEY (`WerkBereichID`);

--
-- Indizes für die Tabelle `werkstattsbereichauthentifizierungen`
--
ALTER TABLE `werkstattsbereichauthentifizierungen`
  ADD PRIMARY KEY (`WerkBereichAuthID`),
  ADD KEY `WerkBereichID` (`WerkBereichID`);

--
-- Indizes für die Tabelle `zutrittsberechtigungen`
--
ALTER TABLE `zutrittsberechtigungen`
  ADD PRIMARY KEY (`ZutrittID`),
  ADD KEY `WerkBenutzerID` (`WerkBenutzerID`),
  ADD KEY `WerkBereichID` (`WerkBereichID`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `authentifizierungen`
--
ALTER TABLE `authentifizierungen`
  MODIFY `WerkBenutzerAuthID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `benutzungserlaubnis`
--
ALTER TABLE `benutzungserlaubnis`
  MODIFY `ErlaubnisID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `maschinenimwerkstattbereich`
--
ALTER TABLE `maschinenimwerkstattbereich`
  MODIFY `MaschineID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `maschinenschulungen`
--
ALTER TABLE `maschinenschulungen`
  MODIFY `MaschinenSchulungsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT für Tabelle `systemlogs`
--
ALTER TABLE `systemlogs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `werkstattbenutzer`
--
ALTER TABLE `werkstattbenutzer`
  MODIFY `WerkBenutzerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT für Tabelle `werkstattbenutzerschulungen`
--
ALTER TABLE `werkstattbenutzerschulungen`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `werkstattbereich`
--
ALTER TABLE `werkstattbereich`
  MODIFY `WerkBereichID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT für Tabelle `werkstattsbereichauthentifizierungen`
--
ALTER TABLE `werkstattsbereichauthentifizierungen`
  MODIFY `WerkBereichAuthID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `zutrittsberechtigungen`
--
ALTER TABLE `zutrittsberechtigungen`
  MODIFY `ZutrittID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `authentifizierungen`
--
ALTER TABLE `authentifizierungen`
  ADD CONSTRAINT `authentifizierungen_ibfk_1` FOREIGN KEY (`WerkBenutzerID`) REFERENCES `werkstattbenutzer` (`WerkBenutzerID`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `benutzungserlaubnis`
--
ALTER TABLE `benutzungserlaubnis`
  ADD CONSTRAINT `benutzungserlaubnis_ibfk_1` FOREIGN KEY (`WerkBenutzerID`) REFERENCES `werkstattbenutzer` (`WerkBenutzerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `benutzungserlaubnis_ibfk_2` FOREIGN KEY (`MaschineID`) REFERENCES `maschinenimwerkstattbereich` (`MaschineID`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `maschinenimwerkstattbereich`
--
ALTER TABLE `maschinenimwerkstattbereich`
  ADD CONSTRAINT `maschinenimwerkstattbereich_ibfk_1` FOREIGN KEY (`WerkBereichID`) REFERENCES `werkstattbereich` (`WerkBereichID`),
  ADD CONSTRAINT `maschinenimwerkstattbereich_ibfk_2` FOREIGN KEY (`NotwendigeSchulungsID`) REFERENCES `maschinenschulungen` (`MaschinenSchulungsID`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `systemlogs`
--
ALTER TABLE `systemlogs`
  ADD CONSTRAINT `systemlogs_ibfk_1` FOREIGN KEY (`WerkBenutzerID`) REFERENCES `werkstattbenutzer` (`WerkBenutzerID`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `werkstattbenutzerschulungen`
--
ALTER TABLE `werkstattbenutzerschulungen`
  ADD CONSTRAINT `werkstattbenutzerschulungen_ibfk_1` FOREIGN KEY (`WerkBenutzerID`) REFERENCES `werkstattbenutzer` (`WerkBenutzerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `werkstattbenutzerschulungen_ibfk_2` FOREIGN KEY (`MaschinenSchulungsID`) REFERENCES `maschinenschulungen` (`MaschinenSchulungsID`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `werkstattsbereichauthentifizierungen`
--
ALTER TABLE `werkstattsbereichauthentifizierungen`
  ADD CONSTRAINT `werkstattsbereichauthentifizierungen_ibfk_1` FOREIGN KEY (`WerkBereichID`) REFERENCES `werkstattbereich` (`WerkBereichID`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `zutrittsberechtigungen`
--
ALTER TABLE `zutrittsberechtigungen`
  ADD CONSTRAINT `zutrittsberechtigungen_ibfk_1` FOREIGN KEY (`WerkBenutzerID`) REFERENCES `werkstattbenutzer` (`WerkBenutzerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `zutrittsberechtigungen_ibfk_2` FOREIGN KEY (`WerkBereichID`) REFERENCES `werkstattbereich` (`WerkBereichID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
