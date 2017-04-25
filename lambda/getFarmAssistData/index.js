const https = require("https");
const mysql = require("mysql");


const connection = mysql.createConnection({
    host: "iot.csosz45qwa0w.ap-southeast-2.rds.amazonaws.com",
    user: "root",
    password: "testavocado",
    database: "analytics"
});

function getRandomInt(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min)) + min;
}

function getData(fromDate, toDate, deviceId) {

    toDate.setTime(toDate.getTime() + (24 * 60 * 60 * 1000))
    fromDate.setTime(fromDate.getTime() - (10 * 60 * 60 * 1000));

    const url = "/measurement/measurements/series?source=" +
        deviceId + "&dateFrom=" + fromDate.toISOString() +
        "&dateTo=" + toDate.toISOString();

    console.log(url)

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

        let dataString = "";

        const req = https.request(options, (res) => {

            res.on("data", (d) => {
                dataString += d.toString();
            });

            res.on("end", () => {


                let recentData = JSON.parse(dataString);
                let dataList = [];

                let orderOfValues = [];

                for (let i = 0; i < recentData.series.length; i++) {
                    orderOfValues.push(recentData.series[i].type);
                }

                if (recentData.hasOwnProperty("values")) {

                    recentData = recentData.values;
                    let newData = [];

                    for (let date in recentData) {
                        if (recentData.hasOwnProperty(date)) {
                            console.log(recentData[date]);
                            if (recentData[date].length == 4) {

                                if (recentData[date][0]) {
                                    newData[0] = recentData[date][0].max
                                }

                                if (recentData[date][1]) {
                                    newData[1] = recentData[date][1].max
                                }

                                if (recentData[date][2]) {
                                    newData[2] = recentData[date][2].max
                                }

                                if (recentData[date][3]) {
                                    newData[3] = recentData[date][3].max
                                }


                            }

                            if ((newData[0] != undefined) &&
                                (newData[1] != undefined) &&
                                (newData[2] != undefined) &&
                                (newData[3] != undefined)
                            ) {

                                let data = {};
                                data.timestamp = date;
                                data.ph = getRandomInt(5, 7);

                                // Go over the orderOfValues
                                for (let i = 0; i < orderOfValues.length;  i++) {

                                    switch (orderOfValues[i]) {

                                        case "Temperature":
                                            data.temperature = newData[i];
                                            break;

                                        case "Pressure":
                                            data.pressure = newData[i];
                                            break;

                                        case "Humidity":
                                            data.humidity = newData[i];
                                            break;

                                        case "Moisture":
                                            data.moisture = newData[i];
                                            break;

                                    }

                                }

                                dataList.push(data);
                                newData = [];
                            }

                        }
                    }

                }

                if (dataList.length > 0) {
                    return resolve(dataList);
                } else {
                    return reject("No Values");
                }
            })

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

function storeNew(data) {


    return new Promise((resolve, reject) => {

        const query = "INSERT INTO sensordata (sensorid, cropcycleid, " +
            "moisture, pressure, temperature, humidity, ph, " +
            "timestamp, message) VALUES ?;";

        let values = [];

        for (let i = 0; i < data.length; i++) {

            values.push([
                1,
                1,
                data[i].moisture,
                data[i].pressure,
                data[i].temperature,
                data[i].humidity,
                data[i].ph,
                data[i].timestamp,
                ""
            ])

        }

        connection.query(query, [values], function (error, results) {
            if (error)
                return reject(error);

            return resolve(true);
        });

    });

}

exports.handler = function (event, context) {

    const deviceId = 25454;

    getLastRetrieved().then((date) => {

        let fromDate = new Date(date);

        getData(fromDate, new Date(), deviceId).then((data) => {

            storeNew(data).then(() => {
                console.log("Success");

                context.succeed("Success: " + JSON.stringify(data));

            }).catch((e) => {
                console.error("Error", e);
                context.succeed("Error: " + JSON.stringify(e));
            })

        }).catch((e) => {
            console.log(e);
            context.succeed("Error: " + JSON.stringify(e));
        });

    }).catch((e) => {
        console.log(e);
        context.succeed("Error: " + JSON.stringify(e));
    });

};

exports.handler();
