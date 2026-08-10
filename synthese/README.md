# 🌐 IoT Multi-Node Synthesis System

> Complete IoT system with TIA Portal integration, web dashboard, and MQTT backend! 🏭

## ✨ Features

- 🌐 Multi-node web dashboard
- 📡 MQTT backend integration
- 💡 LED control for multiple nodes
- 📊 Real-time sensor monitoring
- 🎨 Modern dark interface
- 🗄️ SQLite database storage
- 🏭 TIA Portal PLC integration

## 🛠️ Requirements

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- SQLite database support
- MQTT broker
- Python 3 with paho-mqtt
- TIA Portal project integration

## 📋 Project Structure

```
synthese/
├── synthese.ap18          # TIA Portal project
├── index.php              # Central hub menu
├── dashboard.php          # Master network status
├── led_control.php        # LED control interface
├── metrics_chart.php      # Data visualization
├── api/
│   ├── led_command.php    # LED control API
│   └── sensors.php        # Sensor data API
└── backend/
    ├── lab.db             # SQLite database
    └── mqtt_backend.py    # MQTT subscriber
```

## 🚀 Getting Started

### 1. Web Dashboard Setup

**Deploy files:**
```bash
sudo cp -r synthese/* /var/www/html/iot-synthese/
```

**Set permissions:**
```bash
sudo chown -R www-data:www-data /var/www/html/iot-synthese/
sudo chmod -R 755 /var/www/html/iot-synthese/
```

**Access dashboard:**
```
http://your-server-ip/iot-synthese/
```

### 2. MQTT Backend Setup

**Install dependencies:**
```bash
pip3 install paho-mqtt sqlite3
```

**Run MQTT backend:**
```bash
cd backend
python3 mqtt_backend.py
```

### 3. TIA Portal Integration

**Open project:**
- Launch TIA Portal
- Open `synthese.ap18`
- Configure PLC integration

## 🔧 Configuration

**Database Setup:**
```php
// Database path in PHP files
$db_path = __DIR__ . '/backend/lab.db';
```

**MQTT Settings (backend/mqtt_backend.py):**
```python
MQTT_BROKER = "localhost"
MQTT_PORT = 1883
TOPICS = ["esp32-1/sensors", "esp32-2/sensors", "esp32-3/sensors"]
```

**Node Configuration:**
```php
// Default nodes in index.php
$nodes = ['esp32-1', 'esp32-2', 'esp32-3'];
```

## 📱 Web Interface Features

**Central Hub (index.php):**
- Node selection interface
- Quick access to control panels
- Master network status
- Navigation to different views

**Network Dashboard (dashboard.php):**
- Multi-node sensor monitoring
- Real-time temperature and humidity
- LED control for each node
- Auto-refresh every 4 seconds

**LED Control (led_control.php):**
- Individual node LED control
- Color selection (R/J/V/OFF)
- Real-time status indicators
- Visual feedback with glow effects

## 🎯 API Endpoints

**Sensor Data:**
```
GET /api/sensors.php
```

**LED Control:**
```
POST /api/led_command.php
Content-Type: application/json
{
  "node_id": "esp32-1",
  "led_id": "led1",
  "color": "R"
}
```

## ⚡ System Features

**Multi-Node Support:**
- ESP32-1, ESP32-2, ESP32-3
- Individual node control
- Collective monitoring
- Node status tracking

**LED Control:**
- Red (R), Yellow (J), Green (V), OFF
- 3 LEDs per node
- MQTT command relay
- Database state persistence

**Data Management:**
- SQLite database storage
- MQTT message handling
- Sensor data logging
- Command history tracking

## 🎨 Interface Design

**Dark Theme:**
- Industrial dark color scheme
- High contrast displays
- Neon accent colors
- Modern card-based layout

**User Experience:**
- Intuitive navigation
- Real-time updates
- Visual feedback
- Responsive design

## ⚠️ Notes

- Requires web server with PHP
- MQTT broker must be running
- Database needs write permissions
- TIA Portal integration optional
- Multiple ESP32 nodes recommended

## 🎓 Learning Objectives

- IoT system architecture
- MQTT integration with web
- Multi-node management
- Real-time dashboard development
- PLC and web integration

---

**Made with ❤️ by AliesterEroan**