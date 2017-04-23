exports = {};

const https = require("https");
const mysql = require("mysql");


const connection = mysql.createConnection({
    host: "iot.csosz45qwa0w.ap-southeast-2.rds.amazonaws.com",
    user: "root",
    password: "testavocado",
    database: "analytics"
});

function getData(fromDate, toDate, deviceId) {

	const url = "/measurement/measurements/series?source=" +
		deviceId + "&dateFrom=" + fromDate.toISOString() +
		"&dateTo=" + toDate.toISOString();

    const options = {
        hostname: "tic2017team045.iot.telstra.com",
        port: 443,
        path: url,
        method: "GET",
        headers: {
            Authorization: "Basic YXBpOlN0dWRlbnQyNTYxMDI0"
        }
    };

    return new Promise((resolve, reject) => {

        const req = https.request(options, (res) => {

            res.on("data", (d) => {
                resolve(d.toString());
            });

        });

        req.on("error", (e) => {
            reject(e);
        });

        req.end();

    });

}

function getLastRetrieved() {


    return new Promise((resolve, reject) => {

        const query = "SELECT timestamp FROM analytics.sensordata " +
            "ORDER BY timestamp DESC;";

        connection.query(query, function (error, results) {
            if (error)
                reject(error);

            resolve(results[0].timestamp);
        });

    });

}

function storeNew({moisture, pressure, temperature, humidity, ph, timestamp}) {


    return new Promise((resolve, reject) => {

        const query = "INSERT INTO sensordata (sensorid, cropcycleid, " +
            "moisture, pressure, temperature, humidity, ph, " +
            "timestamp, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?;";

        let values = [
            1,
            1,
            moisture,
            pressure,
            temperature,
            humidity,
            ph,
            timestamp
        ];

        connection.query(query, values, function (error, results) {
            if (error)
                return reject(error);

            return resolve(results[0].insertId);
        });

    });

}

exports.handler = function (event, context) {

    const deviceId = 25324;

    getLastRetrieved().then((data) => {

        console.log(data);

        getData(new Date(data), new Date(), deviceId).then((data) => {

            storeNew(data).then(() => {
                console.log("Success");

                // context.succeed("Success");

            }).catch((e) => {
                console.error("Error", e);
                // context.succeed("Error" + e.toString);
            })

        }).catch((e) => {
            console.log(e);
            // context.succeed("Error" + e.toString);
        });

    }).catch((e) => {
        console.log(e);
        // context.succeed("Error" + e.toString);
    });

};

exports.handler();
