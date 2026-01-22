# 🚀 Logique du Bouton "Commencer" - Vue d'ensemble

## 📋 Flux complet

### 1️⃣ **Conducteur clique "Commencer"** (Mes trajets)
- **Fichier:** `Outils/trips/Mes_trajets.php`
- **Action:** `?route=mes-trajets&action=commencer&trajet_id=XXX`
- **Base de données:** 
  - ✅ Mise à jour du statut du trajet: `'publié'` → `'en cours'`
  - ✅ Marque `conductor_started = 1`
- **Affichage:** Le bouton "Commencer" disparaît, le bouton "Terminer" apparaît

### 2️⃣ **Passagers cliquent "Commencer"** (Mes réservations)
- **Fichier:** `Outils/reservations/Mes_reservations.php`
- **Action:** Appel à `confirm_passenger_start.php` via fetch POST
- **Base de données:**
  - ✅ Insère/met à jour dans `reservations_started`: `passenger_confirmed = 1`
  - ✅ Vérifie si **TOUS** les passagers ont confirmé
  - ✅ Vérifie si le conducteur a aussi confirmé (`conductor_started = 1`)
- **Réponse:** 
  ```json
  {
    "success": true,
    "all_confirmed": true,
    "confirmed_passengers": 2,
    "total_passengers": 2
  }
  ```

### 3️⃣ **Logique de vérification**
```
Le trajet peut VRAIMENT commencer si et seulement si:
✅ Statut du trajet = 'en cours'
✅ conductor_started = 1 (conducteur a confirmé)
✅ TOUS les passagers ont passenger_confirmed = 1
```

### 4️⃣ **Conducteur clique "Terminer"** (Mes trajets)
- **Statut:** `'en cours'` → `'terminée'`
- **Affichage:** Les boutons disparaissent, trajet marqué comme "Terminé"

---

## 🗂️ Structure des tables

### Table `trajet`
```sql
CREATE TABLE `trajet` (
  ...
  `statut` enum('brouillon','publie','publié','en cours','terminée','supprime','supprimé'),
  `conductor_started` TINYINT(1) DEFAULT 0,
  `passenger_started` TINYINT(1) DEFAULT 0
);
```

### Table `reservations_started`
```sql
CREATE TABLE `reservations_started` (
  `ReservationID` int(11) PRIMARY KEY,
  `passenger_confirmed` TINYINT(1) DEFAULT 0,
  `confirmed_at` TIMESTAMP NULL,
  FOREIGN KEY (`ReservationID`) REFERENCES `reservations`(`ReservationID`)
);
```

---

## 🔄 Flux visuel

```
CONDUCTEUR SIDE                          PASSAGER SIDE
│                                        │
├─ Publie trajet                        │
│  (statut: 'publié')                   │
│                                        ├─ Réserve places
│                                        │  (réservation: 'confirmée')
│                                        │
├─ Clique "Commencer"                   │
│  (statut: 'en cours'                  │
│   conductor_started: 1)               │
│                                        ├─ Clique "Commencer"
│                                        │  (passenger_confirmed: 1)
│                                        │
│  ✅ Trajet commence vraiment           │
│  (tous confirmés)                      │
│                                        │
├─ Clique "Terminer"                    │
│  (statut: 'terminée')                 │
│                                        ├─ Peut noter/commenter
│                                        │
```

---

## ✅ Vérification à faire

Avant de commencer :
1. [ ] Exécuter la migration SQL: `SQL/migrate_trajet_statuses.sql`
2. [ ] Vérifier que les colonnes existent en base de données
3. [ ] Tester que le conducteur peut cliquer "Commencer"
4. [ ] Tester que les passagers voient le bouton "Commencer"
5. [ ] Vérifier que le trajet passe en "en cours" uniquement quand tous confirmés
6. [ ] Vérifier que le bouton "Terminer" apparaît correctement
