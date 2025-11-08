CREATE TABLE `Trajet` (
    `TrajetID` int  NOT NULL ,
    `VoitureID` int  NOT NULL ,
    `Ville_de_départ` string  NOT NULL ,
    `Date_de_départ` string  NOT NULL ,
    `PrixID` float  NOT NULL ,
    `Ville_d-arrivé` string  NOT NULL ,
    `Description` string  NOT NULL 
);

CREATE TABLE `User` (
    `UserID` int  NOT NULL ,
    `Nom` string  NOT NULL ,
    `Prénom` string  NOT NULL ,
    `Genre` string  NOT NULL ,
    `Age` string  NOT NULL ,
    `Decription` string  NOT NULL ,
    `Avis` string  NOT NULL ,
    `Photo_de_profil` jpg  NOT NULL ,
    `Numero` string  NOT NULL ,
    `Mails` string  NOT NULL ,
    `Mot_de_passe` string  NOT NULL ,
    `adresse` string  NOT NULL 
);

CREATE TABLE `Reservation` (
    `ReservationID` int  NOT NULL ,
    `TrajetID` int  NOT NULL ,
    `Statut` string  NOT NULL ,
    `PassagerID` int  NOT NULL 
);

CREATE TABLE `Voitures` (
    `VoitureID` int  NOT NULL ,
    `Modèle` string  NOT NULL ,
    `Plaque` string  NOT NULL ,
    `Fiche_technique` jpg  NOT NULL ,
    `UserID` int  NOT NULL 
);

CREATE TABLE `Cagnotte` (
    `CagnotteID` int  NOT NULL ,
    `Valeur` string  NOT NULL ,
    `UserID` int  NOT NULL 
);

CREATE TABLE `Passager` (
    `PassagerID` int  NOT NULL ,
    `UserID` int  NOT NULL 
);

CREATE TABLE `Boite_de_Messagerie` (
    `MessagerieID` int  NOT NULL ,
    `MessageID` int  NOT NULL 
);

CREATE TABLE `Message` (
    `MessageID` int  NOT NULL ,
    `Contenue` string  NOT NULL ,
    `UserID` int  NOT NULL ,
    `ReservationID` int  NOT NULL 
);

ALTER TABLE `Trajet` ADD CONSTRAINT `fk_Trajet_TrajetID` FOREIGN KEY(`TrajetID`)
REFERENCES `Reservation` (`TrajetID`);

ALTER TABLE `Reservation` ADD CONSTRAINT `fk_Reservation_ReservationID` FOREIGN KEY(`ReservationID`)
REFERENCES `Message` (`ReservationID`);

ALTER TABLE `Voitures` ADD CONSTRAINT `fk_Voitures_VoitureID` FOREIGN KEY(`VoitureID`)
REFERENCES `Trajet` (`VoitureID`);

ALTER TABLE `Voitures` ADD CONSTRAINT `fk_Voitures_UserID` FOREIGN KEY(`UserID`)
REFERENCES `User` (`UserID`);

ALTER TABLE `Cagnotte` ADD CONSTRAINT `fk_Cagnotte_UserID` FOREIGN KEY(`UserID`)
REFERENCES `User` (`UserID`);

ALTER TABLE `Passager` ADD CONSTRAINT `fk_Passager_PassagerID` FOREIGN KEY(`PassagerID`)
REFERENCES `Reservation` (`PassagerID`);

ALTER TABLE `Passager` ADD CONSTRAINT `fk_Passager_UserID` FOREIGN KEY(`UserID`)
REFERENCES `User` (`UserID`);

ALTER TABLE `Message` ADD CONSTRAINT `fk_Message_MessageID` FOREIGN KEY(`MessageID`)
REFERENCES `Boite_de_Messagerie` (`MessageID`);

