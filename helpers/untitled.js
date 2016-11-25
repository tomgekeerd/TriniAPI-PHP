'use strict'

const request = require('request');
const data = require('./data.json');

getData()

function getData() {
	request({
        url: 'https://leerlingen.trinitascollege.nl/Login?passAction=login&path=%2F',
        method: 'POST',
        json: {
            wu_loginname: "140946",
            wu_password: "emnwpxnz",
        }
    }, function(error, response, body) {
        if (error) {
            console.log('Error sending messages: ', error)
        } else if (response.body.error) {
            console.log('Error: ', response.body.error)
        } else if (!error) {
            print(response)
        }
    })
}