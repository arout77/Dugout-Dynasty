![Dugout Dynasty](public/img/logo.webp)


# ⚾ Dugout Dynasty: Historical Baseball Simulation

**Dugout Dynasty** is a web-based baseball management simulation that allows users to draft real players from over 150 years of history (1871–2025) and compete in a realistic, physics-driven league. Built on the custom **Rhapsody PHP Framework**, it combines deep statistical analysis with a modern, interactive frontend.



## 🚀 Project Overview

**Dugout Dynasty** answers the ultimate baseball question: *How would Babe Ruth fare against modern pitching?*

Using a custom **simulation engine**, the simulator adjusts historical stats to a neutral era, allowing players from the Deadball Era (1900s) to compete fairly against Steroid Era sluggers (1990s) and today's velocity kings.

### Key Features

* **Historical Snake Draft:** Build a 25-man roster from a pool of 20,000+ historical players.
* **Era-Neutral Simulation:** A custom algorithm normalizes player stats (AVG, HR, ERA, etc.) based on their specific historical season context.
* **Live Game Management:** Manage games pitch-by-pitch with a React-powered interface. Call for bunts, steals, hit-and-runs, or bullpen changes in real-time.
* **Franchise Mode:** Manage salary caps, set lineups/rotations, and compete in a 162-game season with playoffs.
* **AI Personalities:** CPU opponents have distinct archetypes (e.g., "Moneyball," "Small Ball," "Steinbrenner") that influence their drafting and in-game strategy.

## 🛠️ Tech Stack

This project demonstrates a full-stack implementation of a complex simulation engine.

* **Backend:** PHP 8.2+ (My Rhapsody MVC Framework https://github.com/arout77/)
* **Frontend:**
    * **Core:** Twig Templating Engine
    * **Game Interface:** React.js (via "Island Architecture") + Vite
    * **Styling:** Tailwind CSS
* **Database:** MySQL 8.0 (PDO Abstraction Layer)
* **Infrastructure:** Docker / MAMP (Local Dev)

## 🧩 Architecture Highlights

### 1. The Simulation Engine (`App\Services\SimulationService`)
The heart of the application. It calculates the outcome of every at-bat using a modified **Log5 probability formula**

This ensures that a .400 hitter from 1920 doesn't automatically destroy a modern pitcher, as their stats are weighted against their respective league environments.

### 2. "React Island" Gameplay
While the management dashboard uses server-side rendering (Twig) for SEO and performance, the live game interface injects a **React application** into the view. This allows for:
* Instant state updates (runners on base, score, count).
* Interactive SVG field rendering with dynamic player positioning.
* Seamless context switching between Batting and Pitching controls.

### 3. Custom MVC Framework ("Rhapsody")
The app runs on a bespoke framework built from scratch, featuring:
* **Service Container:** Dependency injection for managing simulation services and database connections.
* **Router:** Custom regex-based routing with middleware support (Auth, CSRF).
* **CLI Runner:** A custom console application (`./rhapsody`) for handling migrations, data imports, and schedule generation.

## 📸 Screenshots

| Draft Room | Live Simulation | Dashboard |
| :---: | :---: | :---: |
| *Draft historical legends against AI* | *Manage games pitch-by-pitch* | *Track standings and leaders* |

## 🔮 Roadmap

* **Advanced Bullpen Logic:** Fatigue systems that degrade pitcher performance over high pitch counts.
* **Trade Engine:** CPU-to-User trade offers with logic to evaluate player value.
* **Multiplayer Leagues:** WebSocket implementation for live PvP drafting and games.

---

*Built with ❤️ for baseball history and code.*
