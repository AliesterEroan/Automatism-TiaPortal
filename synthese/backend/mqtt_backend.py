import time
from pathlib import Path
import sqlite3
import paho.mqtt.client as mqtt
from paho.mqtt.enums import CallbackAPIVersion

BROKER = "test.mosquitto.org"
PORT   = 1883
MQTT_CLIENT_ID = "pi5-backend-lab-synchronized"

# Absolute path to your production SQLite database engine
DB_PATH = Path("/var/www/html/SYNTHESE/backend/lab.db")

SENSOR_TOPIC = "lab/esp32/+/dht22/+"
LED_CMD_TOPIC_TEMPLATE = "lab/esp32/{node_id}/led/{led_id}/set"
POLL_COMMANDS_INTERVAL = 0.2

# In-memory dictionary cache to merge separate temperature & humidity payloads
node_cache = {}

def get_db():
    # timeout=10.0 allows concurrent threads to safely queue up without throwing locks
    conn = sqlite3.connect(DB_PATH, timeout=10.0)
    conn.row_factory = sqlite3.Row
    return conn

def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        print(f"Connected to MQTT Broker ({BROKER}). Subscribing to: {SENSOR_TOPIC}")
        client.subscribe(SENSOR_TOPIC, qos=0)
    else:
        print(f"MQTT Connection failed with status code: {rc}")

def on_disconnect(client, userdata, rc, properties=None):
    print(f"Disconnected from MQTT Broker with code: {rc}")

def on_message(client, userdata, msg):
    topic = msg.topic
    payload = msg.payload.decode("utf-8", errors="ignore")
    print(f"Received MQTT data -> {topic}: {payload}")

    parts = topic.split("/")
    if len(parts) != 5:
        return

    _, _, node_id, sensor, metric = parts
    if sensor != "dht22":
        return

    try:
        value = float(payload)
    except ValueError:
        return

    # Warm up cache profile for new hardware IDs
    if node_id not in node_cache:
        node_cache[node_id] = {"temperature": None, "humidity": None}

    conn = get_db()
    cur = conn.cursor()

    if metric == "temperature":
        node_cache[node_id]["temperature"] = value
        # If humidity is already waiting in cache, merge and commit a single rows array
        if node_cache[node_id]["humidity"] is not None:
            cur.execute(
                "INSERT INTO measurements (node_id, temperature, humidity) VALUES (?, ?, ?)",
                (node_id, node_cache[node_id]["temperature"], node_cache[node_id]["humidity"])
            )
            node_cache[node_id] = {"temperature": None, "humidity": None}
            
    elif metric == "humidity":
        node_cache[node_id]["humidity"] = value
        # If temperature is already waiting in cache, merge and commit a single rows array
        if node_cache[node_id]["temperature"] is not None:
            cur.execute(
                "INSERT INTO measurements (node_id, temperature, humidity) VALUES (?, ?, ?)",
                (node_id, node_cache[node_id]["temperature"], node_cache[node_id]["humidity"])
            )
            node_cache[node_id] = {"temperature": None, "humidity": None}

    conn.commit()
    conn.close()

def process_led_commands(client: mqtt.Client):
    conn = get_db()
    cur = conn.cursor()
    
    # Track outgoing requests queued up by api/led_command.php
    cur.execute(
        "SELECT id, node_id, led_id, color FROM led_commands WHERE status='pending'"
    )
    rows = cur.fetchall()

    for row in rows:
        cmd_id, node_id, led_id, color = row['id'], row['node_id'], row['led_id'], row['color']
        
        topic = LED_CMD_TOPIC_TEMPLATE.format(node_id=node_id, led_id=led_id)
        payload = color.upper()
        
        print(f"Publishing Outbound Command -> {topic} : {payload}")
        client.publish(topic, payload, qos=1)
        
        cur.execute(
            "UPDATE led_commands SET status='sent' WHERE id=?",
            (cmd_id,)
        )

    conn.commit()
    conn.close()

def main():
    # Guarantee base folder tracks exist before spinning up threads
    DB_PATH.parent.mkdir(parents=True, exist_ok=True)

    # Clean implementation utilizing modern API Version 2 definitions
    client = mqtt.Client(callback_api_version=CallbackAPIVersion.VERSION2, client_id=MQTT_CLIENT_ID)
    
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    client.on_message = on_message

    client.connect(BROKER, PORT, keepalive=60)
    client.loop_start()

    print("Background synchronization thread active. Awaiting data loops...")
    try:
        while True:
            process_led_commands(client)
            time.sleep(POLL_COMMANDS_INTERVAL)
    except KeyboardInterrupt:
        print("\nStopping background MQTT thread...")
    finally:
        client.loop_stop()
        client.disconnect()
        print("MQTT network link closed cleanly.")

if __name__ == "__main__":
    main()