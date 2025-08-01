/*
 |--------------------------------------------------------------------------
 | Browser-sync config file
 |--------------------------------------------------------------------------
 |
 | For up-to-date information about the options:
 |   http://www.browsersync.io/docs/options/
 |
 | There are more options than you see here, these are just the ones
 | that are set internally. See the website for more info.
 |
 */
module.exports = {
    "ui": {
        "port": 3001
    },
    "files": [
        "app/**/*.php",
        "public/**/*.css",
        "public/**/*.js", 
        "public/**/*.html",
        "app/Views/**/*.php"
    ],
    "watchEvents": [
        "change",
        "add",
        "unlink",
        "addDir",
        "unlinkDir"
    ],
    "watch": true,
    "ignore": [
        "node_modules/**",
        "vendor/**",
        "*.log",
        "*.tmp",
        ".git/**"
    ],
    "single": false,
    "watchOptions": {
        "ignoreInitial": true,
        "followSymlinks": true,
        "cwd": process.cwd(),
        "disableGlobbing": false,
        "usePolling": false,
        "interval": 100,
        "binaryInterval": 300,
        "alwaysStat": false,
        "depth": 99,
        "awaitWriteFinish": {
            "stabilityThreshold": 2000,
            "pollInterval": 100
        }
    },
    "server": false,
    "proxy": "localhost/ai-agent-integration/public",
    "port": 3000,
    "middleware": false,
    "serveStatic": [],
    "ghostMode": {
        "clicks": true,
        "scroll": true,
        "location": true,
        "forms": {
            "submit": true,
            "inputs": true,
            "toggles": true
        }
    },
    "logLevel": "info",
    "logPrefix": "AI-Agent-Integration",
    "logConnections": false,
    "logFileChanges": true,
    "logSnippet": true,
    "rewriteRules": [],
    "open": "local",
    "browser": "default",
    "cors": false,
    "xip": false,
    "hostnameSuffix": false,
    "reloadOnRestart": false,
    "notify": true,
    "scrollProportionally": true,
    "scrollThrottle": 0,
    "scrollRestoreTechnique": "window.name",
    "scrollElements": [],
    "scrollElementMapping": [],
    "reloadDelay": 300,
    "reloadDebounce": 500,
    "reloadThrottle": 0,
    "plugins": [],
    "injectChanges": true,
    "startPath": null,
    "minify": true,
    "host": null,
    "localOnly": false,
    "codeSync": true,
    "timestamps": true,
    "clientEvents": [
        "scroll",
        "scroll:element",
        "input:text",
        "input:toggles",
        "form:submit",
        "form:reset",
        "click"
    ],
    "socket": {
        "socketIoOptions": {
            "log": false
        },
        "socketIoClientConfig": {
            "reconnectionAttempts": 50
        },
        "path": "/browser-sync/socket.io",
        "clientPath": "/browser-sync",
        "namespace": "/browser-sync",
        "clients": {
            "heartbeatTimeout": 5000
        }
    },
    "tagNames": {
        "less": "link",
        "scss": "link",
        "css": "link",
        "jpg": "img",
        "jpeg": "img",
        "png": "img",
        "svg": "img",
        "gif": "img",
        "js": "script"
    },
    "injectNotification": false
};
