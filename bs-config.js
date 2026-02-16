module.exports = {
    "proxy": "http://app",
    "port": 3000,
    "files": ["**/*.php", "**/*.css", "**/*.js"],
    "watchOptions": {
        "ignoreInitial": true
    },
    "open": false,
    "notify": false,
    "socket": {
        "port": 3005,
        "domain": "localhost:3005"
    }
};
