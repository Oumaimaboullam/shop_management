# Cahier des Charges - Système de Gestion de Magasin (Shop Management System)

## 1. Présentation du Projet
Ce projet consiste en une application web de gestion commerciale complète (Point de Vente - POS, inventaire, clients, fournisseurs et analytique) conçue pour les magasins et commerces de détail ainsi que de gros.
L'objectif est d'offrir une plateforme centralisée, rapide et réactive permettant de traiter les ventes au comptoir, gérer le stock en temps réel, émettre des factures et suivre les finances.

## 2. Public Cible & Rôles
Le système est conçu avec un contrôle d'accès basé sur les rôles (RBAC) pour s'adapter à la hiérarchie classique d'un magasin :
- **Administrateur (Admin)** : Accès total à toutes les fonctionnalités, à la configuration de la boutique, à l'analytique globale et à la gestion des utilisateurs.
- **Manager (Gérant)** : Accès complet à l'inventaire, aux fournisseurs, aux retours et aux rapports, mais limité sur les configurations sensibles.
- **Caissier (Cashier)** : Accès restreint au traitement des ventes (Point de Vente - POS) et à la gestion des retours clients.

## 3. Fonctionnalités Principales (Périmètre Fonctionnel)

### 3.1 Point de Vente (POS) et Ventes
- **Interface Caisse Intuitive** : Ajout d'articles au panier par recherche (nom) ou par scan de code-barres.
- **Double Tarification** : Bascule instantanée entre "Prix de détail" et mode "Grossiste" (Prix de gros).
- **Gestion des Clients** :
  - **Vente comptant** (Client de passage sans suivi).
  - **Vente à crédit/compte client** : Assignation d'une vente à un client enregistré, gestion des avances (acomptes) sur paiement direct.
- **Sauvegarde et Brouillons** : Possibilité de suspendre une vente (sauvegarde en brouillon) pour la retrouver plus tard sans bloquer la file d'attente.
- **Types de Documents** : Génération de tickets de caisse, Factures, et Devis (Quotes).
- **Moyens de Paiement Flexibles** : Espèces, Carte bancaire, Virement, Chèques, etc.
- **Remises** : Application de remises globales en pourcentage.

### 3.2 Gestion des Stocks & Inventaire
- **Articles & Catégories** : Création/édition de produits, assignation de catégories et alertes de stock minimum.
- **Code-barres** : Intégration pour la lecture rapide.
- **Alertes de Rupture** : Notifications visuelles automatiques lorsque le niveau de stock d'un produit passe sous son seuil d'alerte afin de faciliter le réapprovisionnement.

### 3.3 Fournisseurs & Achats
- **Suivi des Fournisseurs** : Base de données des fournisseurs du magasin.
- **Entrées de Stock** : Enregistrement des bons de commande/achats entrants et mise à jour automatique du stock local.

### 3.4 Suivi et Relation Clientèle (CRM Basique)
- **Fiches Clients** : Contacts, historique d'achats, solde (balance) de crédits.
- **Règlements Différés** : Suivi du solde restant dû par client et possibilité d'enregistrer ultérieurement le paiement du reste du montant.

### 3.5 Retours & Historique
- **Historique** : Accès complet aux archives des factures, tickets et transactions.
- **Gestion des Retours** : Traitement des retours de ventes (annulation partielle ou totale d'une précédente transaction) avec réintégration en stock et réajustement des montants de caisse.

### 3.6 Reporting et Analytique
- **Tableau de Bord** : Widgets de statistiques rapides (ventes du jour, chiffre d'affaires, produits en alerte).
- **Analytique Avancée** : Représentations graphiques de la performance (Chart.js), analyses des périodes et suivi des tendances.

## 4. Contraintes Techniques (Exigences Non-Fonctionnelles)
### 4.1 Technologies Utilisées
- **Backend** : PHP 8+ (Approche procédurale structurée et requêtes base de données sécurisées via PDO).
- **Base de Données** : MySQL (Schéma relationnel : clients, articles, ventes, retours, etc.).
- **Frontend / UI** :
  - HTML5 / CSS3 propulsé par le framework utilitaire **Tailwind CSS** intégré par CDN.
  - Interactivité via JavaScript natif (Vanilla JS, requêtes asynchrones `fetch` pour l'API RESTful maison).
  - **Icônes** : FontAwesome 6.
  - **Graphiques** : Chart.js pour l'analytique visuelle.

### 4.2 Architecture
- Le système opère comme une **Multi-Page Application (MPA)** servie par PHP, enrichie par des API endpoints (`/api/*`) légers retournant du JSON pour assurer la rapidité requise d'un système de point de vente.

### 4.3 Expérience Utilisateur (UX) & Ergonomie
- **Design "Glassmorphism" et Moderne** : Interface soignée utilisant des animations fluides (Tailwind config customisée), dégradés, et notifications non intrusives basées sur des alertes temporaires.
- **Responsive** : Le design doit s'adapter (menus rétractables, listes défilables) aux écrans modernes larges comme aux petits écrans et tablettes en boutique.
- **Multilinguisme** : Support de traduction via des dictionnaires natifs en PHP et exportables en JS (`/includes/lang/*.php`).

## 5. Perspectives d'Évolutivité
Ce cahier des charges décrit le système de base implémenté. La structure basée sur un backend MVC "light" (API/Templates) permet une future connexion à un système e-commerce frontal ou la mutualisation informatique d'une franchise de magasins (Multi-boutiques) moyennant un ajustement mineur de la base de données.
