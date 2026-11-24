# Plugin Atlas

Le plugin Atlas est un outil essentiel pour gérer votre box Jeedom Atlas. Il vous permet de configurer le WiFi, créer un point d'accès (Hotspot) et surtout, de **restaurer complètement votre système** en cas de problème.

---

##  Restauration Système (Recovery Mode)

### Pourquoi utiliser la restauration système ?

La fonctionnalité de restauration système est **cruciale** pour votre box Atlas. Elle vous permet de :

- **Restaurer votre système** en cas de dysfonctionnement grave
- **Réinstaller Jeedom** à partir d'une image propre
- **Récupérer votre box** si elle ne démarre plus correctement
- **Créer une clé USB de restauration** pour une intervention d'urgence

### ⚠️ Important : Préparez votre backup

> **ATTENTION** : Avant toute restauration système, il est **IMPÉRATIF** de :
> 1. **Créer une sauvegarde complète** de votre Jeedom
> 2. **Télécharger cette sauvegarde en local** sur votre ordinateur
> 3. **Vérifier que la sauvegarde est complète** et accessible
>
> La restauration système **effacera toutes vos données** !

### Comment accéder au Recovery Mode ?

**Depuis Jeedom (Core 4.4.20 et supérieur) :**

La fonctionnalité de recovery est désormais **intégrée directement dans le core de Jeedom**, ce qui la rend plus stable et accessible.

📖 **Documentation officielle** : [https://doc.jeedom.com/fr_FR/installation/recovery](https://doc.jeedom.com/fr_FR/installation/recovery)

###  Deux modes de restauration disponibles

#### 1. Restauration sur mémoire interne (EMMC)

Cette option restaure directement votre système sur la mémoire interne de la box Atlas :

- **Durée moyenne** : environ 15 minutes
- **Procédure** : automatique, guidée par l'interface
- **Après restauration** : retirer la clé USB puis débrancher/rebrancher électriquement la box

#### 2. Création d'une clé USB de restauration

Cette option crée une clé USB bootable pour restaurer votre système :

- **Prérequis** : clé USB de 10 Go minimum (sera formatée)
- **Brancher** : sur le port USB noir du bas à droite de la box
- **Durée** : environ 15 minutes
- **Après création** : redémarrer la box avec la clé USB branchée

###  Étapes de restauration détaillées

1. **Préparation**
   - Téléchargez votre backup en local
   - Préparez une clé USB de 10 Go minimum (si nécessaire)
   - Assurez-vous d'avoir accès au réseau

2. **Lancement**
   - Accédez au mode Recovery depuis Jeedom
   - Choisissez votre méthode (EMMC ou USB)
   - Cliquez sur "LANCER"

3. **Processus automatique**
   - Téléchargement de l'image Jeedom (si nécessaire)
   - Vérification de l'image
   - Copie et installation (affichage de la progression)
   - Finalisation du système

4. **Finalisation**
   - Suivez les instructions à l'écran
   - Redémarrage automatique ou manuel selon le mode
   - Reconnexion à Jeedom
   - Restauration de votre backup

###  Astuces et recommandations

- **Sauvegardez régulièrement** : programmez des backups automatiques
- **Testez vos sauvegardes** : vérifiez qu'elles peuvent être restaurées
- **Gardez une clé USB de recovery** : pratique en cas d'urgence
- **Notez vos identifiants** : vous en aurez besoin après la restauration
- **Patience** : la restauration prend du temps, ne l'interrompez pas

---

##  Configuration WiFi

Le plugin Atlas vous permet de connecter facilement votre box Atlas à un réseau WiFi (avec ou sans mot de passe).

### Configuration

1. Allez dans **Plugins → Communication → Atlas**
2. Sélectionnez l'équipement **Wifi**
3. **Activez** l'équipement
4. Sélectionnez votre réseau WiFi (2.4 GHz ou 5 GHz)
5. Entrez le **mot de passe** (si nécessaire)
6. **Sauvegardez**

> **Note** : Pour le moment, le plugin prend en compte uniquement les accès avec DHCP.

---

##  Hotspot WiFi (BETA)

### Fonctionnalité

Le mode Hotspot vous permet de transformer votre box Atlas en **point d'accès WiFi**, créant ainsi un réseau sans fil auquel d'autres appareils peuvent se connecter.

### Caractéristiques

- **DNS fonctionnel** : résolution de noms de domaine
- **Forwarding IP** : pont automatique entre l'interface Ethernet et WiFi
- **Partage de connexion** : les clients peuvent accéder à Internet via la box

### Activation

1. Dans la partie **Wifi** du plugin
2. Cochez la case **Hotspot**
3. Configurez le nom du réseau et le mot de passe
4. **Sauvegardez** et activez

> ⚠️ **Attention** : Cette fonctionnalité est en version BETA. Des améliorations sont en cours.

### Adresse IP du hotspot

Le hotspot crée son propre réseau en `/24`. Le champ **Adresse IP du hotspot** définit l'adresse de la box sur ce réseau (par défaut `192.168.50.1`, le réseau est alors `192.168.50.0/24`).

- Les appareils qui se connectent reçoivent automatiquement une adresse entre `.10` et `.250`.
- Les adresses `.2` à `.9` et `.251` à `.254` ne sont jamais distribuées : vous pouvez les configurer manuellement sur vos équipements (IP fixe), avec comme passerelle et DNS l'adresse du hotspot.
- Le réseau choisi ne doit pas être déjà utilisé par votre réseau local (Ethernet). Si c'est le cas, ou si l'adresse saisie est invalide, le plugin l'indique dans les logs et le hotspot démarre sur un réseau aléatoire : les adresses des appareils changent alors à chaque démarrage.

### Réservations DHCP

Le champ **Réservations DHCP** permet d'attribuer toujours la même adresse IP à un appareil (caméra, capteur...) sans toucher à sa configuration. Saisissez une réservation par ligne : adresse MAC, adresse IP et, en option, un nom.

```
AA:BB:CC:DD:EE:FF 192.168.50.20 Caméra
11-22-33-44-55-66;192.168.50.21
```

- L'adresse IP doit appartenir au réseau du hotspot et ne pas être l'adresse du hotspot.
- Une même adresse MAC ou IP ne peut apparaître qu'une fois.
- Les lignes invalides sont ignorées et signalées dans les logs du plugin.
- Les réservations ne sont appliquées que si l'adresse IP du hotspot est fixe (voir ci-dessus).

Après la sauvegarde, débranchez puis rebranchez l'appareil (ou redémarrez-le) pour qu'il récupère sa nouvelle adresse.

### Redémarrage du hotspot

Le plugin vérifie régulièrement l'état du hotspot. Il n'est redémarré que s'il est arrêté ou si sa configuration (nom, clé, adresse IP, réservations) a été modifiée : les appareils connectés ne sont pas déconnectés inutilement.

---

##  Besoin d'aide ?

Une question ? Un problème ? N'hésitez pas à consulter la communauté :

 **[Community Jeedom](https://community.jeedom.com/)**

---

##  Ressources complémentaires

- [Documentation officielle du Recovery](https://doc.jeedom.com/fr_FR/installation/recovery)
- [Guide des sauvegardes Jeedom](https://doc.jeedom.com/fr_FR/core/4.4/backup)
- [Forum Community Jeedom](https://community.jeedom.com/)
