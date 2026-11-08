# Automatism — TIA Portal Projects

> A collection of Siemens TIA Portal V15.1 (`.ap18`) automation projects — PLC exercises around cameras, drives, and HMI — plus one folder (`synthese`) containing a complete MQTT↔PHP IoT dashboard lab.

The repo is primarily **binary TIA Portal project folders**: each top-level directory is a self-contained TIA project (device/configuration databases, GSD device descriptions, HMI data). The PLC logic itself lives in TIA's internal databases and is not readable as text. The exception is `synthese/`, which additionally ships a working PHP + Python + SQLite web app for monitoring ESP32 sensor nodes over MQTT.

---

## 📜 Table of Contents

- [✨ Features](#-features)
- [🚀 Installation](#-installation)
- [📖 Usage](#-usage)
- [🏗️ Architecture](#-architecture)
- [🛠️ Technologies Used](#-technologies-used)
- [⚠️ Limitations](#-limitations)
- [🔒 Privacy](#-privacy)
- [📜 License](#-license)
- [📧 Contact](#-contact)

---

## ✨ Features

### TIA Portal projects (`.ap18`)
- **`Camera/`** — project with GSDML device descriptions for a **Pepperl+Fuchs VOS vision system** (`gsdml-v2.4-pepperl+fuchs-vosxxxxf22x`) and a **Siemens G120P variable-frequency drive** (`gsdml-v2.31-siemens-sinamics_g120p`), plus a PLCm archive.
- **`Camera 2/`** — second camera project iteration (same VOS GSD file, PLCm archive).
- **`Testing 1/`** — minimal test project (no GSD files; PLCm archive only).
- **`test1-1/`** — another small test project.
- **`session2/`** — larger project including a **Siemens S7-1200 PCT target description** (`AdditionalFiles/TCI/Siemens/S7-PCT/…1052700-1398.pct`) and HMI broker/download data (`IM/HMI/`).
- **`session2.backup/`** — timestamped zip snapshots of `session2` (2026-05-22, 2026-06-03/05).
- **`synthese/`** — "synthesis" capstone project: its own TIA project **plus** a complete IoT web lab (below).

### `synthese/` IoT lab (web + backend)
- **Multi-node MQTT ingestion** — `backend/mqtt_backend.py` (paho-mqtt) subscribes to `lab/esp32/+/dht22/+`, merges separate temperature/humidity messages per node in an in-memory cache, and inserts rows into SQLite `backend/lab.db` (`measurements` table).
- **LED command channel** — the PHP API queues commands in a `led_commands` table; the Python daemon polls it every 0.2 s and publishes to `lab/esp32/{node}/led/{led}/set` (QoS 1), marking rows `sent`.
- **PHP frontend** — `index.php` (node-selection hub), `dashboard.php` (master status table), `led_control.php` (per-node switchboard), `metrics_chart.php` (telemetry charts), `api/led_command.php`, `api/sensors.php`.
- **Node registry** — node list is read from distinct `node_id`s in the DB, defaulting to `esp32-1`, `esp32-2`, `esp32-3`.

---

## 🚀 Installation

### TIA Portal projects
1. Install **Siemens TIA Portal V15.1** (the `.ap18` format) with a valid license.
2. Open the project folder (e.g. `synthese/synthese.ap18`) — TIA will load the hardware configuration, PLC program, and HMI.
3. GSD files for the VOS camera and G120P drive are already under each project's `AdditionalFiles/GSD/`.

### `synthese/` web lab
1. Python 3 with `paho-mqtt` (`pip install paho-mqtt`).
2. PHP with PDO SQLite; serve the `synthese/` directory under a web root.
3. Edit `backend/mqtt_backend.py`: `BROKER` (currently `test.mosquitto.org`) and `DB_PATH` (currently hardcoded `/var/www/html/SYNTHESE/backend/lab.db`).
4. Run `python backend/mqtt_backend.py`, then open the PHP pages.

---

## 📖 Usage

### TIA projects
- Open each `.ap18` project in TIA Portal to browse hardware config, PLC blocks, and HMI screens. `session2.backup/` contains zipped snapshots if you need to roll back.

### IoT lab
- ESP32 nodes publish scalar temperature/humidity to `lab/esp32/{node_id}/dht22/{temperature|humidity}`.
- `index.php?node=esp32-1` → Node Switchboard (`led_control.php`) to queue LED color commands, or Metrics Chart (`metrics_chart.php`) for history.
- `dashboard.php` shows the master multi-node status table.

---

## 🏗️ Architecture

```
automatism/
├── Camera/            # TIA V15.1 project: VOS camera + G120P drive GSDs
├── Camera 2/          # second camera project iteration
├── Testing 1/         # minimal test project
├── test1-1/           # small test project
├── session2/          # larger project (S7 PCT, HMI data)
│   └── (see also session2.backup/ timestamped zips)
├── session2.backup/   # project snapshots
└── synthese/          # capstone: TIA project + IoT web lab
    ├── synthese.ap18  # TIA project file
    ├── IM/, System/, Vci/, XRef/, AdditionalFiles/   # TIA internals
    ├── index.php      # node-selection hub
    ├── dashboard.php  # master status table
    ├── led_control.php, metrics_chart.php, led_command.php (api/), sensors.php (api/)
    ├── backend/
    │   ├── mqtt_backend.py   # MQTT → SQLite ingest + LED command dispatch
    │   └── lab.db            # SQLite database (measurements, led_commands)
    └── AdditionalFiles/      # UR device GSD + S7-PCT TCI files
```

### Data Flow (`synthese` lab)
1. ESP32 → MQTT `lab/esp32/{node}/dht22/{metric}` → `mqtt_backend.py` merges temp/humidity pairs → INSERT into `lab.db.measurements`.
2. Browser → `api/led_command.php` → INSERT pending row into `led_commands`.
3. `mqtt_backend.py` polls `led_commands` every 0.2 s → publishes color to `lab/esp32/{node}/led/{led}/set` → marks row `sent`.
4. PHP pages read `lab.db` directly (PDO SQLite) to render dashboards/charts.

---

## 🛠️ Technologies Used

### TIA Portal side
- **Siemens TIA Portal V15.1** (`.ap18`) - PLC/HMI project files
- **PROFINET GSDML** - device descriptions: Pepperl+Fuchs VOSxxxxF22x vision system, Siemens Sinamics G120P drive, UR device
- **Siemens S7 PCT / TCI** - target description & catalog in `session2`
- **PLCm archive** (`plcmArchive.pma15_0`) - PLC memory container in each project

### Web lab (`synthese/`)
- **Python + paho-mqtt** - MQTT bridge daemon (`CallbackAPIVersion.VERSION2`)
- **SQLite** - `lab.db` storage (`measurements`, `led_commands` tables)
- **PHP + PDO** - dashboard/API endpoints
- **HTML/CSS/JS** - dark-themed frontend (`index.php`, `dashboard.php`, `led_control.php`, `metrics_chart.php`)

---

## ⚠️ Limitations

### Accessibility
- **Binary project format** — `.ap18` projects require licensed TIA Portal V15.1 to open; there is no text-readable PLC source in the repo (logic lives in `IM/`/`System/` databases).
- **No documentation for the TIA projects** — folder names (`Camera`, `Testing 1`, `test1-1`, `session2`) are the only descriptions; intended function must be inferred from GSD devices.
- **`session2.backup/` duplicates** — zipped snapshots inflate the repo.

### Web lab
- **Hardcoded broker & DB path** — `mqtt_backend.py` targets `test.mosquitto.org:1883` (public, plaintext, no auth) and a Linux path `/var/www/html/SYNTHESE/backend/lab.db`; edit before running elsewhere.
- **No auth on the PHP side** — the dashboard endpoints have no login; fine on a LAN, unsafe exposed.
- **Command loop is poll-based** — LED commands are dispatched on a 0.2 s SQLite poll, not event-driven; latency up to ~200 ms plus DB lock contention (`timeout=10.0` mitigates).
- **Committed `lab.db`** — a live SQLite database is checked into the repo.

### Testing Limitations
- **What a clone can re-check**: the `synthese` PHP pages render and `mqtt_backend.py` runs against any reachable Mosquitto broker with a writable `lab.db` path.
- **What a clone cannot re-check**: TIA project contents (need licensed software + possibly the original PLC/hardware), and end-to-end ESP32→dashboard behavior without the physical nodes.

---

## 🔒 Privacy

The `synthese` lab uses the **public `test.mosquitto.org` broker over plaintext port 1883** — all `lab/esp32/...` telemetry and LED commands are world-visible to anyone subscribed. The PHP frontend has no authentication. TIA project internals may contain network/device addresses; review before sharing.

---

## 📜 License

Automatism is proprietary software — see [LICENSE](LICENSE) for the full terms.

**Copyright © 2026 AliesterEroan. All rights reserved.**

This software is provided for personal, non-commercial use only. Commercial use, redistribution, modification, and sublicensing are prohibited without explicit written permission. The copyright holder may distribute copies through the project's official GitHub Releases page; third parties may not host, mirror, or redistribute this software.

For licensing inquiries, open an issue at <https://github.com/AliesterEroan/Automatism-TiaPortal/issues>.

---

## 📧 Contact

- **GitHub**: [AliesterEroan/Automatism-TiaPortal](https://github.com/AliesterEroan/Automatism-TiaPortal)
- **Issues**: [Create an issue](https://github.com/AliesterEroan/Automatism-TiaPortal/issues)

---

*Built with ❤️ by AliesterEroan*
