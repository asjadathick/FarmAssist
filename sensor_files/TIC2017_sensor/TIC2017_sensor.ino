// Inclusion of Header files
// For Quectel 4G Sheild
#include <connection4g.h>
#include <telstraiot.h>
#include <iotshield.h>
#include <shieldinterface.h>

// For BME280 Sensor
#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>

// Structure to store the values read by a BME280 Sensor
struct TPHvalue {
  float temperature;      // Value in degrees Celcius
  float pressure;         // Value in hPa
  float humidity;         // Value in %
};

// Function Prototypes
TPHvalue getTPHvalues();
void sendData(float temperature, float pressure, float humidity, int moisture);

// BME Sensor
Adafruit_BME280 bme;

// IOT Sheild
ShieldInterface shieldif;
IoTShield shield(&shieldif);
Connection4G conn(true, &shieldif);
TelstraIoT iotPlatform(&conn, &shield);
const char host[] = "tic2017team000.iot.telstra.com";
char id[8];
char tenant[32];
char username[32];
char password[32];

void setup() {
  Serial.begin(9600);         // Setup the serial connection for debugging
  delay(500);
  Serial.println("[Start] General Sensor Operation");
  // Check that the BME sensor is fucntioning correctly
  bool status = bme.begin();
  if (!status) {
    Serial.println("Error: BME280 Sensor is not fucntioning correctly.\n");
  }

  // Check if the IOT Sheild is ready
  if (!shield.isShieldReady()) {
    Serial.println("Waiting for sheild to become ready.");
    shield.waitUntilShieldIsReady();
  }

  // Setup Telstra Sheild
  shield.readCredentials(id, tenant, username, password);
  iotPlatform.setCredentials(id, tenant, username, password, "");
  iotPlatform.setHost(host, 443);
  conn.openTCP(host, 443);

  delay(1000);
}

void loop() {
  int moistureValue;

  // Get the values from the BME280 Sensor
  TPHvalue tph = getTPHvalues();
  Serial.print("Temperature: ");
  Serial.print(tph.temperature);
  Serial.print(" *C\nPressure: ");
  Serial.print(tph.pressure);
  Serial.print("hPa\nHumidity: ");
  Serial.print(tph.humidity);
  Serial.println("%");

  // Get the values from the moisture sensor
  moistureValue = analogRead(A0);
  Serial.print("Moisture Reading: ");
  Serial.println(moistureValue);

  // Send the values to IOT platform
  sendData(tph.temperature, tph.pressure, tph.humidity, moistureValue);

  //delay(60000);     // 1 Minute delay beofre next reading
  delay(1000);
}

// Reads all output of the BME280 Sensor to determine temperature, pressure and humididty
TPHvalue getTPHvalues() {
  TPHvalue values;
  values.temperature = bme.readTemperature();
  values.pressure = bme.readPressure() / 100.0F;
  values.humidity = bme.readHumidity();
  return values;
}

// Send the data
void sendData(float temperature, float pressure, float humidity, int moisture) {
  char temp_string[256];
  char press_string[10];
  char hum_string[10];
  char moist_string[10];
  dtostrf(temperature, 3, 5, temp_string);
  dtostrf(pressure, 4, 2, press_string);
  dtostrf(humidity, 4, 2, hum_string);
  dtostrf(moisture, 5, 0, moist_string);

  iotPlatform.sendMeasurement("Temperature", "Temperature", "Temperature (*C)", temp_string, "*C");
    iotPlatform.sendMeasurement("Pressure", "Pressure", "Presure (hPa)", press_string, "hPa");
    iotPlatform.sendMeasurement("Humidity", "Humidity", "Humidity (%)", hum_string, "%");
    iotPlatform.sendMeasurement("Moisture", "Moisture", "Moisture ()", moist_string, "");

  return;
}
