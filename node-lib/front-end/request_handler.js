// Core
var fs = require("fs"),
    url = require("url"),
    path = require("path"),
    crypto = require("crypto"),
    mime = require("mime")
    mkdirp = require("mkdirp")
    md5_file = require("md5-file"),
    moment = require("moment");

// Connect
var st = require("st"),
    ex_static = require("express-static"),
    blinker = require("express-blinker"),
    php = require("./php_handler.js"),
    bodyParser = require('body-parser'),
    cookies = require("cookie-parser"),
    session = require("express-session"),
    RedisStore = require('connect-redis')(session),
    multiparty = require("connect-multiparty"),
    responseTime = require("response-time"),
    compression = require("compression"),
    favicon = require("serve-favicon");

var debug = require("debug")("bird3:http");

// Options
var stOpts = {
    index: false,
    dot: false,
    gzip: false,
    passthrough: true,
    cache: { // specify cache:false to turn off caching entirely
        fd: {
            max: 1000, // number of fd's to hang on to
            maxAge: 1000*60*60, // amount of ms before fd's expire
        },
        stat: {
          max: 5000, // number of stat objects to hang on to
          maxAge: 1000*60, // number of ms that stats are good for
        },
        content: {
          max: 1024*1024*64, // how much memory to use on caching contents
          maxAge: 1000*60*60, // how long to cache contents for
                              // if `false` does not set cache control headers
          cacheControl: 'public, max-age=1200' // to set an explicit cache-control
                                              // header value
        }
    }
};

module.exports = function(app) {
    // We need to incororate with NGINX or any other webserver here.
    debug("BIRD3 WebService: Starting...");

    // Some tiny middlewares...
    app.use(compression({
        level: 9,
        memLevel: 9
    }));
    app.use(responseTime());

    // favicon
    app.use(favicon(config.base+"/cdn/images/favicons/favicon.ico"));

    // Inject API and CloudFlare
    require("./cloudflare_worker.js")(app);
    require("./api_handler.js")(app);

    // CDN must not return caching when not needed.
    app.use(config.CDN.baseUrl, function(req, res, next){
        if("nocache" in req.query) {
            return ex_static(config.base+"/cdn")(req, res, next);
        } else return next();
    });
    var cdn_st = stOpts;
    cdn_st.path = config.base+"/cdn";
    //app.use(config.CDN.baseUrl, st(cdn_st));
    app.use(config.CDN.baseUrl, blinker(config.base+"/cdn", [
        {
            test: /\.(jp?g|png|tiff|ttf|eot|woff?|svg|js|css)$/,
            etag: false,
            lastModified: false,
            cacheControl: true,
            cacheKeyword: "public",
            expires: true,
            age: moment.duration(1, "day").asSeconds()
        },{
            test: /.+/,
            etag: false,
            lastModified: false,
            cacheControl: false,
            expires: false,
            age: 0
        }
    ]));

    // Configuring the BIRD Main server.
    app.use("/", bodyParser.urlencoded({
      extended: true
    }));
    app.use("/", multiparty(config.version));
    app.use("/", cookies());
    /*app.use("/", session({
        store: new RedisStore({
            prefix: "BIRD3.Session.",
            db: 0
        }),
        name: 'PHPSESSID',
        secret: config.version,
        resave: false,
        saveUninitialized: true
    }));*/

    // Set up the PHP stuff
    //app.use("/", php({}));
    var $php = php();
    require("./php_processor")($php);
    app.use("/", $php.middleware);

    debug("BIRD3 WebService: Running.");
}
