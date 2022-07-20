var formintorjs;(function () { if (!formintorjs || !formintorjs.requirejs) {
if (!formintorjs) { formintorjs = {}; } else { require = formintorjs; }
/** vim: et:ts=4:sw=4:sts=4
 * @license RequireJS 2.3.3 Copyright jQuery Foundation and other contributors.
 * Released under MIT license, https://github.com/requirejs/requirejs/blob/master/LICENSE
 */

// UPFRONT DEV NOTICE: do not replace with minified version, this needs to be full version for proper build!!!

//Not using strict: uneven strict support in browsers, #392, and causes
//problems with requirejs.exec()/transpiler plugins that may not be strict.
/*jslint regexp: true, nomen: true, sloppy: true */
/*global window, navigator, document, importScripts, setTimeout, opera */

var requirejs, require, define;
(function (global, setTimeout) {
    var req, s, head, baseElement, dataMain, src,
        interactiveScript, currentlyAddingScript, mainScript, subPath,
        version = '2.3.3',
        commentRegExp = /\/\*[\s\S]*?\*\/|([^:"'=]|^)\/\/.*$/mg,
        cjsRequireRegExp = /[^.]\s*require\s*\(\s*["']([^'"\s]+)["']\s*\)/g,
        jsSuffixRegExp = /\.js$/,
        currDirRegExp = /^\.\//,
        op = Object.prototype,
        ostring = op.toString,
        hasOwn = op.hasOwnProperty,
        isBrowser = !!(typeof window !== 'undefined' && typeof navigator !== 'undefined' && window.document),
        isWebWorker = !isBrowser && typeof importScripts !== 'undefined',
        //PS3 indicates loaded and complete, but need to wait for complete
        //specifically. Sequence is 'loading', 'loaded', execution,
        // then 'complete'. The UA check is unfortunate, but not sure how
        //to feature test w/o causing perf issues.
        readyRegExp = isBrowser && navigator.platform === 'PLAYSTATION 3' ?
                      /^complete$/ : /^(complete|loaded)$/,
        defContextName = '_',
        //Oh the tragedy, detecting opera. See the usage of isOpera for reason.
        isOpera = typeof opera !== 'undefined' && opera.toString() === '[object Opera]',
        contexts = {},
        cfg = {},
        globalDefQueue = [],
        useInteractive = false;

    //Could match something like ')//comment', do not lose the prefix to comment.
    function commentReplace(match, singlePrefix) {
        return singlePrefix || '';
    }

    function isFunction(it) {
        return ostring.call(it) === '[object Function]';
    }

    function isArray(it) {
        return ostring.call(it) === '[object Array]';
    }

    /**
     * Helper function for iterating over an array. If the func returns
     * a true value, it will break out of the loop.
     */
    function each(ary, func) {
        if (ary) {
            var i;
            for (i = 0; i < ary.length; i += 1) {
                if (ary[i] && func(ary[i], i, ary)) {
                    break;
                }
            }
        }
    }

    /**
     * Helper function for iterating over an array backwards. If the func
     * returns a true value, it will break out of the loop.
     */
    function eachReverse(ary, func) {
        if (ary) {
            var i;
            for (i = ary.length - 1; i > -1; i -= 1) {
                if (ary[i] && func(ary[i], i, ary)) {
                    break;
                }
            }
        }
    }

    function hasProp(obj, prop) {
        return hasOwn.call(obj, prop);
    }

    function getOwn(obj, prop) {
        return hasProp(obj, prop) && obj[prop];
    }

    /**
     * Cycles over properties in an object and calls a function for each
     * property value. If the function returns a truthy value, then the
     * iteration is stopped.
     */
    function eachProp(obj, func) {
        var prop;
        for (prop in obj) {
            if (hasProp(obj, prop)) {
                if (func(obj[prop], prop)) {
                    break;
                }
            }
        }
    }

    /**
     * Simple function to mix in properties from source into target,
     * but only if target does not already have a property of the same name.
     */
    function mixin(target, source, force, deepStringMixin) {
        if (source) {
            eachProp(source, function (value, prop) {
                if (force || !hasProp(target, prop)) {
                    if (deepStringMixin && typeof value === 'object' && value &&
                        !isArray(value) && !isFunction(value) &&
                        !(value instanceof RegExp)) {

                        if (!target[prop]) {
                            target[prop] = {};
                        }
                        mixin(target[prop], value, force, deepStringMixin);
                    } else {
                        target[prop] = value;
                    }
                }
            });
        }
        return target;
    }

    //Similar to Function.prototype.bind, but the 'this' object is specified
    //first, since it is easier to read/figure out what 'this' will be.
    function bind(obj, fn) {
        return function () {
            return fn.apply(obj, arguments);
        };
    }

    function scripts() {
        return document.getElementsByTagName('script');
    }

    function defaultOnError(err) {
        throw err;
    }

    //Allow getting a global that is expressed in
    //dot notation, like 'a.b.c'.
    function getGlobal(value) {
        if (!value) {
            return value;
        }
        var g = global;
        each(value.split('.'), function (part) {
            g = g[part];
        });
        return g;
    }

    /**
     * Constructs an error with a pointer to an URL with more information.
     * @param {String} id the error ID that maps to an ID on a web page.
     * @param {String} message human readable error.
     * @param {Error} [err] the original error, if there is one.
     *
     * @returns {Error}
     */
    function makeError(id, msg, err, requireModules) {
        var e = new Error(msg + '\nhttp://requirejs.org/docs/errors.html#' + id);
        e.requireType = id;
        e.requireModules = requireModules;
        if (err) {
            e.originalError = err;
        }
        return e;
    }

    if (typeof define !== 'undefined') {
        //If a define is already in play via another AMD loader,
        //do not overwrite.
        return;
    }

    if (typeof requirejs !== 'undefined') {
        if (isFunction(requirejs)) {
            //Do not overwrite an existing requirejs instance.
            return;
        }
        cfg = requirejs;
        requirejs = undefined;
    }

    //Allow for a require config object
    if (typeof require !== 'undefined' && !isFunction(require)) {
        //assume it is a config object.
        cfg = require;
        require = undefined;
    }

    function newContext(contextName) {
        var inCheckLoaded, Module, context, handlers,
            checkLoadedTimeoutId,
            config = {
                //Defaults. Do not set a default for map
                //config to speed up normalize(), which
                //will run faster if there is no default.
                waitSeconds: 7,
                baseUrl: './',
                paths: {},
                bundles: {},
                pkgs: {},
                shim: {},
                config: {}
            },
            registry = {},
            //registry of just enabled modules, to speed
            //cycle breaking code when lots of modules
            //are registered, but not activated.
            enabledRegistry = {},
            undefEvents = {},
            defQueue = [],
            defined = {},
            urlFetched = {},
            bundlesMap = {},
            requireCounter = 1,
            unnormalizedCounter = 1;

        /**
         * Trims the . and .. from an array of path segments.
         * It will keep a leading path segment if a .. will become
         * the first path segment, to help with module name lookups,
         * which act like paths, but can be remapped. But the end result,
         * all paths that use this function should look normalized.
         * NOTE: this method MODIFIES the input array.
         * @param {Array} ary the array of path segments.
         */
        function trimDots(ary) {
            var i, part;
            for (i = 0; i < ary.length; i++) {
                part = ary[i];
                if (part === '.') {
                    ary.splice(i, 1);
                    i -= 1;
                } else if (part === '..') {
                    // If at the start, or previous value is still ..,
                    // keep them so that when converted to a path it may
                    // still work when converted to a path, even though
                    // as an ID it is less than ideal. In larger point
                    // releases, may be better to just kick out an error.
                    if (i === 0 || (i === 1 && ary[2] === '..') || ary[i - 1] === '..') {
                        continue;
                    } else if (i > 0) {
                        ary.splice(i - 1, 2);
                        i -= 2;
                    }
                }
            }
        }

        /**
         * Given a relative module name, like ./something, normalize it to
         * a real name that can be mapped to a path.
         * @param {String} name the relative name
         * @param {String} baseName a real name that the name arg is relative
         * to.
         * @param {Boolean} applyMap apply the map config to the value. Should
         * only be done if this normalization is for a dependency ID.
         * @returns {String} normalized name
         */
        function normalize(name, baseName, applyMap) {
            var pkgMain, mapValue, nameParts, i, j, nameSegment, lastIndex,
                foundMap, foundI, foundStarMap, starI, normalizedBaseParts,
                baseParts = (baseName && baseName.split('/')),
                map = config.map,
                starMap = map && map['*'];

            //Adjust any relative paths.
            if (name) {
                name = name.split('/');
                lastIndex = name.length - 1;

                // If wanting node ID compatibility, strip .js from end
                // of IDs. Have to do this here, and not in nameToUrl
                // because node allows either .js or non .js to map
                // to same file.
                if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
                    name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
                }

                // Starts with a '.' so need the baseName
                if (name[0].charAt(0) === '.' && baseParts) {
                    //Convert baseName to array, and lop off the last part,
                    //so that . matches that 'directory' and not name of the baseName's
                    //module. For instance, baseName of 'one/two/three', maps to
                    //'one/two/three.js', but we want the directory, 'one/two' for
                    //this normalization.
                    normalizedBaseParts = baseParts.slice(0, baseParts.length - 1);
                    name = normalizedBaseParts.concat(name);
                }

                trimDots(name);
                name = name.join('/');
            }

            //Apply map config if available.
            if (applyMap && map && (baseParts || starMap)) {
                nameParts = name.split('/');

                outerLoop: for (i = nameParts.length; i > 0; i -= 1) {
                    nameSegment = nameParts.slice(0, i).join('/');

                    if (baseParts) {
                        //Find the longest baseName segment match in the config.
                        //So, do joins on the biggest to smallest lengths of baseParts.
                        for (j = baseParts.length; j > 0; j -= 1) {
                            mapValue = getOwn(map, baseParts.slice(0, j).join('/'));

                            //baseName segment has config, find if it has one for
                            //this name.
                            if (mapValue) {
                                mapValue = getOwn(mapValue, nameSegment);
                                if (mapValue) {
                                    //Match, update name to the new value.
                                    foundMap = mapValue;
                                    foundI = i;
                                    break outerLoop;
                                }
                            }
                        }
                    }

                    //Check for a star map match, but just hold on to it,
                    //if there is a shorter segment match later in a matching
                    //config, then favor over this star map.
                    if (!foundStarMap && starMap && getOwn(starMap, nameSegment)) {
                        foundStarMap = getOwn(starMap, nameSegment);
                        starI = i;
                    }
                }

                if (!foundMap && foundStarMap) {
                    foundMap = foundStarMap;
                    foundI = starI;
                }

                if (foundMap) {
                    nameParts.splice(0, foundI, foundMap);
                    name = nameParts.join('/');
                }
            }

            // If the name points to a package's name, use
            // the package main instead.
            pkgMain = getOwn(config.pkgs, name);

            return pkgMain ? pkgMain : name;
        }

        function removeScript(name) {
            if (isBrowser) {
                each(scripts(), function (scriptNode) {
                    if (scriptNode.getAttribute('data-requiremodule') === name &&
                            scriptNode.getAttribute('data-requirecontext') === context.contextName) {
                        scriptNode.parentNode.removeChild(scriptNode);
                        return true;
                    }
                });
            }
        }

        function hasPathFallback(id) {
            var pathConfig = getOwn(config.paths, id);
            if (pathConfig && isArray(pathConfig) && pathConfig.length > 1) {
                //Pop off the first array value, since it failed, and
                //retry
                pathConfig.shift();
                context.require.undef(id);

                //Custom require that does not do map translation, since
                //ID is "absolute", already mapped/resolved.
                context.makeRequire(null, {
                    skipMap: true
                })([id]);

                return true;
            }
        }

        //Turns a plugin!resource to [plugin, resource]
        //with the plugin being undefined if the name
        //did not have a plugin prefix.
        function splitPrefix(name) {
            var prefix,
                index = name ? name.indexOf('!') : -1;
            if (index > -1) {
                prefix = name.substring(0, index);
                name = name.substring(index + 1, name.length);
            }
            return [prefix, name];
        }

        /**
         * Creates a module mapping that includes plugin prefix, module
         * name, and path. If parentModuleMap is provided it will
         * also normalize the name via require.normalize()
         *
         * @param {String} name the module name
         * @param {String} [parentModuleMap] parent module map
         * for the module name, used to resolve relative names.
         * @param {Boolean} isNormalized: is the ID already normalized.
         * This is true if this call is done for a define() module ID.
         * @param {Boolean} applyMap: apply the map config to the ID.
         * Should only be true if this map is for a dependency.
         *
         * @returns {Object}
         */
        function makeModuleMap(name, parentModuleMap, isNormalized, applyMap) {
            var url, pluginModule, suffix, nameParts,
                prefix = null,
                parentName = parentModuleMap ? parentModuleMap.name : null,
                originalName = name,
                isDefine = true,
                normalizedName = '';

            //If no name, then it means it is a require call, generate an
            //internal name.
            if (!name) {
                isDefine = false;
                name = '_@r' + (requireCounter += 1);
            }

            nameParts = splitPrefix(name);
            prefix = nameParts[0];
            name = nameParts[1];

            if (prefix) {
                prefix = normalize(prefix, parentName, applyMap);
                pluginModule = getOwn(defined, prefix);
            }

            //Account for relative paths if there is a base name.
            if (name) {
                if (prefix) {
                    if (isNormalized) {
                        normalizedName = name;
                    } else if (pluginModule && pluginModule.normalize) {
                        //Plugin is loaded, use its normalize method.
                        normalizedName = pluginModule.normalize(name, function (name) {
                            return normalize(name, parentName, applyMap);
                        });
                    } else {
                        // If nested plugin references, then do not try to
                        // normalize, as it will not normalize correctly. This
                        // places a restriction on resourceIds, and the longer
                        // term solution is not to normalize until plugins are
                        // loaded and all normalizations to allow for async
                        // loading of a loader plugin. But for now, fixes the
                        // common uses. Details in #1131
                        normalizedName = name.indexOf('!') === -1 ?
                                         normalize(name, parentName, applyMap) :
                                         name;
                    }
                } else {
                    //A regular module.
                    normalizedName = normalize(name, parentName, applyMap);

                    //Normalized name may be a plugin ID due to map config
                    //application in normalize. The map config values must
                    //already be normalized, so do not need to redo that part.
                    nameParts = splitPrefix(normalizedName);
                    prefix = nameParts[0];
                    normalizedName = nameParts[1];
                    isNormalized = true;

                    url = context.nameToUrl(normalizedName);
                }
            }

            //If the id is a plugin id that cannot be determined if it needs
            //normalization, stamp it with a unique ID so two matching relative
            //ids that may conflict can be separate.
            suffix = prefix && !pluginModule && !isNormalized ?
                     '_unnormalized' + (unnormalizedCounter += 1) :
                     '';

            return {
                prefix: prefix,
                name: normalizedName,
                parentMap: parentModuleMap,
                unnormalized: !!suffix,
                url: url,
                originalName: originalName,
                isDefine: isDefine,
                id: (prefix ?
                        prefix + '!' + normalizedName :
                        normalizedName) + suffix
            };
        }

        function getModule(depMap) {
            var id = depMap.id,
                mod = getOwn(registry, id);

            if (!mod) {
                mod = registry[id] = new context.Module(depMap);
            }

            return mod;
        }

        function on(depMap, name, fn) {
            var id = depMap.id,
                mod = getOwn(registry, id);

            if (hasProp(defined, id) &&
                    (!mod || mod.defineEmitComplete)) {
                if (name === 'defined') {
                    fn(defined[id]);
                }
            } else {
                mod = getModule(depMap);
                if (mod.error && name === 'error') {
                    fn(mod.error);
                } else {
                    mod.on(name, fn);
                }
            }
        }

        function onError(err, errback) {
            var ids = err.requireModules,
                notified = false;

            if (errback) {
                errback(err);
            } else {
                each(ids, function (id) {
                    var mod = getOwn(registry, id);
                    if (mod) {
                        //Set error on module, so it skips timeout checks.
                        mod.error = err;
                        if (mod.events.error) {
                            notified = true;
                            mod.emit('error', err);
                        }
                    }
                });

                if (!notified) {
                    req.onError(err);
                }
            }
        }

        /**
         * Internal method to transfer globalQueue items to this context's
         * defQueue.
         */
        function takeGlobalQueue() {
            //Push all the globalDefQueue items into the context's defQueue
            if (globalDefQueue.length) {
                each(globalDefQueue, function(queueItem) {
                    var id = queueItem[0];
                    if (typeof id === 'string') {
                        context.defQueueMap[id] = true;
                    }
                    defQueue.push(queueItem);
                });
                globalDefQueue = [];
            }
        }

        handlers = {
            'require': function (mod) {
                if (mod.require) {
                    return mod.require;
                } else {
                    return (mod.require = context.makeRequire(mod.map));
                }
            },
            'exports': function (mod) {
                mod.usingExports = true;
                if (mod.map.isDefine) {
                    if (mod.exports) {
                        return (defined[mod.map.id] = mod.exports);
                    } else {
                        return (mod.exports = defined[mod.map.id] = {});
                    }
                }
            },
            'module': function (mod) {
                if (mod.module) {
                    return mod.module;
                } else {
                    return (mod.module = {
                        id: mod.map.id,
                        uri: mod.map.url,
                        config: function () {
                            return getOwn(config.config, mod.map.id) || {};
                        },
                        exports: mod.exports || (mod.exports = {})
                    });
                }
            }
        };

        function cleanRegistry(id) {
            //Clean up machinery used for waiting modules.
            delete registry[id];
            delete enabledRegistry[id];
        }

        function breakCycle(mod, traced, processed) {
            var id = mod.map.id;

            if (mod.error) {
                mod.emit('error', mod.error);
            } else {
                traced[id] = true;
                each(mod.depMaps, function (depMap, i) {
                    var depId = depMap.id,
                        dep = getOwn(registry, depId);

                    //Only force things that have not completed
                    //being defined, so still in the registry,
                    //and only if it has not been matched up
                    //in the module already.
                    if (dep && !mod.depMatched[i] && !processed[depId]) {
                        if (getOwn(traced, depId)) {
                            mod.defineDep(i, defined[depId]);
                            mod.check(); //pass false?
                        } else {
                            breakCycle(dep, traced, processed);
                        }
                    }
                });
                processed[id] = true;
            }
        }

        function checkLoaded() {
            var err, usingPathFallback,
                waitInterval = config.waitSeconds * 1000,
                //It is possible to disable the wait interval by using waitSeconds of 0.
                expired = waitInterval && (context.startTime + waitInterval) < new Date().getTime(),
                noLoads = [],
                reqCalls = [],
                stillLoading = false,
                needCycleCheck = true;

            //Do not bother if this call was a result of a cycle break.
            if (inCheckLoaded) {
                return;
            }

            inCheckLoaded = true;

            //Figure out the state of all the modules.
            eachProp(enabledRegistry, function (mod) {
                var map = mod.map,
                    modId = map.id;

                //Skip things that are not enabled or in error state.
                if (!mod.enabled) {
                    return;
                }

                if (!map.isDefine) {
                    reqCalls.push(mod);
                }

                if (!mod.error) {
                    //If the module should be executed, and it has not
                    //been inited and time is up, remember it.
                    if (!mod.inited && expired) {
                        if (hasPathFallback(modId)) {
                            usingPathFallback = true;
                            stillLoading = true;
                        } else {
                            noLoads.push(modId);
                            removeScript(modId);
                        }
                    } else if (!mod.inited && mod.fetched && map.isDefine) {
                        stillLoading = true;
                        if (!map.prefix) {
                            //No reason to keep looking for unfinished
                            //loading. If the only stillLoading is a
                            //plugin resource though, keep going,
                            //because it may be that a plugin resource
                            //is waiting on a non-plugin cycle.
                            return (needCycleCheck = false);
                        }
                    }
                }
            });

            if (expired && noLoads.length) {
                //If wait time expired, throw error of unloaded modules.
                err = makeError('timeout', 'Load timeout for modules: ' + noLoads, null, noLoads);
                err.contextName = context.contextName;
                return onError(err);
            }

            //Not expired, check for a cycle.
            if (needCycleCheck) {
                each(reqCalls, function (mod) {
                    breakCycle(mod, {}, {});
                });
            }

            //If still waiting on loads, and the waiting load is something
            //other than a plugin resource, or there are still outstanding
            //scripts, then just try back later.
            if ((!expired || usingPathFallback) && stillLoading) {
                //Something is still waiting to load. Wait for it, but only
                //if a timeout is not already in effect.
                if ((isBrowser || isWebWorker) && !checkLoadedTimeoutId) {
                    checkLoadedTimeoutId = setTimeout(function () {
                        checkLoadedTimeoutId = 0;
                        checkLoaded();
                    }, 50);
                }
            }

            inCheckLoaded = false;
        }

        Module = function (map) {
            this.events = getOwn(undefEvents, map.id) || {};
            this.map = map;
            this.shim = getOwn(config.shim, map.id);
            this.depExports = [];
            this.depMaps = [];
            this.depMatched = [];
            this.pluginMaps = {};
            this.depCount = 0;

            /* this.exports this.factory
               this.depMaps = [],
               this.enabled, this.fetched
            */
        };

        Module.prototype = {
            init: function (depMaps, factory, errback, options) {
                options = options || {};

                //Do not do more inits if already done. Can happen if there
                //are multiple define calls for the same module. That is not
                //a normal, common case, but it is also not unexpected.
                if (this.inited) {
                    return;
                }

                this.factory = factory;

                if (errback) {
                    //Register for errors on this module.
                    this.on('error', errback);
                } else if (this.events.error) {
                    //If no errback already, but there are error listeners
                    //on this module, set up an errback to pass to the deps.
                    errback = bind(this, function (err) {
                        this.emit('error', err);
                    });
                }

                //Do a copy of the dependency array, so that
                //source inputs are not modified. For example
                //"shim" deps are passed in here directly, and
                //doing a direct modification of the depMaps array
                //would affect that config.
                this.depMaps = depMaps && depMaps.slice(0);

                this.errback = errback;

                //Indicate this module has be initialized
                this.inited = true;

                this.ignore = options.ignore;

                //Could have option to init this module in enabled mode,
                //or could have been previously marked as enabled. However,
                //the dependencies are not known until init is called. So
                //if enabled previously, now trigger dependencies as enabled.
                if (options.enabled || this.enabled) {
                    //Enable this module and dependencies.
                    //Will call this.check()
                    this.enable();
                } else {
                    this.check();
                }
            },

            defineDep: function (i, depExports) {
                //Because of cycles, defined callback for a given
                //export can be called more than once.
                if (!this.depMatched[i]) {
                    this.depMatched[i] = true;
                    this.depCount -= 1;
                    this.depExports[i] = depExports;
                }
            },

            fetch: function () {
                if (this.fetched) {
                    return;
                }
                this.fetched = true;

                context.startTime = (new Date()).getTime();

                var map = this.map;

                //If the manager is for a plugin managed resource,
                //ask the plugin to load it now.
                if (this.shim) {
                    context.makeRequire(this.map, {
                        enableBuildCallback: true
                    })(this.shim.deps || [], bind(this, function () {
                        return map.prefix ? this.callPlugin() : this.load();
                    }));
                } else {
                    //Regular dependency.
                    return map.prefix ? this.callPlugin() : this.load();
                }
            },

            load: function () {
                var url = this.map.url;

                //Regular dependency.
                if (!urlFetched[url]) {
                    urlFetched[url] = true;
                    context.load(this.map.id, url);
                }
            },

            /**
             * Checks if the module is ready to define itself, and if so,
             * define it.
             */
            check: function () {
                if (!this.enabled || this.enabling) {
                    return;
                }

                var err, cjsModule,
                    id = this.map.id,
                    depExports = this.depExports,
                    exports = this.exports,
                    factory = this.factory;

                if (!this.inited) {
                    // Only fetch if not already in the defQueue.
                    if (!hasProp(context.defQueueMap, id)) {
                        this.fetch();
                    }
                } else if (this.error) {
                    this.emit('error', this.error);
                } else if (!this.defining) {
                    //The factory could trigger another require call
                    //that would result in checking this module to
                    //define itself again. If already in the process
                    //of doing that, skip this work.
                    this.defining = true;

                    if (this.depCount < 1 && !this.defined) {
                        if (isFunction(factory)) {
                            //If there is an error listener, favor passing
                            //to that instead of throwing an error. However,
                            //only do it for define()'d  modules. require
                            //errbacks should not be called for failures in
                            //their callbacks (#699). However if a global
                            //onError is set, use that.
                            if ((this.events.error && this.map.isDefine) ||
                                req.onError !== defaultOnError) {
                                try {
                                    exports = context.execCb(id, factory, depExports, exports);
                                } catch (e) {
                                    err = e;
                                }
                            } else {
                                exports = context.execCb(id, factory, depExports, exports);
                            }

                            // Favor return value over exports. If node/cjs in play,
                            // then will not have a return value anyway. Favor
                            // module.exports assignment over exports object.
                            if (this.map.isDefine && exports === undefined) {
                                cjsModule = this.module;
                                if (cjsModule) {
                                    exports = cjsModule.exports;
                                } else if (this.usingExports) {
                                    //exports already set the defined value.
                                    exports = this.exports;
                                }
                            }

                            if (err) {
                                err.requireMap = this.map;
                                err.requireModules = this.map.isDefine ? [this.map.id] : null;
                                err.requireType = this.map.isDefine ? 'define' : 'require';
                                return onError((this.error = err));
                            }

                        } else {
                            //Just a literal value
                            exports = factory;
                        }

                        this.exports = exports;

                        if (this.map.isDefine && !this.ignore) {
                            defined[id] = exports;

                            if (req.onResourceLoad) {
                                var resLoadMaps = [];
                                each(this.depMaps, function (depMap) {
                                    resLoadMaps.push(depMap.normalizedMap || depMap);
                                });
                                req.onResourceLoad(context, this.map, resLoadMaps);
                            }
                        }

                        //Clean up
                        cleanRegistry(id);

                        this.defined = true;
                    }

                    //Finished the define stage. Allow calling check again
                    //to allow define notifications below in the case of a
                    //cycle.
                    this.defining = false;

                    if (this.defined && !this.defineEmitted) {
                        this.defineEmitted = true;
                        this.emit('defined', this.exports);
                        this.defineEmitComplete = true;
                    }

                }
            },

            callPlugin: function () {
                var map = this.map,
                    id = map.id,
                    //Map already normalized the prefix.
                    pluginMap = makeModuleMap(map.prefix);

                //Mark this as a dependency for this plugin, so it
                //can be traced for cycles.
                this.depMaps.push(pluginMap);

                on(pluginMap, 'defined', bind(this, function (plugin) {
                    var load, normalizedMap, normalizedMod,
                        bundleId = getOwn(bundlesMap, this.map.id),
                        name = this.map.name,
                        parentName = this.map.parentMap ? this.map.parentMap.name : null,
                        localRequire = context.makeRequire(map.parentMap, {
                            enableBuildCallback: true
                        });

                    //If current map is not normalized, wait for that
                    //normalized name to load instead of continuing.
                    if (this.map.unnormalized) {
                        //Normalize the ID if the plugin allows it.
                        if (plugin.normalize) {
                            name = plugin.normalize(name, function (name) {
                                return normalize(name, parentName, true);
                            }) || '';
                        }

                        //prefix and name should already be normalized, no need
                        //for applying map config again either.
                        normalizedMap = makeModuleMap(map.prefix + '!' + name,
                                                      this.map.parentMap,
                                                      true);
                        on(normalizedMap,
                            'defined', bind(this, function (value) {
                                this.map.normalizedMap = normalizedMap;
                                this.init([], function () { return value; }, null, {
                                    enabled: true,
                                    ignore: true
                                });
                            }));

                        normalizedMod = getOwn(registry, normalizedMap.id);
                        if (normalizedMod) {
                            //Mark this as a dependency for this plugin, so it
                            //can be traced for cycles.
                            this.depMaps.push(normalizedMap);

                            if (this.events.error) {
                                normalizedMod.on('error', bind(this, function (err) {
                                    this.emit('error', err);
                                }));
                            }
                            normalizedMod.enable();
                        }

                        return;
                    }

                    //If a paths config, then just load that file instead to
                    //resolve the plugin, as it is built into that paths layer.
                    if (bundleId) {
                        this.map.url = context.nameToUrl(bundleId);
                        this.load();
                        return;
                    }

                    load = bind(this, function (value) {
                        this.init([], function () { return value; }, null, {
                            enabled: true
                        });
                    });

                    load.error = bind(this, function (err) {
                        this.inited = true;
                        this.error = err;
                        err.requireModules = [id];

                        //Remove temp unnormalized modules for this module,
                        //since they will never be resolved otherwise now.
                        eachProp(registry, function (mod) {
                            if (mod.map.id.indexOf(id + '_unnormalized') === 0) {
                                cleanRegistry(mod.map.id);
                            }
                        });

                        onError(err);
                    });

                    //Allow plugins to load other code without having to know the
                    //context or how to 'complete' the load.
                    load.fromText = bind(this, function (text, textAlt) {
                        /*jslint evil: true */
                        var moduleName = map.name,
                            moduleMap = makeModuleMap(moduleName),
                            hasInteractive = useInteractive;

                        //As of 2.1.0, support just passing the text, to reinforce
                        //fromText only being called once per resource. Still
                        //support old style of passing moduleName but discard
                        //that moduleName in favor of the internal ref.
                        if (textAlt) {
                            text = textAlt;
                        }

                        //Turn off interactive script matching for IE for any define
                        //calls in the text, then turn it back on at the end.
                        if (hasInteractive) {
                            useInteractive = false;
                        }

                        //Prime the system by creating a module instance for
                        //it.
                        getModule(moduleMap);

                        //Transfer any config to this other module.
                        if (hasProp(config.config, id)) {
                            config.config[moduleName] = config.config[id];
                        }

                        try {
                            req.exec(text);
                        } catch (e) {
                            return onError(makeError('fromtexteval',
                                             'fromText eval for ' + id +
                                            ' failed: ' + e,
                                             e,
                                             [id]));
                        }

                        if (hasInteractive) {
                            useInteractive = true;
                        }

                        //Mark this as a dependency for the plugin
                        //resource
                        this.depMaps.push(moduleMap);

                        //Support anonymous modules.
                        context.completeLoad(moduleName);

                        //Bind the value of that module to the value for this
                        //resource ID.
                        localRequire([moduleName], load);
                    });

                    //Use parentName here since the plugin's name is not reliable,
                    //could be some weird string with no path that actually wants to
                    //reference the parentName's path.
                    plugin.load(map.name, localRequire, load, config);
                }));

                context.enable(pluginMap, this);
                this.pluginMaps[pluginMap.id] = pluginMap;
            },

            enable: function () {
                enabledRegistry[this.map.id] = this;
                this.enabled = true;

                //Set flag mentioning that the module is enabling,
                //so that immediate calls to the defined callbacks
                //for dependencies do not trigger inadvertent load
                //with the depCount still being zero.
                this.enabling = true;

                //Enable each dependency
                each(this.depMaps, bind(this, function (depMap, i) {
                    var id, mod, handler;

                    if (typeof depMap === 'string') {
                        //Dependency needs to be converted to a depMap
                        //and wired up to this module.
                        depMap = makeModuleMap(depMap,
                                               (this.map.isDefine ? this.map : this.map.parentMap),
                                               false,
                                               !this.skipMap);
                        this.depMaps[i] = depMap;

                        handler = getOwn(handlers, depMap.id);

                        if (handler) {
                            this.depExports[i] = handler(this);
                            return;
                        }

                        this.depCount += 1;

                        on(depMap, 'defined', bind(this, function (depExports) {
                            if (this.undefed) {
                                return;
                            }
                            this.defineDep(i, depExports);
                            this.check();
                        }));

                        if (this.errback) {
                            on(depMap, 'error', bind(this, this.errback));
                        } else if (this.events.error) {
                            // No direct errback on this module, but something
                            // else is listening for errors, so be sure to
                            // propagate the error correctly.
                            on(depMap, 'error', bind(this, function(err) {
                                this.emit('error', err);
                            }));
                        }
                    }

                    id = depMap.id;
                    mod = registry[id];

                    //Skip special modules like 'require', 'exports', 'module'
                    //Also, don't call enable if it is already enabled,
                    //important in circular dependency cases.
                    if (!hasProp(handlers, id) && mod && !mod.enabled) {
                        context.enable(depMap, this);
                    }
                }));

                //Enable each plugin that is used in
                //a dependency
                eachProp(this.pluginMaps, bind(this, function (pluginMap) {
                    var mod = getOwn(registry, pluginMap.id);
                    if (mod && !mod.enabled) {
                        context.enable(pluginMap, this);
                    }
                }));

                this.enabling = false;

                this.check();
            },

            on: function (name, cb) {
                var cbs = this.events[name];
                if (!cbs) {
                    cbs = this.events[name] = [];
                }
                cbs.push(cb);
            },

            emit: function (name, evt) {
                each(this.events[name], function (cb) {
                    cb(evt);
                });
                if (name === 'error') {
                    //Now that the error handler was triggered, remove
                    //the listeners, since this broken Module instance
                    //can stay around for a while in the registry.
                    delete this.events[name];
                }
            }
        };

        function callGetModule(args) {
            //Skip modules already defined.
            if (!hasProp(defined, args[0])) {
                getModule(makeModuleMap(args[0], null, true)).init(args[1], args[2]);
            }
        }

        function removeListener(node, func, name, ieName) {
            //Favor detachEvent because of IE9
            //issue, see attachEvent/addEventListener comment elsewhere
            //in this file.
            if (node.detachEvent && !isOpera) {
                //Probably IE. If not it will throw an error, which will be
                //useful to know.
                if (ieName) {
                    node.detachEvent(ieName, func);
                }
            } else {
                node.removeEventListener(name, func, false);
            }
        }

        /**
         * Given an event from a script node, get the requirejs info from it,
         * and then removes the event listeners on the node.
         * @param {Event} evt
         * @returns {Object}
         */
        function getScriptData(evt) {
            //Using currentTarget instead of target for Firefox 2.0's sake. Not
            //all old browsers will be supported, but this one was easy enough
            //to support and still makes sense.
            var node = evt.currentTarget || evt.srcElement;

            //Remove the listeners once here.
            removeListener(node, context.onScriptLoad, 'load', 'onreadystatechange');
            removeListener(node, context.onScriptError, 'error');

            return {
                node: node,
                id: node && node.getAttribute('data-requiremodule')
            };
        }

        function intakeDefines() {
            var args;

            //Any defined modules in the global queue, intake them now.
            takeGlobalQueue();

            //Make sure any remaining defQueue items get properly processed.
            while (defQueue.length) {
                args = defQueue.shift();
                if (args[0] === null) {
                    return onError(makeError('mismatch', 'Mismatched anonymous define() module: ' +
                        args[args.length - 1]));
                } else {
                    //args are id, deps, factory. Should be normalized by the
                    //define() function.
                    callGetModule(args);
                }
            }
            context.defQueueMap = {};
        }

        context = {
            config: config,
            contextName: contextName,
            registry: registry,
            defined: defined,
            urlFetched: urlFetched,
            defQueue: defQueue,
            defQueueMap: {},
            Module: Module,
            makeModuleMap: makeModuleMap,
            nextTick: req.nextTick,
            onError: onError,

            /**
             * Set a configuration for the context.
             * @param {Object} cfg config object to integrate.
             */
            configure: function (cfg) {
                //Make sure the baseUrl ends in a slash.
                if (cfg.baseUrl) {
                    if (cfg.baseUrl.charAt(cfg.baseUrl.length - 1) !== '/') {
                        cfg.baseUrl += '/';
                    }
                }

                // Convert old style urlArgs string to a function.
                if (typeof cfg.urlArgs === 'string') {
                    var urlArgs = cfg.urlArgs;
                    cfg.urlArgs = function(id, url) {
                        return (url.indexOf('?') === -1 ? '?' : '&') + urlArgs;
                    };
                }

                //Save off the paths since they require special processing,
                //they are additive.
                var shim = config.shim,
                    objs = {
                        paths: true,
                        bundles: true,
                        config: true,
                        map: true
                    };

                eachProp(cfg, function (value, prop) {
                    if (objs[prop]) {
                        if (!config[prop]) {
                            config[prop] = {};
                        }
                        mixin(config[prop], value, true, true);
                    } else {
                        config[prop] = value;
                    }
                });

                //Reverse map the bundles
                if (cfg.bundles) {
                    eachProp(cfg.bundles, function (value, prop) {
                        each(value, function (v) {
                            if (v !== prop) {
                                bundlesMap[v] = prop;
                            }
                        });
                    });
                }

                //Merge shim
                if (cfg.shim) {
                    eachProp(cfg.shim, function (value, id) {
                        //Normalize the structure
                        if (isArray(value)) {
                            value = {
                                deps: value
                            };
                        }
                        if ((value.exports || value.init) && !value.exportsFn) {
                            value.exportsFn = context.makeShimExports(value);
                        }
                        shim[id] = value;
                    });
                    config.shim = shim;
                }

                //Adjust packages if necessary.
                if (cfg.packages) {
                    each(cfg.packages, function (pkgObj) {
                        var location, name;

                        pkgObj = typeof pkgObj === 'string' ? {name: pkgObj} : pkgObj;

                        name = pkgObj.name;
                        location = pkgObj.location;
                        if (location) {
                            config.paths[name] = pkgObj.location;
                        }

                        //Save pointer to main module ID for pkg name.
                        //Remove leading dot in main, so main paths are normalized,
                        //and remove any trailing .js, since different package
                        //envs have different conventions: some use a module name,
                        //some use a file name.
                        config.pkgs[name] = pkgObj.name + '/' + (pkgObj.main || 'main')
                                     .replace(currDirRegExp, '')
                                     .replace(jsSuffixRegExp, '');
                    });
                }

                //If there are any "waiting to execute" modules in the registry,
                //update the maps for them, since their info, like URLs to load,
                //may have changed.
                eachProp(registry, function (mod, id) {
                    //If module already has init called, since it is too
                    //late to modify them, and ignore unnormalized ones
                    //since they are transient.
                    if (!mod.inited && !mod.map.unnormalized) {
                        mod.map = makeModuleMap(id, null, true);
                    }
                });

                //If a deps array or a config callback is specified, then call
                //require with those args. This is useful when require is defined as a
                //config object before require.js is loaded.
                if (cfg.deps || cfg.callback) {
                    context.require(cfg.deps || [], cfg.callback);
                }
            },

            makeShimExports: function (value) {
                function fn() {
                    var ret;
                    if (value.init) {
                        ret = value.init.apply(global, arguments);
                    }
                    return ret || (value.exports && getGlobal(value.exports));
                }
                return fn;
            },

            makeRequire: function (relMap, options) {
                options = options || {};

                function localRequire(deps, callback, errback) {
                    var id, map, requireMod;

                    if (options.enableBuildCallback && callback && isFunction(callback)) {
                        callback.__requireJsBuild = true;
                    }

                    if (typeof deps === 'string') {
                        if (isFunction(callback)) {
                            //Invalid call
                            return onError(makeError('requireargs', 'Invalid require call'), errback);
                        }

                        //If require|exports|module are requested, get the
                        //value for them from the special handlers. Caveat:
                        //this only works while module is being defined.
                        if (relMap && hasProp(handlers, deps)) {
                            return handlers[deps](registry[relMap.id]);
                        }

                        //Synchronous access to one module. If require.get is
                        //available (as in the Node adapter), prefer that.
                        if (req.get) {
                            return req.get(context, deps, relMap, localRequire);
                        }

                        //Normalize module name, if it contains . or ..
                        map = makeModuleMap(deps, relMap, false, true);
                        id = map.id;

                        if (!hasProp(defined, id)) {
                            return onError(makeError('notloaded', 'Module name "' +
                                        id +
                                        '" has not been loaded yet for context: ' +
                                        contextName +
                                        (relMap ? '' : '. Use require([])')));
                        }
                        return defined[id];
                    }

                    //Grab defines waiting in the global queue.
                    intakeDefines();

                    //Mark all the dependencies as needing to be loaded.
                    context.nextTick(function () {
                        //Some defines could have been added since the
                        //require call, collect them.
                        intakeDefines();

                        requireMod = getModule(makeModuleMap(null, relMap));

                        //Store if map config should be applied to this require
                        //call for dependencies.
                        requireMod.skipMap = options.skipMap;

                        requireMod.init(deps, callback, errback, {
                            enabled: true
                        });

                        checkLoaded();
                    });

                    return localRequire;
                }

                mixin(localRequire, {
                    isBrowser: isBrowser,

                    /**
                     * Converts a module name + .extension into an URL path.
                     * *Requires* the use of a module name. It does not support using
                     * plain URLs like nameToUrl.
                     */
                    toUrl: function (moduleNamePlusExt) {
                        var ext,
                            index = moduleNamePlusExt.lastIndexOf('.'),
                            segment = moduleNamePlusExt.split('/')[0],
                            isRelative = segment === '.' || segment === '..';

                        //Have a file extension alias, and it is not the
                        //dots from a relative path.
                        if (index !== -1 && (!isRelative || index > 1)) {
                            ext = moduleNamePlusExt.substring(index, moduleNamePlusExt.length);
                            moduleNamePlusExt = moduleNamePlusExt.substring(0, index);
                        }

                        return context.nameToUrl(normalize(moduleNamePlusExt,
                                                relMap && relMap.id, true), ext,  true);
                    },

                    defined: function (id) {
                        return hasProp(defined, makeModuleMap(id, relMap, false, true).id);
                    },

                    specified: function (id) {
                        id = makeModuleMap(id, relMap, false, true).id;
                        return hasProp(defined, id) || hasProp(registry, id);
                    }
                });

                //Only allow undef on top level require calls
                if (!relMap) {
                    localRequire.undef = function (id) {
                        //Bind any waiting define() calls to this context,
                        //fix for #408
                        takeGlobalQueue();

                        var map = makeModuleMap(id, relMap, true),
                            mod = getOwn(registry, id);

                        mod.undefed = true;
                        removeScript(id);

                        delete defined[id];
                        delete urlFetched[map.url];
                        delete undefEvents[id];

                        //Clean queued defines too. Go backwards
                        //in array so that the splices do not
                        //mess up the iteration.
                        eachReverse(defQueue, function(args, i) {
                            if (args[0] === id) {
                                defQueue.splice(i, 1);
                            }
                        });
                        delete context.defQueueMap[id];

                        if (mod) {
                            //Hold on to listeners in case the
                            //module will be attempted to be reloaded
                            //using a different config.
                            if (mod.events.defined) {
                                undefEvents[id] = mod.events;
                            }

                            cleanRegistry(id);
                        }
                    };
                }

                return localRequire;
            },

            /**
             * Called to enable a module if it is still in the registry
             * awaiting enablement. A second arg, parent, the parent module,
             * is passed in for context, when this method is overridden by
             * the optimizer. Not shown here to keep code compact.
             */
            enable: function (depMap) {
                var mod = getOwn(registry, depMap.id);
                if (mod) {
                    getModule(depMap).enable();
                }
            },

            /**
             * Internal method used by environment adapters to complete a load event.
             * A load event could be a script load or just a load pass from a synchronous
             * load call.
             * @param {String} moduleName the name of the module to potentially complete.
             */
            completeLoad: function (moduleName) {
                var found, args, mod,
                    shim = getOwn(config.shim, moduleName) || {},
                    shExports = shim.exports;

                takeGlobalQueue();

                while (defQueue.length) {
                    args = defQueue.shift();
                    if (args[0] === null) {
                        args[0] = moduleName;
                        //If already found an anonymous module and bound it
                        //to this name, then this is some other anon module
                        //waiting for its completeLoad to fire.
                        if (found) {
                            break;
                        }
                        found = true;
                    } else if (args[0] === moduleName) {
                        //Found matching define call for this script!
                        found = true;
                    }

                    callGetModule(args);
                }
                context.defQueueMap = {};

                //Do this after the cycle of callGetModule in case the result
                //of those calls/init calls changes the registry.
                mod = getOwn(registry, moduleName);

                if (!found && !hasProp(defined, moduleName) && mod && !mod.inited) {
                    if (config.enforceDefine && (!shExports || !getGlobal(shExports))) {
                        if (hasPathFallback(moduleName)) {
                            return;
                        } else {
                            return onError(makeError('nodefine',
                                             'No define call for ' + moduleName,
                                             null,
                                             [moduleName]));
                        }
                    } else {
                        //A script that does not call define(), so just simulate
                        //the call for it.
                        callGetModule([moduleName, (shim.deps || []), shim.exportsFn]);
                    }
                }

                checkLoaded();
            },

            /**
             * Converts a module name to a file path. Supports cases where
             * moduleName may actually be just an URL.
             * Note that it **does not** call normalize on the moduleName,
             * it is assumed to have already been normalized. This is an
             * internal API, not a public one. Use toUrl for the public API.
             */
            nameToUrl: function (moduleName, ext, skipExt) {
                var paths, syms, i, parentModule, url,
                    parentPath, bundleId,
                    pkgMain = getOwn(config.pkgs, moduleName);

                if (pkgMain) {
                    moduleName = pkgMain;
                }

                bundleId = getOwn(bundlesMap, moduleName);

                if (bundleId) {
                    return context.nameToUrl(bundleId, ext, skipExt);
                }

                //If a colon is in the URL, it indicates a protocol is used and it is just
                //an URL to a file, or if it starts with a slash, contains a query arg (i.e. ?)
                //or ends with .js, then assume the user meant to use an url and not a module id.
                //The slash is important for protocol-less URLs as well as full paths.
                if (req.jsExtRegExp.test(moduleName)) {
                    //Just a plain path, not module name lookup, so just return it.
                    //Add extension if it is included. This is a bit wonky, only non-.js things pass
                    //an extension, this method probably needs to be reworked.
                    url = moduleName + (ext || '');
                } else {
                    //A module that needs to be converted to a path.
                    paths = config.paths;

                    syms = moduleName.split('/');
                    //For each module name segment, see if there is a path
                    //registered for it. Start with most specific name
                    //and work up from it.
                    for (i = syms.length; i > 0; i -= 1) {
                        parentModule = syms.slice(0, i).join('/');

                        parentPath = getOwn(paths, parentModule);
                        if (parentPath) {
                            //If an array, it means there are a few choices,
                            //Choose the one that is desired
                            if (isArray(parentPath)) {
                                parentPath = parentPath[0];
                            }
                            syms.splice(0, i, parentPath);
                            break;
                        }
                    }

                    //Join the path parts together, then figure out if baseUrl is needed.
                    url = syms.join('/');
                    url += (ext || (/^data\:|^blob\:|\?/.test(url) || skipExt ? '' : '.js'));
                    url = (url.charAt(0) === '/' || url.match(/^[\w\+\.\-]+:/) ? '' : config.baseUrl) + url;
                }

                return config.urlArgs && !/^blob\:/.test(url) ?
                       url + config.urlArgs(moduleName, url) : url;
            },

            //Delegates to req.load. Broken out as a separate function to
            //allow overriding in the optimizer.
            load: function (id, url) {
                req.load(context, id, url);
            },

            /**
             * Executes a module callback function. Broken out as a separate function
             * solely to allow the build system to sequence the files in the built
             * layer in the right sequence.
             *
             * @private
             */
            execCb: function (name, callback, args, exports) {
                return callback.apply(exports, args);
            },

            /**
             * callback for script loads, used to check status of loading.
             *
             * @param {Event} evt the event from the browser for the script
             * that was loaded.
             */
            onScriptLoad: function (evt) {
                //Using currentTarget instead of target for Firefox 2.0's sake. Not
                //all old browsers will be supported, but this one was easy enough
                //to support and still makes sense.
                if (evt.type === 'load' ||
                        (readyRegExp.test((evt.currentTarget || evt.srcElement).readyState))) {
                    //Reset interactive script so a script node is not held onto for
                    //to long.
                    interactiveScript = null;

                    //Pull out the name of the module and the context.
                    var data = getScriptData(evt);
                    context.completeLoad(data.id);
                }
            },

            /**
             * Callback for script errors.
             */
            onScriptError: function (evt) {
                var data = getScriptData(evt);
                if (!hasPathFallback(data.id)) {
                    var parents = [];
                    eachProp(registry, function(value, key) {
                        if (key.indexOf('_@r') !== 0) {
                            each(value.depMaps, function(depMap) {
                                if (depMap.id === data.id) {
                                    parents.push(key);
                                    return true;
                                }
                            });
                        }
                    });
                    return onError(makeError('scripterror', 'Script error for "' + data.id +
                                             (parents.length ?
                                             '", needed by: ' + parents.join(', ') :
                                             '"'), evt, [data.id]));
                }
            }
        };

        context.require = context.makeRequire();
        return context;
    }

    /**
     * Main entry point.
     *
     * If the only argument to require is a string, then the module that
     * is represented by that string is fetched for the appropriate context.
     *
     * If the first argument is an array, then it will be treated as an array
     * of dependency string names to fetch. An optional function callback can
     * be specified to execute when all of those dependencies are available.
     *
     * Make a local req variable to help Caja compliance (it assumes things
     * on a require that are not standardized), and to give a short
     * name for minification/local scope use.
     */
    req = requirejs = function (deps, callback, errback, optional) {

        //Find the right context, use default
        var context, config,
            contextName = defContextName;

        // Determine if have config object in the call.
        if (!isArray(deps) && typeof deps !== 'string') {
            // deps is a config object
            config = deps;
            if (isArray(callback)) {
                // Adjust args if there are dependencies
                deps = callback;
                callback = errback;
                errback = optional;
            } else {
                deps = [];
            }
        }

        if (config && config.context) {
            contextName = config.context;
        }

        context = getOwn(contexts, contextName);
        if (!context) {
            context = contexts[contextName] = req.s.newContext(contextName);
        }

        if (config) {
            context.configure(config);
        }

        return context.require(deps, callback, errback);
    };

    /**
     * Support formintorjs.require.config() to make it easier to cooperate with other
     * AMD loaders on globally agreed names.
     */
    req.config = function (config) {
        return req(config);
    };

    /**
     * Execute something after the current tick
     * of the event loop. Override for other envs
     * that have a better solution than setTimeout.
     * @param  {Function} fn function to execute later.
     */
    req.nextTick = typeof setTimeout !== 'undefined' ? function (fn) {
        setTimeout(fn, 4);
    } : function (fn) { fn(); };

    /**
     * Export require as a global, but only if it does not already exist.
     */
    if (!require) {
        require = req;
    }

    req.version = version;

    //Used to filter out dependencies that are already paths.
    req.jsExtRegExp = /^\/|:|\?|\.js$/;
    req.isBrowser = isBrowser;
    s = req.s = {
        contexts: contexts,
        newContext: newContext
    };

    //Create default context.
    req({});

    //Exports some context-sensitive methods on global require.
    each([
        'toUrl',
        'undef',
        'defined',
        'specified'
    ], function (prop) {
        //Reference from contexts instead of early binding to default context,
        //so that during builds, the latest instance of the default context
        //with its config gets used.
        req[prop] = function () {
            var ctx = contexts[defContextName];
            return ctx.require[prop].apply(ctx, arguments);
        };
    });

    if (isBrowser) {
        head = s.head = document.getElementsByTagName('head')[0];
        //If BASE tag is in play, using appendChild is a problem for IE6.
        //When that browser dies, this can be removed. Details in this jQuery bug:
        //http://dev.jquery.com/ticket/2709
        baseElement = document.getElementsByTagName('base')[0];
        if (baseElement) {
            head = s.head = baseElement.parentNode;
        }
    }

    /**
     * Any errors that require explicitly generates will be passed to this
     * function. Intercept/override it if you want custom error handling.
     * @param {Error} err the error object.
     */
    req.onError = defaultOnError;

    /**
     * Creates the node for the load command. Only used in browser envs.
     */
    req.createNode = function (config, moduleName, url) {
        var node = config.xhtml ?
                document.createElementNS('http://www.w3.org/1999/xhtml', 'html:script') :
                document.createElement('script');
        node.type = config.scriptType || 'text/javascript';
        node.charset = 'utf-8';
        node.async = true;
        return node;
    };

    /**
     * Does the request to load a module for the browser case.
     * Make this a separate function to allow other environments
     * to override it.
     *
     * @param {Object} context the require context to find state.
     * @param {String} moduleName the name of the module.
     * @param {Object} url the URL to the module.
     */
    req.load = function (context, moduleName, url) {
        var config = (context && context.config) || {},
            node;
        if (isBrowser) {
            //In the browser so use a script tag
            node = req.createNode(config, moduleName, url);

            node.setAttribute('data-requirecontext', context.contextName);
            node.setAttribute('data-requiremodule', moduleName);

            //Set up load listener. Test attachEvent first because IE9 has
            //a subtle issue in its addEventListener and script onload firings
            //that do not match the behavior of all other browsers with
            //addEventListener support, which fire the onload event for a
            //script right after the script execution. See:
            //https://connect.microsoft.com/IE/feedback/details/648057/script-onload-event-is-not-fired-immediately-after-script-execution
            //UNFORTUNATELY Opera implements attachEvent but does not follow the script
            //script execution mode.
            if (node.attachEvent &&
                    //Check if node.attachEvent is artificially added by custom script or
                    //natively supported by browser
                    //read https://github.com/requirejs/requirejs/issues/187
                    //if we can NOT find [native code] then it must NOT natively supported.
                    //in IE8, node.attachEvent does not have toString()
                    //Note the test for "[native code" with no closing brace, see:
                    //https://github.com/requirejs/requirejs/issues/273
                    !(node.attachEvent.toString && node.attachEvent.toString().indexOf('[native code') < 0) &&
                    !isOpera) {
                //Probably IE. IE (at least 6-8) do not fire
                //script onload right after executing the script, so
                //we cannot tie the anonymous define call to a name.
                //However, IE reports the script as being in 'interactive'
                //readyState at the time of the define call.
                useInteractive = true;

                node.attachEvent('onreadystatechange', context.onScriptLoad);
                //It would be great to add an error handler here to catch
                //404s in IE9+. However, onreadystatechange will fire before
                //the error handler, so that does not help. If addEventListener
                //is used, then IE will fire error before load, but we cannot
                //use that pathway given the connect.microsoft.com issue
                //mentioned above about not doing the 'script execute,
                //then fire the script load event listener before execute
                //next script' that other browsers do.
                //Best hope: IE10 fixes the issues,
                //and then destroys all installs of IE 6-9.
                //node.attachEvent('onerror', context.onScriptError);
            } else {
                node.addEventListener('load', context.onScriptLoad, false);
                node.addEventListener('error', context.onScriptError, false);
            }
            node.src = url;

            //Calling onNodeCreated after all properties on the node have been
            //set, but before it is placed in the DOM.
            if (config.onNodeCreated) {
                config.onNodeCreated(node, config, moduleName, url);
            }

            //For some cache cases in IE 6-8, the script executes before the end
            //of the appendChild execution, so to tie an anonymous define
            //call to the module name (which is stored on the node), hold on
            //to a reference to this node, but clear after the DOM insertion.
            currentlyAddingScript = node;
            if (baseElement) {
                head.insertBefore(node, baseElement);
            } else {
                head.appendChild(node);
            }
            currentlyAddingScript = null;

            return node;
        } else if (isWebWorker) {
            try {
                //In a web worker, use importScripts. This is not a very
                //efficient use of importScripts, importScripts will block until
                //its script is downloaded and evaluated. However, if web workers
                //are in play, the expectation is that a build has been done so
                //that only one script needs to be loaded anyway. This may need
                //to be reevaluated if other use cases become common.

                // Post a task to the event loop to work around a bug in WebKit
                // where the worker gets garbage-collected after calling
                // importScripts(): https://webkit.org/b/153317
                setTimeout(function() {}, 0);
                importScripts(url);

                //Account for anonymous modules
                context.completeLoad(moduleName);
            } catch (e) {
                context.onError(makeError('importscripts',
                                'importScripts failed for ' +
                                    moduleName + ' at ' + url,
                                e,
                                [moduleName]));
            }
        }
    };

    function getInteractiveScript() {
        if (interactiveScript && interactiveScript.readyState === 'interactive') {
            return interactiveScript;
        }

        eachReverse(scripts(), function (script) {
            if (script.readyState === 'interactive') {
                return (interactiveScript = script);
            }
        });
        return interactiveScript;
    }

    //Look for a data-main script attribute, which could also adjust the baseUrl.
    if (isBrowser && !cfg.skipDataMain) {
        //Figure out baseUrl. Get it from the script tag with require.js in it.
        eachReverse(scripts(), function (script) {
            //Set the 'head' where we can append children by
            //using the script's parent.
            if (!head) {
                head = script.parentNode;
            }

            //Look for a data-main attribute to set main script for the page
            //to load. If it is there, the path to data main becomes the
            //baseUrl, if it is not already set.
            dataMain = script.getAttribute('data-main');
            if (dataMain) {
                //Preserve dataMain in case it is a path (i.e. contains '?')
                mainScript = dataMain;

                //Set final baseUrl if there is not already an explicit one,
                //but only do so if the data-main value is not a loader plugin
                //module ID.
                if (!cfg.baseUrl && mainScript.indexOf('!') === -1) {
                    //Pull off the directory of data-main for use as the
                    //baseUrl.
                    src = mainScript.split('/');
                    mainScript = src.pop();
                    subPath = src.length ? src.join('/')  + '/' : './';

                    cfg.baseUrl = subPath;
                }

                //Strip off any trailing .js since mainScript is now
                //like a module name.
                mainScript = mainScript.replace(jsSuffixRegExp, '');

                //If mainScript is still a path, fall back to dataMain
                if (req.jsExtRegExp.test(mainScript)) {
                    mainScript = dataMain;
                }

                //Put the data-main script in the files to load.
                cfg.deps = cfg.deps ? cfg.deps.concat(mainScript) : [mainScript];

                return true;
            }
        });
    }

    /**
     * The function that handles definitions of modules. Differs from
     * require() in that a string for the module should be the first argument,
     * and the function to execute after dependencies are loaded should
     * return a value to define the module corresponding to the first argument's
     * name.
     */
    define = function (name, deps, callback) {
        var node, context;

        //Allow for anonymous modules
        if (typeof name !== 'string') {
            //Adjust args appropriately
            callback = deps;
            deps = name;
            name = null;
        }

        //This module may not have dependencies
        if (!isArray(deps)) {
            callback = deps;
            deps = null;
        }

        //If no name, and callback is a function, then figure out if it a
        //CommonJS thing with dependencies.
        if (!deps && isFunction(callback)) {
            deps = [];
            //Remove comments from the callback string,
            //look for require calls, and pull them into the dependencies,
            //but only if there are function args.
            if (callback.length) {
                callback
                    .toString()
                    .replace(commentRegExp, commentReplace)
                    .replace(cjsRequireRegExp, function (match, dep) {
                        deps.push(dep);
                    });

                //May be a CommonJS thing even without require calls, but still
                //could use exports, and module. Avoid doing exports and module
                //work though if it just needs require.
                //REQUIRES the function to expect the CommonJS variables in the
                //order listed below.
                deps = (callback.length === 1 ? ['require'] : ['require', 'exports', 'module']).concat(deps);
            }
        }

        //If in IE 6-8 and hit an anonymous define() call, do the interactive
        //work.
        if (useInteractive) {
            node = currentlyAddingScript || getInteractiveScript();
            if (node) {
                if (!name) {
                    name = node.getAttribute('data-requiremodule');
                }
                context = contexts[node.getAttribute('data-requirecontext')];
            }
        }

        //Always save off evaluating the def call until the script onload handler.
        //This allows multiple modules to be in a file without prematurely
        //tracing dependencies, and allows for anonymous module support,
        //where the module name is not known until the script onload event
        //occurs. If no context, use the global queue, and get it processed
        //in the onscript load callback.
        if (context) {
            context.defQueue.push([name, deps, callback]);
            context.defQueueMap[name] = true;
        } else {
            globalDefQueue.push([name, deps, callback]);
        }
    };

    define.amd = {
        jQuery: true
    };

    /**
     * Executes the text. Normally just uses eval, but can be modified
     * to use a better, environment-specific call. Only used for transpiling
     * loader plugins, not for plain JS modules.
     * @param {String} text the text to execute/evaluate.
     */
    req.exec = function (text) {
        /*jslint evil: true */
        return eval(text);
    };

    //Set up with config info.
    req(cfg);
}(this, (typeof setTimeout === 'undefined' ? undefined : setTimeout)));
formintorjs.requirejs = requirejs;formintorjs.require = require;formintorjs.define = define;
}
}());
formintorjs.define("requireLib", function(){});

/**
 * @license RequireJS text 2.0.3 Copyright (c) 2010-2012, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/requirejs/text for details
 */
/*jslint regexp: true */
/*global require: false, XMLHttpRequest: false, ActiveXObject: false,
  define: false, window: false, process: false, Packages: false,
  java: false, location: false */

formintorjs.define('text',['module'], function (module) {
    'use strict';

    var text, fs,
        progIds = ['Msxml2.XMLHTTP', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP.4.0'],
        xmlRegExp = /^\s*<\?xml(\s)+version=[\'\"](\d)*.(\d)*[\'\"](\s)*\?>/im,
        bodyRegExp = /<body[^>]*>\s*([\s\S]+)\s*<\/body>/im,
        hasLocation = typeof location !== 'undefined' && location.href,
        defaultProtocol = hasLocation && location.protocol && location.protocol.replace(/\:/, ''),
        defaultHostName = hasLocation && location.hostname,
        defaultPort = hasLocation && (location.port || undefined),
        buildMap = [],
        masterConfig = (module.config && module.config()) || {};

    text = {
        version: '2.0.3',

        strip: function (content) {
            //Strips <?xml ...?> declarations so that external SVG and XML
            //documents can be added to a document without worry. Also, if the string
            //is an HTML document, only the part inside the body tag is returned.
            if (content) {
                content = content.replace(xmlRegExp, "");
                var matches = content.match(bodyRegExp);
                if (matches) {
                    content = matches[1];
                }
            } else {
                content = "";
            }
            return content;
        },

        jsEscape: function (content) {
            return content.replace(/(['\\])/g, '\\$1')
                .replace(/[\f]/g, "\\f")
                .replace(/[\b]/g, "\\b")
                .replace(/[\n]/g, "\\n")
                .replace(/[\t]/g, "\\t")
                .replace(/[\r]/g, "\\r")
                .replace(/[\u2028]/g, "\\u2028")
                .replace(/[\u2029]/g, "\\u2029");
        },

        createXhr: masterConfig.createXhr || function () {
            //Would love to dump the ActiveX crap in here. Need IE 6 to die first.
            var xhr, i, progId;
            if (typeof XMLHttpRequest !== "undefined") {
                return new XMLHttpRequest();
            } else if (typeof ActiveXObject !== "undefined") {
                for (i = 0; i < 3; i += 1) {
                    progId = progIds[i];
                    try {
                        xhr = new ActiveXObject(progId);
                    } catch (e) {}

                    if (xhr) {
                        progIds = [progId];  // so faster next time
                        break;
                    }
                }
            }

            return xhr;
        },

        /**
         * Parses a resource name into its component parts. Resource names
         * look like: module/name.ext!strip, where the !strip part is
         * optional.
         * @param {String} name the resource name
         * @returns {Object} with properties "moduleName", "ext" and "strip"
         * where strip is a boolean.
         */
        parseName: function (name) {
            var strip = false, index = name.indexOf("."),
                modName = name.substring(0, index),
                ext = name.substring(index + 1, name.length);

            index = ext.indexOf("!");
            if (index !== -1) {
                //Pull off the strip arg.
                strip = ext.substring(index + 1, ext.length);
                strip = strip === "strip";
                ext = ext.substring(0, index);
            }

            return {
                moduleName: modName,
                ext: ext,
                strip: strip
            };
        },

        xdRegExp: /^((\w+)\:)?\/\/([^\/\\]+)/,

        /**
         * Is an URL on another domain. Only works for browser use, returns
         * false in non-browser environments. Only used to know if an
         * optimized .js version of a text resource should be loaded
         * instead.
         * @param {String} url
         * @returns Boolean
         */
        useXhr: function (url, protocol, hostname, port) {
            var uProtocol, uHostName, uPort,
                match = text.xdRegExp.exec(url);
            if (!match) {
                return true;
            }
            uProtocol = match[2];
            uHostName = match[3];

            uHostName = uHostName.split(':');
            uPort = uHostName[1];
            uHostName = uHostName[0];

            return (!uProtocol || uProtocol === protocol) &&
                   (!uHostName || uHostName.toLowerCase() === hostname.toLowerCase()) &&
                   ((!uPort && !uHostName) || uPort === port);
        },

        finishLoad: function (name, strip, content, onLoad) {
            content = strip ? text.strip(content) : content;
            if (masterConfig.isBuild) {
                buildMap[name] = content;
            }
            onLoad(content);
        },

        load: function (name, req, onLoad, config) {
            //Name has format: some.module.filext!strip
            //The strip part is optional.
            //if strip is present, then that means only get the string contents
            //inside a body tag in an HTML string. For XML/SVG content it means
            //removing the <?xml ...?> declarations so the content can be inserted
            //into the current doc without problems.

            // Do not bother with the work if a build and text will
            // not be inlined.
            if (config.isBuild && !config.inlineText) {
                onLoad();
                return;
            }

            masterConfig.isBuild = config.isBuild;

            var parsed = text.parseName(name),
                nonStripName = parsed.moduleName + '.' + parsed.ext,
                url = req.toUrl(nonStripName),
                useXhr = (masterConfig.useXhr) ||
                         text.useXhr;

            //Load the text. Use XHR if possible and in a browser.
            if (!hasLocation || useXhr(url, defaultProtocol, defaultHostName, defaultPort)) {
                text.get(url, function (content) {
                    text.finishLoad(name, parsed.strip, content, onLoad);
                }, function (err) {
                    if (onLoad.error) {
                        onLoad.error(err);
                    }
                });
            } else {
                //Need to fetch the resource across domains. Assume
                //the resource has been optimized into a JS module. Fetch
                //by the module name + extension, but do not include the
                //!strip part to avoid file system issues.
                req([nonStripName], function (content) {
                    text.finishLoad(parsed.moduleName + '.' + parsed.ext,
                                    parsed.strip, content, onLoad);
                });
            }
        },

        write: function (pluginName, moduleName, write, config) {
            if (buildMap.hasOwnProperty(moduleName)) {
                var content = text.jsEscape(buildMap[moduleName]);
                write.asModule(pluginName + "!" + moduleName,
                               "define(function () { return '" +
                                   content +
                               "';});\n");
            }
        },

        writeFile: function (pluginName, moduleName, req, write, config) {
            var parsed = text.parseName(moduleName),
                nonStripName = parsed.moduleName + '.' + parsed.ext,
                //Use a '.js' file name so that it indicates it is a
                //script that can be loaded across domains.
                fileName = req.toUrl(parsed.moduleName + '.' +
                                     parsed.ext) + '.js';

            //Leverage own load() method to load plugin value, but only
            //write out values that do not have the strip argument,
            //to avoid any potential issues with ! in file names.
            text.load(nonStripName, req, function (value) {
                //Use own write() method to construct full module value.
                //But need to create shell that translates writeFile's
                //write() to the right interface.
                var textWrite = function (contents) {
                    return write(fileName, contents);
                };
                textWrite.asModule = function (moduleName, contents) {
                    return write.asModule(moduleName, fileName, contents);
                };

                text.write(pluginName, nonStripName, textWrite, config);
            }, config);
        }
    };

    if (masterConfig.env === 'node' || (!masterConfig.env &&
            typeof process !== "undefined" &&
            process.versions &&
            !!process.versions.node)) {
        //Using special require.nodeRequire, something added by r.js.
        fs = require.nodeRequire('fs');

        text.get = function (url, callback) {
            var file = fs.readFileSync(url, 'utf8');
            //Remove BOM (Byte Mark Order) from utf8 files if it is there.
            if (file.indexOf('\uFEFF') === 0) {
                file = file.substring(1);
            }
            callback(file);
        };
    } else if (masterConfig.env === 'xhr' || (!masterConfig.env &&
            text.createXhr())) {
        text.get = function (url, callback, errback) {
            var xhr = text.createXhr();
            xhr.open('GET', url, true);

            //Allow overrides specified in config
            if (masterConfig.onXhr) {
                masterConfig.onXhr(xhr, url);
            }

            xhr.onreadystatechange = function (evt) {
                var status, err;
                //Do not explicitly handle errors, those should be
                //visible via console output in the browser.
                if (xhr.readyState === 4) {
                    status = xhr.status;
                    if (status > 399 && status < 600) {
                        //An http 4xx or 5xx error. Signal an error.
                        err = new Error(url + ' HTTP status: ' + status);
                        err.xhr = xhr;
                        errback(err);
                    } else {
                        callback(xhr.responseText);
                    }
                }
            };
            xhr.send(null);
        };
    } else if (masterConfig.env === 'rhino' || (!masterConfig.env &&
            typeof Packages !== 'undefined' && typeof java !== 'undefined')) {
        //Why Java, why is this so awkward?
        text.get = function (url, callback) {
            var stringBuffer, line,
                encoding = "utf-8",
                file = new java.io.File(url),
                lineSeparator = java.lang.System.getProperty("line.separator"),
                input = new java.io.BufferedReader(new java.io.InputStreamReader(new java.io.FileInputStream(file), encoding)),
                content = '';
            try {
                stringBuffer = new java.lang.StringBuffer();
                line = input.readLine();

                // Byte Order Mark (BOM) - The Unicode Standard, version 3.0, page 324
                // http://www.unicode.org/faq/utf_bom.html

                // Note that when we use utf-8, the BOM should appear as "EF BB BF", but it doesn't due to this bug in the JDK:
                // http://bugs.sun.com/bugdatabase/view_bug.do?bug_id=4508058
                if (line && line.length() && line.charAt(0) === 0xfeff) {
                    // Eat the BOM, since we've already found the encoding on this file,
                    // and we plan to concatenating this buffer with others; the BOM should
                    // only appear at the top of a file.
                    line = line.substring(1);
                }

                stringBuffer.append(line);

                while ((line = input.readLine()) !== null) {
                    stringBuffer.append(lineSeparator);
                    stringBuffer.append(line);
                }
                //Make sure we return a JavaScript string and not a Java string.
                content = String(stringBuffer.toString()); //String
            } finally {
                input.close();
            }
            callback(content);
        };
    }

    return text;
});


formintorjs.define('text!admin/templates/popups.html',[],function () { return '<div>\r\n\r\n\t<!-- Base Structure -->\r\n\t<script type="text/template" id="popup-tpl">\r\n\r\n\t\t<div class="sui-modal">\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="dialog"\r\n\t\t\t\tid="forminator-popup"\r\n\t\t\t\tclass="sui-modal-content"\r\n\t\t\t\taria-modal="true"\r\n\t\t\t\taria-labelledby="forminator-popup__title"\r\n\t\t\t\taria-describedby="forminator-popup__description"\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-box"></div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- Modal Header: Center-aligned title with floating close button -->\r\n\t<script type="text/template" id="popup-header-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close" data-modal-close>\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg">{{ title }}</h3>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- Modal Header: Inline title and close button -->\r\n\t<script type="text/template" id="popup-header-inline-tpl">\r\n\r\n\t\t<div class="sui-box-header">\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title">{{ title }}</h3>\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button class="sui-button-icon forminator-popup-close" data-modal-close>\r\n\t\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-integration-tpl">\r\n\r\n\t\t<div class="sui-modal sui-modal-sm">\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="dialog"\r\n\t\t\t\tid="forminator-integration-popup"\r\n\t\t\t\tclass="sui-modal-content"\r\n\t\t\t\taria-modal="true"\r\n\t\t\t\taria-labelledby="forminator-integration-popup__title"\r\n\t\t\t\taria-describedby="forminator-integration-popup__description"\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-box"></div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-integration-content-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<figure class="sui-box-logo" aria-hidden="true">\r\n\r\n\t\t\t\t<img\r\n\t\t\t\t\tsrc="{{ image }}"\r\n\t\t\t\t\tsrcset="{{ image }} 1x, {{ image_x2 }} 2x"\r\n\t\t\t\t\talt="{{ title }}"\r\n\t\t\t\t/>\r\n\r\n\t\t\t</figure>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--left forminator-addon-back" style="display: none;">\r\n\t\t\t\t<span class="sui-icon-chevron-left sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Back</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-integration-close" data-modal-close>\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<div class="forminator-integration-popup__header"></div>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="forminator-integration-popup__body sui-box-body"></div>\r\n\r\n\t\t<div class="forminator-integration-popup__footer sui-box-footer sui-flatten sui-content-separated"></div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-loader-tpl">\r\n\r\n\t\t<p style="margin: 0; text-align: center;" aria-hidden="true"><span class="sui-icon-loader sui-md sui-loading"></span></p>\r\n\t\t<p class="sui-screen-reader-text">Loading content...</p>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-stripe-tpl">\r\n\r\n\t\t<div class="sui-modal sui-modal-sm">\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="dialog"\r\n\t\t\t\tid="forminator-stripe-popup"\r\n\t\t\t\tclass="sui-modal-content"\r\n\t\t\t\taria-modal="true"\r\n\t\t\t\taria-labelledby="forminator-stripe-popup__title"\r\n\t\t\t\taria-describedby=""\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-box"></div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-stripe-content-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<figure class="sui-box-logo" aria-hidden="true">\r\n\t\t\t\t<img src="{{ image }}" srcset="{{ image }} 1x, {{ image_x2 }} 2x" alt="{{ title }}" />\r\n\t\t\t</figure>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close">\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.close_label }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-stripe-popup__title" class="sui-box-title sui-lg" style="overflow: initial; display: none; white-space: normal; text-overflow: initial;">{{ title }}</h3>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--10"></div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-right"></div>\r\n\r\n\t</script>\r\n\r\n</div>\r\n';});

(function ($) {
	window.empty = function (what) { return "undefined" === typeof what ? true : !what; };
	window.count = function (what) { return "undefined" === typeof what ? 0 : (what && what.length ? what.length : 0); };
	window.stripslashes = function (what) {
		return (what + '')
		.replace(/\\(.?)/g, function (s, n1) {
			switch (n1) {
				case '\\':
					return '\\'
				case '0':
					return '\u0000'
				case '':
					return ''
				default:
					return n1
			}
		});
	};
	window.forminator_array_value_exists = function ( array, key ) {
		return ( !_.isUndefined( array[ key ] ) && ! _.isEmpty( array[ key ] ) );
	};
	window.decodeHtmlEntity = function(str) {
		if( typeof str === "undefined" ) return str;

		return str.replace( /&#(\d+);/g, function( match, dec ) {
			return String.fromCharCode( dec );
		});
	};
	window.encodeHtmlEntity = function(str) {
		if( typeof str === "undefined" ) return str;
		var buf = [];
		for ( var i=str.length-1; i>=0; i-- ) {
			buf.unshift( ['&#', str[i].charCodeAt(), ';'].join('') );
		}
		return buf.join('');
	};
	window.singularPluralText = function( count, singular, plural ) {
		var txt  = '';

		if ( count < 2 ) {
			txt = singular;
		} else {
			txt = plural;
		}

		return count + ' ' + txt;
	};

	formintorjs.define('admin/utils',[
		'text!admin/templates/popups.html'
	], function ( popupTpl ) {
		var Utils = {
			/**
			 * generated field id
			 * {
			 *  type : [id,id]
			 * }
			 * sample
			 * {text:[1,2,3],phone:[1,2,3]}
			 */
			fields_ids: [],

			/**
			 * Forminator_Google_font_families
			 * @since 1.0.5
			 */
			google_font_families: [],
			/*
			 * Returns if touch device ( using wp_is_mobile() )
			 */
			is_touch: function () {
				return Forminator.Data.is_touch;
			},

			/*
			 * Returns if window resized for browser
			 */
			is_mobile_size: function () {
				if ( window.screen.width <= 782 ) return true;

				return false;
			},

			/*
			 * Return if touch or windows mobile width
			 */
			is_mobile: function () {
				if( Forminator.Utils.is_touch() || Forminator.Utils.is_mobile_size() ) return true;

				return false;
			},

			/*
			 * Extend default underscore template with mustache style
			 */
			template: function( markup ) {
				// Each time we re-render the dynamic markup we initialize mustache style
				_.templateSettings = {
					evaluate : /\{\[([\s\S]+?)\]\}/g,
					interpolate : /\{\{([\s\S]+?)\}\}/g
				};

				return _.template( markup );
			},

			/*
			 * Extend default underscore template with PHP
			 */
			template_php: function( markup ) {
				var oldSettings = _.templateSettings,
				tpl = false;

				_.templateSettings = {
					interpolate : /<\?php echo (.+?) \?>/g,
					evaluate: /<\?php (.+?) \?>/g
				};

				tpl = _.template(markup);

				_.templateSettings = oldSettings;

				return function(data){
					_.each(data, function(value, key){
						data['$' + key] = value;
					});

					return tpl(data);
				};
			},

			/**
			 * Capitalize string
			 *
			 * @param value
			 * @returns {string}
			 */
			ucfirst: function( value ) {
				return value.charAt(0).toUpperCase() + value.slice(1);
			},

			/*
			 * Returns slug from title
			 */
			get_slug: function ( title ) {
				title = title.replace( ' ', '-' );
				title = title.replace( /[^-a-zA-Z0-9]/, '' );
				return title;
			},

			/*
			 * Returns slug from title
			 */
			sanitize_uri_string: function ( string ) {
				// Decode URI components
				var decoded = decodeURIComponent( string );

				// Replace interval with -
				decoded = decoded.replace( /-/g, ' ' );

				return decoded;
			},

			/*
			 * Return URL param value
			 */
			get_url_param: function ( param ) {
				var page_url = window.location.search.substring(1),
					url_params = page_url.split('&')
				;

				for ( var i = 0; i < url_params.length; i++ ) {
					var param_name = url_params[i].split('=');
					if ( param_name[0] === param ) {
						return param_name[1];
					}
				}

				return false;
			},

			/**
			 * Check if email acceptable by WP
			 * @param value
			 * @returns {boolean}
			 */
			is_email_wp: function (value) {
				if (value.length < 6) {
					return false;
				}

				// Test for an @ character after the first position
				if (value.indexOf('@', 1) < 0) {
					return false;
				}

				// Split out the local and domain parts
				var parts = value.split('@', 2);

				// LOCAL PART
				// Test for invalid characters
				if (!parts[0].match(/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~\.-]+$/)) {
					return false;
				}

				// DOMAIN PART
				// Test for sequences of periods
				if (parts[1].match(/\.{2,}/)) {
					return false;
				}

				var domain = parts[1];
				// Split the domain into subs
				var subs = domain.split('.');
				if (subs.length < 2) {
					return false;
				}

				var subsLen = subs.length;
				for (var i = 0; i < subsLen; i++) {
					// Test for invalid characters
					if (!subs[i].match(/^[a-z0-9-]+$/i)) {
						return false;
					}
				}
				return true;
			},

			forminator_select2_tags: function( $el, options ) {
				var select = $el.find( 'select.sui-select.fui-multi-select' );

				// SELECT2 forminator-ui-tags
				select.each( function() {
					var select       = $( this ),
						getParent    = select.closest( '.sui-modal-content' ),
						getParentId  = getParent.attr( 'id' ),
						selectParent = ( getParent.length ) ? $( '#' + getParentId ) : $( 'SUI_BODY_CLASS' ),
						hasSearch    = ( 'true' === select.attr( 'data-search' ) ) ? 0 : -1,
						isSmall      = select.hasClass( 'sui-select-sm' ) ? 'sui-select-dropdown-sm' : '';

					options = _.defaults( options, {
						dropdownParent: selectParent,
						minimumResultsForSearch: hasSearch,
						dropdownCssClass: isSmall
					});

					// reorder-support, it will preserve order based on user tags added
					if ( select.attr( 'data-reorder' ) ) {
						select.on( 'select2:select', function( e ) {
							var elm  = e.params.data.element,
							    $elm = $( elm ),
							    $t   = select;

							$t.append( $elm );
							$t.trigger( 'change.select2' );
						});
					}

					select.SUIselect2( options );
				});
			},

			forminator_select2_custom: function ($el, options) {
				// SELECT2 custom
				$el.find( 'select.sui-select.custom-select2' ).each( function() {
					var select       = $( this ),
						getParent    = select.closest( '.sui-modal-content' ),
						getParentId  = getParent.attr( 'id' ),
						selectParent = ( getParent.length ) ? $( '#' + getParentId ) : $( 'body' ),
						hasSearch    = ( 'true' === select.attr( 'data-search' ) ) ? 0 : -1,
						isSmall      = select.hasClass( 'sui-select-sm' ) ? 'sui-select-dropdown-sm' : '';

					options = _.defaults( options, {
						dropdownParent: selectParent,
						minimumResultsForSearch: hasSearch,
						dropdownCssClass: isSmall
					});

					// Reorder-support, it will preserve order based on user tags added.
					if ( select.attr( 'data-reorder' ) ) {
						select.on( 'select2:select', function( e ) {
							var elm  = e.params.data.element,
							    $elm = $(elm),
							    $t   = $( this );
							$t.append( $elm );
							$t.trigger( 'change.select2' );
						});
					}

					select.SUIselect2( options );
				});
			},

			/*
			 * Initialize Select 2
			 */
			init_select2: function(){
				var self = this;
				if ( 'object' !== typeof window.SUI ) return;
			},

			load_google_fonts: function (callback) {
				var self = this;
				$.ajax({
					url : Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: "forminator_load_google_fonts",
						_wpnonce: Forminator.Data.gFontNonce
					}
				}).done(function (result) {
					if (result.success === true) {
						// cache result
						self.google_font_families = result.data;
					}
					// do callback even font_families is empty
					callback.apply(result, [self.google_font_families]);
				});
			},

			sui_delegate_events: function() {
				var self = this;
				if ( 'object' !== typeof window.SUI ) return;

				// Time it out
				setTimeout( function() {
					// Rebind Accordion scripts.
					SUI.suiAccordion($('.sui-accordion'));

					// Rebind Tabs scripts.
					SUI.suiTabs( $( '.sui-tabs' ) );

					// Rebind Select2 scripts.
					$( 'select.sui-select[data-theme="icon"]' ).each( function() {
						SUI.select.initIcon( $( this ) );
					});

					$( 'select.sui-select[data-theme="color"]' ).each( function() {
						SUI.select.initColor( $( this ) );
					});

					$( 'select.sui-select[data-theme="search"]' ).each( function() {
						SUI.select.initSearch( $( this ) );
					});

					$( 'select.sui-select:not([data-theme]):not(.custom-select2):not(.fui-multi-select)' ).each( function() {
						SUI.select.init( $( this ) );
					});

					// Rebind Variables scripts.
					$( 'select.sui-variables' ).each( function() {
						SUI.select.initVars( $( this ) );
					});

					// Rebind Circle scripts.
					SUI.loadCircleScore( $( '.sui-circle-score' ) );

					// Rebind Password scripts.
					SUI.showHidePassword();

				}, 50);
			},
		};

		var Popup = {
			$popup: {},
			_deferred: {},

			initialize: function () {

				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-tpl' ).html() );

				if ( ! $( "#forminator-popup" ).length ) {
					$( "main.sui-wrap" ).append( tpl({}) );
				} else {
					$( "#forminator-popup" ).remove();
					this.initialize();
				}

				this.$popup = $( "#forminator-popup" );
				this.$popupId = 'forminator-popup';
				this.$focusAfterClosed = 'wpbody-content';

			},

			open: function ( callback, data, size, title ) {
				this.data             = data;
				this.title            = '';
				this.action_text      = '';
				this.action_callback  = false;
				this.action_css_class = '';
				this.has_custom_box   = false;
				this.has_footer       = true;

				var header_tpl = '';

				switch ( title ) {
					case 'inline':
						header_tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-header-inline-tpl' ).html() );
						break;

					case 'center':
						header_tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-header-tpl' ).html() );
						break;
				}

				if ( !_.isUndefined( this.data ) ) {
					if ( !_.isUndefined( this.data.title ) ) {
						this.title = this.data.title;
					}

					if ( !_.isUndefined( this.data.has_footer ) ) {
						this.has_footer = this.data.has_footer;
					}

					if ( !_.isUndefined( this.data.action_callback ) &&
						!_.isUndefined( this.data.action_text ) ) {
						this.action_callback = this.data.action_callback;
						this.action_text = this.data.action_text;
						if ( !_.isUndefined( this.data.action_css_class ) ) {
							this.action_css_class = this.data.action_css_class;
						}
					}

					if ( !_.isUndefined( this.data.has_custom_box ) ) {
						this.has_custom_box = this.data.has_custom_box;
					}
				}

				this.initialize();

				// restart base structure
				if ( '' !== header_tpl ) {
					this.$popup.find( '.sui-box' ).html( header_tpl({
						title: this.title
					}) );
				}

				var self = this,
					close_click = function () {
						self.close();
						return false;
					}
				;

				// Set modal size
				if ( size ) {
					this.$popup.closest( '.sui-modal' )
						.addClass( 'sui-modal-' + size );
				}

				if ( this.has_custom_box ) {
					callback.apply( this.$popup.find( '.sui-box' ).get(), data );
				} else {
					var box_markup = '<div class="sui-box-body">' +
						'</div>';

					if( this.has_footer ) {
						box_markup += '<div class="sui-box-footer">' +
							'<button class="sui-button forminator-popup-cancel">' + Forminator.l10n.popup.cancel +'</button>' +
						'</div>';
					}

					this.$popup.find('.sui-box').append( box_markup );
					callback.apply(this.$popup.find(".sui-box-body").get(), data);
				}

				// Add additional Button if callback_action available
				if (this.action_text && this.action_callback) {
					var action_callback = this.action_callback;
					this.$popup.find('.sui-box-footer').append(
						'<div class="sui-actions-right">' +
							'<button class="forminator-popup-action sui-button ' + this.action_css_class + '">' + this.action_text + '</button>' +
						'</div>'
					);
					this.$popup.find('.forminator-popup-action').on('click', function(){
						if( action_callback ) {
							action_callback.apply();
						}
						self.close();
					});
				} else {
					this.$popup.find('.forminator-popup-action').remove();
				}

				// Add closing event
				this.$popup.find( ".forminator-popup-close" ).on( "click", close_click );
				this.$popup.find( ".forminator-popup-cancel" ).on( "click", close_click );
				this.$popup.on( "click", '.forminator-popup-cancel', close_click );

				// Open Modal
				SUI.openModal(
					this.$popupId,
					this.$focusAfterClosed,
					undefined,
					true,
					true
				);

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result, callback ) {
				var self = this;
				// Close Modal
				SUI.closeModal();

				setTimeout(function () {

					// Remove modal size.
					self.$popup.closest( '.sui-modal' )
						.removeClass( 'sui-modal-sm' )
						.removeClass( 'sui-modal-md' )
						.removeClass( 'sui-modal-lg' )
						.removeClass( 'sui-modal-xl' );

					if( callback ) {
						callback.apply();
					}
				}, 300);

				this._deferred.resolve( this.$popup, result );
			}
		};

		var Notification = {
			$notification: {},
			_deferred: {},

			initialize: function () {

				if ( ! $( ".sui-floating-notices" ).length ) {

					$( "main.sui-wrap" )
						.prepend(
							'<div class="sui-floating-notices">' +
								'<div role="alert" id="forminator-floating-notification" class="sui-notice" aria-live="assertive"></div>' +
							'</div>'
						);

				} else {
					$( ".sui-floating-notices" ).remove();
					this.initialize();
				}

				this.$notification = $( "#forminator-floating-notification" );
			},

			open: function ( type, text, closeTime ) {
				var self = this;
				var noticeTimeOut = closeTime;

				if ( ! _.isUndefined( closeTime ) ) {
					noticeTimeOut = 5000;
				}

				this.uniq = 'forminator-floating-notification';
				this.text = '<p>' + text + '</p>';
				this.type = '';
				this.time = closeTime || 5000;

				if ( ! _.isUndefined( type ) && '' !== type ) {
					this.type = type;
				}

				if ( ! _.isUndefined( closeTime ) ) {
					this.time = closeTime;
				}

				this.opts = {
					type: this.type,
					autoclose: {
						show: true,
						timeout: this.time
					}
				};

				this.initialize();

				SUI.openNotice(
					this.uniq,
					this.text,
					this.opts
				);


				setTimeout( function () {
					self.close();
				}, 3000 );

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result ) {
				this._deferred.resolve( this.$popup, result );
			}
		};

		var Integrations_Popup = {
			$popup: {},
			_deferred: {},

			initialize: function () {
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-integration-tpl' ).html() );

				if ( ! $( "#forminator-integration-popup" ).length ) {

					$( "main.sui-wrap" ).append( tpl({
						provider_image: '',
						provider_image2: '',
						provider_title: ''
					}));

				} else {
					$( "#forminator-integration-popup" ).remove();
					this.initialize();
				}

				this.$popup = $( "#forminator-integration-popup" );
				this.$popupId = 'forminator-integration-popup';
				this.$focusAfterClosed = 'forminator-integrations-page';

			},

			open: function ( callback, data, className ) {
				this.data             = data;
				this.title            = '';
				this.image            = '';
				this.image_x2         = '';
				this.action_text      = '';
				this.action_callback  = false;
				this.action_css_class = '';
				this.has_custom_box   = false;
				this.has_footer       = true;

				if ( !_.isUndefined( this.data ) ) {
					if ( !_.isUndefined( this.data.title ) ) {
						this.title = this.data.title;
					}

					if ( !_.isUndefined( this.data.image ) ) {
						this.image = this.data.image;
					}

					if ( !_.isUndefined( this.data.image_x2 ) ) {
						this.image_x2 = this.data.image_x2;
					}
				}

				this.initialize();

				// restart base structure
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-integration-content-tpl' ).html() );

				this.$popup.find('.sui-box').html(tpl({
					image: this.image,
					image_x2: this.image_x2,
					title: this.title
				}));

				var self = this,
					close_click = function () {
						self.close();
						return false;
					}
				;

				// Add custom class
				if ( className ) {
					this.$popup
						.addClass( className )
					;
				}

				callback.apply(this.$popup.get(), data);

				// Add additional Button if callback_action available
				if (this.action_text && this.action_callback) {
					var action_callback = this.action_callback;
					this.$popup.find('.sui-box-footer').append(
						'<button class="forminator-popup-action sui-button ' + this.action_css_class + '">' + this.action_text + '</button>'
					);
					this.$popup.find('.forminator-popup-action').on('click', function(){
						if( action_callback ) {
							action_callback.apply();
						}
						self.close();
					});
				} else {
					this.$popup.find('.forminator-popup-action').remove();
				}

				// Add closing event
				this.$popup.find( ".sui-dialog-close" ).on( "click", close_click );
				this.$popup.on( "click", '.forminator-popup-cancel', close_click );

				// Open Modal
				SUI.openModal( this.$popupId, this.$focusAfterClosed );

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result, callback ) {

				// Refrest add-on list
				Forminator.Events.trigger( "forminator:addons:reload" );

				// Close Modal
				SUI.closeModal();

				setTimeout(function () {
					if( callback ) {
						callback.apply();
					}
				}, 300);

				this._deferred.resolve( this.$popup, result );
			}
		};

		var Stripe_Popup = {
			$popup: {},
			_deferred: {},

			initialize: function () {
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-stripe-tpl' ).html() );

				if ( ! $( "#forminator-stripe-popup" ).length ) {

					$( "main.sui-wrap" ).append( tpl({
						provider_image: '',
						provider_image2: '',
						provider_title: ''
					}));

				} else {
					$( "#forminator-stripe-popup" ).remove();
					this.initialize();
				}

				this.$popup = $( "#forminator-stripe-popup" );
				this.$popupId = 'forminator-stripe-popup';
				this.$focusAfterClosed = 'wpbody-content';

			},

			open: function ( callback, data ) {
				this.data             = data;
				this.title            = '';
				this.image            = '';
				this.image_x2         = '';
				this.action_text      = '';
				this.action_callback  = false;
				this.action_css_class = '';
				this.has_custom_box   = false;
				this.has_footer       = true;

				if ( !_.isUndefined( this.data ) ) {
					if ( !_.isUndefined( this.data.title ) ) {
						this.title = this.data.title;
					}

					if ( !_.isUndefined( this.data.image ) ) {
						this.image = this.data.image;
					}

					if ( !_.isUndefined( this.data.image_x2 ) ) {
						this.image_x2 = this.data.image_x2;
					}
				}

				this.initialize();

				// restart base structure
				var tpl = Forminator.Utils.template( $( popupTpl ).find( '#popup-stripe-content-tpl' ).html() );

				this.$popup.find('.sui-box').html(tpl({
					image: this.image,
					image_x2: this.image_x2,
					title: this.title
				}));

				this.$popup.find('.sui-box-footer').css({
					'padding-top': '0',
				});

				var self = this,
				    close_click = function () {
					    self.close();
					    return false;
				    }
				;

				callback.apply(this.$popup.get(), data);

				// Add additional Button if callback_action available
				if (this.action_text && this.action_callback) {
					var action_callback = this.action_callback;
					this.$popup.find('.sui-box-footer').append(
						'<div class="sui-actions-right">' +
						'<button class="forminator-popup-action sui-button ' + this.action_css_class + '">' + this.action_text + '</button>' +
						'</div>'
					);
					this.$popup.find('.forminator-popup-action').on('click', function(){
						if( action_callback ) {
							action_callback.apply();
						}
						self.close();
					});
				} else {
					this.$popup.find('.forminator-popup-action').remove();
				}

				// Add closing event
				this.$popup.find( ".forminator-popup-close" ).on( "click", close_click );
				this.$popup.on( "click", '.forminator-popup-cancel', close_click );

				// Open Modal
				SUI.openModal(
					this.$popupId,
					this.$focusAfterClosed,
					undefined,
					true,
					true
				);

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				this._deferred = new $.Deferred();
				return this._deferred.promise();
			},

			close: function ( result, callback ) {

				// Close Modal
				SUI.closeModal();

				var self = this;

				setTimeout(function () {

					// Remove modal size.
					self.$popup.closest( '.sui-modal' )
						.removeClass( 'sui-modal-sm' )
						.removeClass( 'sui-modal-md' )
						.removeClass( 'sui-modal-lg' )
						.removeClass( 'sui-modal-xl' );

					if( callback ) {
						callback.apply();
					}
				}, 300);

				this._deferred.resolve( this.$popup, result );
			}
		};

		return {
			Utils: Utils,
			Popup: Popup,
			Integrations_Popup: Integrations_Popup,
			Stripe_Popup: Stripe_Popup,
			Notification: Notification
		};
	});

})(jQuery);

(function ($) {
	formintorjs.define('admin/dashboard',[
	], function( TemplatesPopup ) {
		var Dashboard = Backbone.View.extend({
			el: '.wpmudev-dashboard-section',
			events: {
				"click .wpmudev-action-close": "dismiss_welcome"
			},
			initialize: function () {
				var notification = Forminator.Utils.get_url_param( 'notification' ),
					form_title = Forminator.Utils.get_url_param( 'title' ),
					create = Forminator.Utils.get_url_param( 'createnew' )
				;

				setTimeout( function() {
					if ( $( '.forminator-scroll-to' ).length ) {
						$('html, body').animate({
							scrollTop: $( '.forminator-scroll-to' ).offset().top - 50
						}, 'slow');
					}
				}, 100 );

				if( notification ) {
					var markup = _.template( '<strong>{{ formName }}</strong> {{ Forminator.l10n.options.been_published }}' );

					Forminator.Notification.open( 'success', markup({
						formName: Forminator.Utils.sanitize_uri_string( form_title )
					}), 4000 );
				}

				if ( create ) {
					setTimeout( function() {
						jQuery( '.forminator-create-' + create ).click();
					}, 200 );
				}

				return this.render();
			},

			dismiss_welcome: function( e ) {
				e.preventDefault();

				var $container = $( e.target ).closest( '.sui-box' ),
					$nonce = $( e.target ).data( "nonce" )
				;

				$container.slideToggle( 300, function() {
					$.ajax({
						url: Forminator.Data.ajaxUrl,
						type: "POST",
						data: {
							action: "forminator_dismiss_welcome",
							_ajax_nonce: $nonce
						},
						complete: function( result ){
							$container.remove();
						}
					});
				});
			},

			render: function() {

				if ( $( '#forminator-new-feature' ).length > 0 ) {

					setTimeout( function () {
						SUI.openModal(
							'forminator-new-feature',
							'wpbody-content'
						);
					}, 300 );
				}
			}
		});

		var DashView = new Dashboard();

		return DashView;
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/settings-page',[
	], function() {
		var SettingsPage = Backbone.View.extend({
			el: '.wpmudev-forminator-forminator-settings, .wpmudev-forminator-forminator-addons',
			events: {
				'click .sui-side-tabs label.sui-tab-item input': 'sidetabs',
				"click .sui-sidenav .sui-vertical-tab a": "sidenav",
				"change .sui-sidenav select.sui-mobile-nav": "sidenav_select",
				"click .stripe-connect-modal" : 'open_stripe_connect_modal',
				"click .paypal-connect-modal" : 'open_paypal_connect_modal',
				"click .forminator-stripe-connect" : 'connect_stripe',
				"click .disconnect_stripe": "disconnect_stripe",
				"click .forminator-paypal-connect" : 'connect_paypal',
				"click .disconnect_paypal": "disconnect_paypal",
				'click button.sui-tab-item': 'buttonTabs',
				"click .forminator-toggle-unsupported-settings": "show_unsupported_settings",
				"click .forminator-dismiss-unsupported": "hide_unsupported_settings",
				"click button.addons-configure": "open_configure_connect_modal",
			},
			initialize: function () {
				var self = this;

				// only trigger on settings page
				if (!$('.wpmudev-forminator-forminator-settings').length && !$('.wpmudev-forminator-forminator-addons').length ) {
					return;
				}
				// on submit
				this.$el.find('.forminator-settings-save').submit( function(e) {
					e.preventDefault();

					var $form = $( this ),
						nonce = $form.find('.wpmudev-action-done').data( "nonce" ),
						action = $form.find('.wpmudev-action-done').data( "action" ),
						title = $form.find('.wpmudev-action-done').data( "title" ),
						isReload = $form.find('.wpmudev-action-done').data( "isReload" )
					;

					self.submitForm( $( this ), action, nonce, title, isReload );
				});

				var hash = window.location.hash;
				if( ! _.isUndefined( hash ) && ! _.isEmpty( hash ) ) {
					this.sidenav_go_to( hash.substring(1), true );
				}

				this.renderHcaptcha();
				this.render( 'v2' );
				this.render( 'v2-invisible' );
				this.render( 'v3' );

				// Save captcha tab last saved
				this.captchaTab();
			},

			captchaTab: function() {
				var $captchaType = this.$el.find( 'input[name="captcha_tab_saved"]' ),
					$tabs	   	 = this.$el.find( 'button.captcha-main-tab' )
				;

				$tabs.on( 'click', function ( e ) {
					e.preventDefault();
					$captchaType.val( $( this ).data( 'tab-name' ) );
				} );
			},

			render: function ( captcha ) {
				var self = this,
					$container = this.$el.find( '#' + captcha + '-recaptcha-preview' )
				;

				var preloader =
					'<p class="fui-loading-dialog">' +
					'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
					'</p>'
				;

				$container.html( preloader );

				$.ajax({
					url : Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: "forminator_load_recaptcha_preview",
						captcha: captcha,
					}
				}).done(function (result) {
					if( result.success ) {
						$container.html( result.data );
					}
				});
			},

			renderHcaptcha: function () {
				var $hcontainer = this.$el.find( '#hcaptcha-preview' );

				var preloader =
					'<p class="fui-loading-dialog">' +
					'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
					'</p>'
				;

				$hcontainer.html( preloader );

				$.ajax({
					url : Forminator.Data.ajaxUrl,
					type: "POST",
					data: {
						action: "forminator_load_hcaptcha_preview",
					}
				}).done(function (result) {
					if( result.success ) {
						$hcontainer.html( result.data );
					}
				});
			},

			submitForm: function( $form, action, nonce, title, isReload ) {
				var data = {},
					self = this
				;

				data.action = 'forminator_save_' + action + '_popup';
				data._ajax_nonce = nonce;

				var ajaxData = $form.serialize() + '&' + $.param(data);

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: ajaxData,
					beforeSend: function() {
						$form.find('.sui-button').addClass('sui-button-onload');
					},
					success: function( result ) {
						var markup = _.template( '<strong>{{ tab }}</strong> {{ Forminator.l10n.commons.update_successfully }}' );

						Forminator.Notification.open( 'success', markup({
							tab: title
						}), 4000 );

						if( action === "captcha" ) {

							$captcha_tab_saved = $form.find( 'input[name="captcha_tab_saved"]' ).val();
							$captcha_tab_saved = '' === $captcha_tab_saved ? 'recaptcha' : $captcha_tab_saved;
							if ( 'recaptcha' === $captcha_tab_saved ) {
								self.render( 'v2' );
								self.render( 'v2-invisible' );
								self.render( 'v3' );
							} else if ( 'hcaptcha' === $captcha_tab_saved ) {
								self.renderHcaptcha();
							}

						}

						if (isReload) {
							window.location.reload();
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', Forminator.l10n.commons.update_unsuccessfull, 4000 );
					}
				}).always(function(){
					$form.find('.sui-button').removeClass('sui-button-onload');
				});
			},

			sidetabs: function( e ) {
				var $this      = this.$( e.target ),
					$label     = $this.parent( 'label' ),
					$data      = $this.data( 'tab-menu' ),
					$wrapper   = $this.closest( '.sui-side-tabs' ),
					$alllabels = $wrapper.find( '.sui-tabs-menu .sui-tab-item' ),
					$allinputs = $alllabels.find( 'input' )
				;

				if ( $this.is( 'input' ) ) {

					$alllabels.removeClass( 'active' );
					$allinputs.removeAttr( 'checked' );
					$wrapper.find( '.sui-tabs-content > div' ).removeClass( 'active' );

					$label.addClass( 'active' );
					$this.prop( 'checked', 'checked' );

					if ( $wrapper.find( '.sui-tabs-content div[data-tab-content="' + $data + '"]' ).length ) {
						$wrapper.find( '.sui-tabs-content div[data-tab-content="' + $data + '"]' ).addClass( 'active' );
					}
				}
			},

			sidenav: function( e ) {
				var tab_name = $( e.target ).data( 'nav' );
				if ( tab_name ) {
					this.sidenav_go_to( tab_name, true );
				}
				e.preventDefault();

			},

			sidenav_select: function( e ) {
				var tab_name = $(e.target).val();
				if ( tab_name ) {
					this.sidenav_go_to( tab_name, true );
				}
				e.preventDefault();

			},

			sidenav_go_to: function( tab_name, update_history ) {

				var $tab 	 = this.$el.find( 'a[data-nav="' + tab_name + '"]' ),
				    $sidenav = $tab.closest( '.sui-vertical-tabs' ),
				    $tabs    = $sidenav.find( '.sui-vertical-tab' ),
				    $content = this.$el.find( '.sui-box[data-nav]' ),
				    $current = this.$el.find( '.sui-box[data-nav="' + tab_name + '"]' );

				if ( update_history ) {
					history.pushState( { selected_tab: tab_name }, 'Global Settings', 'admin.php?page=forminator-settings&section=' + tab_name );
				}

				$tabs.removeClass( 'current' );
				$content.hide();

				$tab.parent().addClass( 'current' );
				$current.show();
			},

			open_configure_connect_modal: function ( e ) {
				var $target = $(e.target),
					actions = $target.data('action');
				if ( 'stripe-connect-modal' === actions ) {
					this.open_stripe_connect_modal( e );
				} else if ( 'paypal-connect-modal' === actions ) {
					this.open_paypal_connect_modal( e );
				}
			},

			open_stripe_connect_modal: function (e) {

				e.preventDefault();

				var self = this;
				var $target  = $(e.target);
				var image    = $target.data('modalImage');
				var image_x2 = $target.data('modalImageX2');
				var title    = $target.data('modalTitle');
				var nonce    = $target.data('modalNonce');

				var popup_options = {
					title: title,
					image: image,
					image_x2: image_x2
				}

				Forminator.Stripe_Popup.open( function () {
					var popup = $(this);
					self.render_stripe_connect_modal_content(popup, nonce, {});
				}, popup_options );

				return false;
			},

			render_stripe_connect_modal_content: function (popup, popup_nonce, request_data) {
				var self = this;
				request_data.action      = 'forminator_stripe_settings_modal';
				request_data._ajax_nonce = popup_nonce;

				var preloader =
					'<p class="fui-loading-modal">' +
					'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
					'</p>'
				;

				popup.find(".sui-box-body").html(preloader);

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: request_data
				})
					.done(function (result) {
						if (result && result.success) {
							// Imitate title loading
							popup.find(".sui-box-header h3.sui-box-title").show();

							// Render popup body
							popup.find(".sui-box-body").html(result.data.html);

							// Render popup footer
							var buttons = result.data.buttons;

							// Clear footer from previous buttons
							popup.find(".sui-box-footer").html('');

							// Append buttons
							_.each( buttons, function( button ) {
								popup.find( '.sui-box-footer' ).append( button.markup );
							});

							popup.find(".sui-button").removeClass("sui-button-onload");

							// Handle notifications
							if (!_.isUndefined(result.data.notification) &&
								!_.isUndefined(result.data.notification.type) &&
								!_.isUndefined(result.data.notification.text) &&
								!_.isUndefined(result.data.notification.duration)
							) {

								Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
									.done(function () {
										// Notification opened
									});

								self.update_stripe_page( popup_nonce );
							}
						}
					});
			},

			update_stripe_page: function( nonce ) {
				var new_request = {
					action: 'forminator_stripe_update_page',
					_ajax_nonce: nonce,
				}

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-stripe' ).html( result.data );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();
					});
			},

			show_unsupported_settings: function (e) {
				e.preventDefault();

				$('.forminator-unsupported-settings').show();
			},

			hide_unsupported_settings: function (e) {
				e.preventDefault();

				$('.forminator-unsupported-settings').hide();
			},

			connect_stripe: function (e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var nonce        = $target.data('nonce');
				var popup        = this.$el.find('#forminator-stripe-popup');
				var form         = popup.find('form');
				var data         = form.serializeArray();
				var indexedArray = {};

				$.map(data, function (n, i) {
					indexedArray[n['name']] = n['value'];
				});
				indexedArray['connect'] = true;

				this.render_stripe_connect_modal_content(popup, nonce, indexedArray);

				return false;
			},

			/**
			 * WAI-ARIA Side Tabs
			 *
			 * @since 1.7.2
			 */
			buttonTabs: function( e ) {

				var button = this.$( e.target ),
					wrapper = button.closest( '.sui-tabs' ),
					list    = wrapper.find( '.sui-tabs-menu .sui-tab-item' ),
					panes   = wrapper.find( '.sui-tabs-content .sui-tab-content' )
					;

				if ( button.is( 'button' ) ) {

					// Reset lists
					list.removeClass( 'active' );
					list.attr( 'tabindex', '-1' );

					// Reset panes
					panes.attr( 'hidden', true );
					panes.removeClass('active');

					// Select current tab
					button.removeAttr( 'tabindex' );
					button.addClass( 'active' );

					// Select current content
					wrapper.find( '#' + button.attr( 'aria-controls' ) ).addClass( 'active' );
					wrapper.find( '#' + button.attr( 'aria-controls' ) ).attr( 'hidden', false );
					wrapper.find( '#' + button.attr( 'aria-controls' ) ).removeAttr( 'hidden' );
				}

				e.preventDefault();

			},

			/**
			 * Paypal
			 *
			 * @param {*} e
			 * @since 1.7.1
			 */
			open_paypal_connect_modal: function (e) {
				e.preventDefault();
				var self = this;
				var $target = $(e.target);
				var image    = $target.data('modalImage');
				var image_x2 = $target.data('modalImageX2');
				var title    = $target.data('modalTitle');
				var nonce    = $target.data('modalNonce');
				Forminator.Stripe_Popup.open(function () {
					var popup = $(this);
					self.render_paypal_connect_modal_content(popup, nonce, {});
				}, {
					title: title,
					image: image,
					image_x2: image_x2,
				});

				return false;
			},

			render_paypal_connect_modal_content: function (popup, popup_nonce, request_data) {
				var self = this;
				request_data.action      = 'forminator_paypal_settings_modal';
				request_data._ajax_nonce = popup_nonce;

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: request_data
				})
					.done(function (result) {
						if (result && result.success) {
							// Imitate title loading
							popup.find(".sui-box-header h3.sui-box-title").show();

							// Render popup body
							popup.find(".sui-box-body").html(result.data.html);

							// Render popup footer
							var buttons = result.data.buttons;

							// Clear footer from previous buttons
							popup.find(".sui-box-footer").html('');

							// Append buttons
							_.each( buttons, function( button ) {
								popup.find( '.sui-box-footer' ).append( button.markup );
							});

							popup.find(".sui-button").removeClass("sui-button-onload");

							// Handle notifications
							if (!_.isUndefined(result.data.notification) &&
								!_.isUndefined(result.data.notification.type) &&
								!_.isUndefined(result.data.notification.text) &&
								!_.isUndefined(result.data.notification.duration)
							) {

								Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
									.done(function () {
										// Notification opened
									});

								self.update_paypal_page( popup_nonce );
							}
						}
					});
			},

			update_paypal_page: function( nonce ) {
				var new_request = {
					action: 'forminator_paypal_update_page',
					_ajax_nonce: nonce,
				}

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-paypal' ).html( result.data );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();
					});
			},

			connect_paypal: function (e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var nonce        = $target.data('nonce');
				var popup        = this.$el.find('#forminator-stripe-popup');
				var form         = popup.find('form');
				var data         = form.serializeArray();
				var indexedArray = {};

				$.map(data, function (n, i) {
					indexedArray[n['name']] = n['value'];
				});
				indexedArray['connect'] = true;

				this.render_paypal_connect_modal_content(popup, nonce, indexedArray);

				return false;
			},

			disconnect_stripe: function (e) {
				var $target = $(e.target);
				var new_request = {
					action: 'forminator_disconnect_stripe',
					_ajax_nonce: $target.data('nonce'),
				}
				$target.addClass('sui-button-onload');

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-stripe' ).html( result.data.html );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();

						// Handle notifications
						if (!_.isUndefined(result.data.notification) &&
							!_.isUndefined(result.data.notification.type) &&
							!_.isUndefined(result.data.notification.text) &&
							!_.isUndefined(result.data.notification.duration)
						) {

							Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
								.done(function () {
									// Notification opened
								});

						}
					});

			},

			disconnect_paypal: function (e) {
				var $target = $(e.target);
				var new_request = {
					action: 'forminator_disconnect_paypal',
					_ajax_nonce: $target.data('nonce'),
				}
				$target.addClass('sui-button-onload');

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'get',
					data: new_request
				})
					.done(function (result) {
						// Update screen
						jQuery( '#sui-box-paypal' ).html( result.data.html );

						// Re-init SUI events
						Forminator.Utils.sui_delegate_events();

						// Close the popup
						Forminator.Stripe_Popup.close();

						// Handle notifications
						if (!_.isUndefined(result.data.notification) &&
							!_.isUndefined(result.data.notification.type) &&
							!_.isUndefined(result.data.notification.text) &&
							!_.isUndefined(result.data.notification.duration)
						) {

							Forminator.Notification.open(result.data.notification.type, result.data.notification.text, result.data.notification.duration)
								.done(function () {
									// Notification opened
								});

						}
					});

			},
		});

		var SettingsPage = new SettingsPage();

		return SettingsPage;
	});
})(jQuery);

// noinspection JSUnusedGlobalSymbols
var forminator_render_admin_captcha = function () {
	setTimeout( function () {
		var $captcha = jQuery( '.forminator-g-recaptcha' ),
			sitekey = $captcha.data('sitekey'),
			theme = $captcha.data('theme'),
			size = $captcha.data('size')
		;
		window.grecaptcha.render( $captcha[0], {
			sitekey: sitekey,
			theme: theme,
			size: size
		} );
	}, 100 );
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_admin_captcha_v2 = function () {
	setTimeout( function () {
		var $captcha_v2 = jQuery( '.forminator-g-recaptcha-v2' ),
			sitekey_v2 = $captcha_v2.data('sitekey'),
			theme_v2 = $captcha_v2.data('theme'),
			size_v2 = $captcha_v2.data('size')
		;
		window.grecaptcha.render( $captcha_v2[0], {
			sitekey: sitekey_v2,
			theme: theme_v2,
			size: size_v2
		} );
	}, 100 );
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_admin_captcha_v2_invisible = function () {
	setTimeout( function () {
		var $captcha = jQuery( '.forminator-g-recaptcha-v2-invisible' ),
			sitekey = $captcha.data('sitekey'),
			theme = $captcha.data('theme'),
			size = $captcha.data('size')
		;
		window.grecaptcha.render( $captcha[0], {
			sitekey: sitekey,
			theme: theme,
			size: size,
			badge: 'inline'
		} );
	}, 100 );
};

var forminator_render_admin_captcha_v3 = function () {
	setTimeout( function () {
		var $captcha = jQuery( '.forminator-g-recaptcha-v3' ),
			sitekey = $captcha.data('sitekey'),
			theme = $captcha.data('theme'),
			size = $captcha.data('size')
		;
		window.grecaptcha.render( $captcha[0], {
			sitekey: sitekey,
			theme: theme,
			size: size,
			badge: 'inline'
		} );
	}, 100 );
};

var forminator_render_admin_hcaptcha = function () {
	setTimeout( function () {
		var $hcaptcha = jQuery( '.forminator-hcaptcha' ),
			sitekey = $hcaptcha.data( 'sitekey' )
			// theme = $captcha.data('theme'),
			// size = $captcha.data('size')
		;

		hcaptcha.render( $hcaptcha[0], {
			sitekey: sitekey
		} );
	}, 1000 );
};


formintorjs.define('text!tpl/dashboard.html',[],function () { return '<div>\r\n\r\n\t<!-- TEMPLATE: Delete -->\r\n\t<script type="text/template" id="forminator-delete-popup-tpl">\r\n\r\n\t    <div class="sui-box-body sui-spacing-top--0">\r\n\t\t    <p id="forminator-popup__description" class="sui-description" style="margin: 5px 0 0; text-align: center;">{{ content }}</p>\r\n\t    </div>\r\n\r\n\t    <div class="sui-box-footer sui-flatten sui-content-center sui-spacing-top--10">\r\n\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\r\n\t\t\t<form method="post" class="delete-action">\r\n\r\n\t\t\t\t<input type="hidden" name="forminator_action" value="{{ action }}">\r\n\t\t\t\t<input type="hidden" name="id" value="{{ id }}">\r\n\t\t\t\t<input type="hidden" id="forminatorNonce" name="forminatorNonce" value="{{ nonce }}">\r\n\t\t\t\t<input type="hidden" id="forminatorEntryNonce" name="forminatorEntryNonce" value="{{ nonce }}">\r\n\t\t\t\t<input type="hidden" name="_wp_http_referer" value="{{ referrer }}">\r\n\r\n\t\t\t\t<button type="submit" class="sui-button sui-button-ghost sui-button-red popup-confirmation-confirm" style="margin: 0;">\r\n\t\t\t\t\t<span class="sui-icon-trash" aria-hidden="true"></span>\r\n\t\t\t\t\t{{ button }}\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</form>\r\n\r\n\t    </div>\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create New Form (Base Structure) -->\r\n\t<script type="text/template" id="forminator-new-form-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close" data-modal-close>\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.close_label }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg">{{ Forminator.l10n.popup.enter_name }}</h3>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-body sui-content-center sui-spacing-top--10"></div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten">\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button id="forminator-build-your-form" class="sui-button sui-button-blue">\r\n\t\t\t\t\t<span class="sui-loading-text">\r\n\t\t\t\t\t\t<i class="sui-icon-plus" aria-hidden="true"></i>\r\n\t\t\t\t\t\t{{Forminator.l10n.popup.create}}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t\t<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t{[ if( Forminator.Data.showBranding ) { ]}\r\n\t\t<img src="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png"\r\n\t\t     srcset="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png 1x, {{ Forminator.Data.imagesUrl }}/forminator-create-modal@2x.png 2x"\r\n\t\t     class="sui-image sui-image-center"\r\n\t\t     aria-hidden="true"\r\n\t\t     alt="Forminator">\r\n\t\t{[ } ]}\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create New Form (Content) -->\r\n\t<script type="text/template" id="forminator-new-form-content-tpl">\r\n\r\n\t\t<p id="forminator-popup__description" class="sui-description">{{ Forminator.l10n.popup.new_form_desc2 }}</p>\r\n\r\n\t\t<div\r\n\t\t\trole="alert"\r\n\t\t\tid="forminator-template-register-notice"\r\n\t\t\tclass="sui-notice sui-notice-blue"\r\n\t\t\taria-live="assertive"\r\n\t\t>\r\n\r\n\t\t\t<div class="sui-notice-content">\r\n\r\n\t\t\t\t<div class="sui-notice-message">\r\n\r\n\t\t\t\t\t<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>\r\n\r\n\t\t\t\t\t<p style="text-align: left;">{{ Forminator.l10n.popup.registration_notice }}</p>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div\r\n\t\t\trole="alert"\r\n\t\t\tid="forminator-template-login-notice"\r\n\t\t\tclass="sui-notice sui-notice-blue"\r\n\t\t\taria-live="assertive"\r\n\t\t>\r\n\r\n\t\t\t<div class="sui-notice-content">\r\n\r\n\t\t\t\t<div class="sui-notice-message">\r\n\r\n\t\t\t\t\t<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>\r\n\r\n\t\t\t\t\t<p style="text-align: left;">{{ Forminator.l10n.popup.login_notice }}</p>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div id="forminator-form-name-input" class="sui-form-field">\r\n\r\n\t\t\t<label for="forminator-form-name" class="sui-screen-reader-text">{{ Forminator.l10n.popup.input_label }}</label>\r\n\t\t\t<input type="text"\r\n\t\t\t       id="forminator-form-name"\r\n\t\t\t       class="sui-form-control fui-required"\r\n\t\t\t       placeholder="{{Forminator.l10n.popup.new_form_placeholder}}" autofocus>\r\n\t\t\t<span class="sui-error-message" style="display: none;">{{Forminator.l10n.popup.form_name_validation}}</span>\r\n\r\n\t\t</div>\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create Poll -->\r\n\t<script type="text/template" id="forminator-new-poll-content-tpl">\r\n\t\t<p class="sui-description" style="text-align: center;">{{ Forminator.l10n.popup.new_poll_desc2 }}</p>\r\n\r\n\t\t<div id="forminator-form-name-input" class="sui-form-field">\r\n\r\n\t\t\t<label for="forminator-form-name" class="sui-screen-reader-text">{{ Forminator.l10n.popup.input_label }}</label>\r\n\t\t\t<input type="text"\r\n\t\t\t       id="forminator-form-name"\r\n\t\t\t       class="sui-form-control fui-required"\r\n\t\t\t       placeholder="{{Forminator.l10n.popup.new_poll_placeholder}}" autofocus>\r\n\t\t\t<span class="sui-error-message" style="display: none;">{{Forminator.l10n.popup.poll_name_validation}}</span>\r\n\r\n\t\t</div>\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create Quiz, Step 1 -->\r\n\t<script type="text/template" id="forminator-quizzes-popup-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close" data-modal-close>\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: initial; text-overflow: none;">{{ Forminator.l10n.quiz.choose_quiz_title }}</h3>\r\n\r\n\t\t\t<p id="forminator-popup__description" class="sui-description">{{ Forminator.l10n.quiz.choose_quiz_description }}</p>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-body sui-spacing-bottom--10">\r\n\r\n\t\t\t<div id="forminator-form-name-input" class="sui-form-field">\r\n\r\n\t\t\t\t<label for="forminator-form-name" class="sui-label">{{ Forminator.l10n.quiz.quiz_name }}</label>\r\n\r\n\t\t\t\t<input\r\n\t\t\t\t\ttype="text"\r\n\t\t\t\t\tid="forminator-form-name"\r\n\t\t\t\t\tclass="sui-form-control"\r\n\t\t\t\t\tplaceholder="{{Forminator.l10n.popup.new_quiz_placeholder}}"\r\n\t\t\t\t\tautofocus\r\n\t\t\t\t/>\r\n\r\n\t\t\t\t<div\r\n\t\t\t\t\trole="alert"\r\n\t\t\t\t\tid="sui-quiz-name-error"\r\n\t\t\t\t\tclass="sui-notice sui-notice-red forminator-validate-answers"\r\n\t\t\t\t\taria-live="assertive"\r\n\t\t\t\t>\r\n\r\n\t\t\t\t\t<div class="sui-notice-content">\r\n\r\n\t\t\t\t\t\t<div class="sui-notice-message">\r\n\r\n\t\t\t\t\t\t\t<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>\r\n\r\n\t\t\t\t\t\t\t<p>{{Forminator.l10n.popup.quiz_name_validation}}</p>\r\n\r\n\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t</div>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t\t<label class="sui-label">{{ Forminator.l10n.quiz.quiz_type }}</label>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-selectors" style="margin: 0;">\r\n\r\n\t\t\t<ul>\r\n\r\n\t\t\t\t<li><label for="forminator-new-quiz--knowledge" class="sui-box-selector">\r\n\t\t\t\t\t<input\r\n\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\tname="forminator-new-quiz"\r\n\t\t\t\t\t\tid="forminator-new-quiz--knowledge"\r\n\t\t\t\t\t\tclass="forminator-new-quiz-type"\r\n\t\t\t\t\t\tvalue="knowledge"\r\n\t\t\t\t\t\tchecked="checked"\r\n\t\t\t\t\t/>\r\n\t\t\t\t\t<span>\r\n\t\t\t\t\t\t<i class="sui-icon-academy" aria-hidden="true"></i>\r\n\t\t\t\t\t\t{{ Forminator.l10n.quiz.knowledge_label }}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t\t<span>{{ Forminator.l10n.quiz.knowledge_description }}</span>\r\n\t\t\t\t</label></li>\r\n\r\n\t\t\t\t<li><label for="forminator-new-quiz--nowrong" class="sui-box-selector">\r\n\t\t\t\t\t<input\r\n\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\tname="forminator-new-quiz"\r\n\t\t\t\t\t\tid="forminator-new-quiz--nowrong"\r\n\t\t\t\t\t\tclass="forminator-new-quiz-type"\r\n\t\t\t\t\t\tvalue="nowrong"\r\n\t\t\t\t\t/>\r\n\t\t\t\t\t<span>\r\n\t\t\t\t\t\t<i class="sui-icon-community-people" aria-hidden="true"></i>\r\n\t\t\t\t\t\t{{ Forminator.l10n.quiz.nowrong_label }}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t\t<span>{{ Forminator.l10n.quiz.nowrong_description }}</span>\r\n\t\t\t\t</label></li>\r\n\r\n\t\t\t</ul>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-spacing-top--30">\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button class="sui-button select-quiz-template">\r\n\t\t\t\t\t<span class="sui-loading-text">{{ Forminator.l10n.quiz.continue_button }}</span>\r\n\t\t\t\t\t<i class="sui-icon-load sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t{[ if( Forminator.Data.showBranding ) { ]}\r\n\t\t<img src="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png"\r\n\t\t     srcset="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png 1x, {{ Forminator.Data.imagesUrl }}/forminator-create-modal@2x.png 2x"\r\n\t\t     class="sui-image sui-image-center"\r\n\t\t     aria-hidden="true"\r\n\t\t     alt="Forminator">\r\n\t\t{[ } ]}\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create Quiz, Step 2 (Pagination) -->\r\n\t<script type="text/template" id="forminator-new-quiz-pagination-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--left forminator-popup-back">\r\n\t\t\t\t<span class="sui-icon-chevron-left sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.go_back }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close">\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.close_label }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: initial;">{{ Forminator.l10n.quiz.quiz_pagination }}</h3>\r\n\r\n\t\t\t<p id="forminator-popup__description" class="sui-description">\r\n\t\t\t\t{{ Forminator.l10n.quiz.quiz_pagination_descr }}<br>\r\n\t\t\t\t{{ Forminator.l10n.quiz.quiz_pagination_descr2 }}\r\n\t\t\t</p>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-body sui-spacing-bottom--10">\r\n\r\n\t\t\t<label class="sui-label">{{ Forminator.l10n.quiz.presentation }}</label>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-selectors" style="margin: 0;">\r\n\r\n\t\t\t<ul>\r\n\r\n\t\t\t\t<li><label class="sui-box-selector">\r\n\t\t\t\t\t<input\r\n\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\tname="forminator-quiz-pagination"\r\n\t\t\t\t\t\tvalue=""\r\n\t\t\t\t\t\tchecked="checked"\r\n\t\t\t\t\t/>\r\n\t\t\t\t\t<span>\r\n\t\t\t\t\t\t{{ Forminator.l10n.quiz.no_pagination }}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t</label></li>\r\n\r\n\t\t\t\t<li><label class="sui-box-selector">\r\n\t\t\t\t\t<input\r\n\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\tname="forminator-quiz-pagination"\r\n\t\t\t\t\t\tvalue="1"\r\n\t\t\t\t\t/>\r\n\t\t\t\t\t<span>\r\n\t\t\t\t\t\t{{ Forminator.l10n.quiz.paginate_quiz }}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t</label></li>\r\n\r\n\t\t\t</ul>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-spacing-top--30">\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button class="sui-button select-quiz-pagination">\r\n\t\t\t\t\t<span class="sui-loading-text">{{ Forminator.l10n.quiz.continue_button }}</span>\r\n\t\t\t\t\t<i class="sui-icon-load sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t{[ if( Forminator.Data.showBranding ) { ]}\r\n\t\t<img src="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png"\r\n\t\t     srcset="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png 1x, {{ Forminator.Data.imagesUrl }}/forminator-create-modal@2x.png 2x"\r\n\t\t     class="sui-image sui-image-center"\r\n\t\t     aria-hidden="true"\r\n\t\t     alt="Forminator">\r\n\t\t{[ } ]}\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create Quiz, Step 3A (Leads Base Structure) -->\r\n\t<script type="text/template" id="forminator-new-quiz-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--left forminator-popup-back">\r\n\t\t\t\t<span class="sui-icon-chevron-left sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.go_back }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close forminator-cancel-create-form">\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.close_label }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg">{{ Forminator.l10n.quiz.collect_leads }}</h3>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--10"></div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-separated">\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button id="forminator-build-your-form" class="sui-button sui-button-blue">\r\n\t\t\t\t\t<span class="sui-loading-text">\r\n\t\t\t\t\t\t<i class="sui-icon-plus" aria-hidden="true"></i> {{ Forminator.l10n.quiz.create_quiz }}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t\t<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t{[ if( Forminator.Data.showBranding ) { ]}\r\n\t\t<img src="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png"\r\n\t\t     srcset="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png 1x, {{ Forminator.Data.imagesUrl }}/forminator-create-modal@2x.png 2x"\r\n\t\t     class="sui-image sui-image-center"\r\n\t\t     aria-hidden="true"\r\n\t\t     alt="Forminator">\r\n\t\t{[ } ]}\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Create Quiz, Step 3B (Leads Content) -->\r\n\t<script type="text/template" id="forminator-new-quiz-content-tpl">\r\n\r\n\t\t<p class="sui-description" style="text-align: center;">{{ Forminator.l10n.popup.new_quiz_desc2 }}\r\n\t\t\t{[ if( Forminator.Data.showDocLink ) { ]}\r\n\t\t\t\t<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/?utm_source=forminator&utm_medium=plugin&utm_campaign=capture_leads_learnmore_link#leads" target="_blank">\r\n\t\t\t\t\t{{ Forminator.l10n.popup.learn_more }}\r\n\t\t\t\t</a>\r\n\t\t\t{[ } ]}\r\n\t\t</p>\r\n\r\n\t\t<div class="sui-form-field">\r\n\r\n\t\t\t<label for="forminator-new-quiz-leads" class="sui-toggle fui-highlighted-toggle">\r\n\r\n\t\t\t\t<input\r\n\t\t\t\t\ttype="checkbox"\r\n\t\t\t\t\tid="forminator-new-quiz-leads"\r\n\t\t\t\t\taria-labelledby="forminator-new-quiz-leads-label"\r\n\t\t\t\t/>\r\n\r\n\t\t\t\t<span class="sui-toggle-slider" aria-hidden="true"></span>\r\n\r\n\t\t\t\t<span id="forminator-new-quiz-leads-label" class="sui-toggle-label">{{ Forminator.l10n.quiz.quiz_leads_toggle }}</span>\r\n\r\n\t\t\t</label>\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="alert"\r\n\t\t\t\tid="sui-quiz-leads-description"\r\n\t\t\t\tclass="sui-notice sui-notice-blue"\r\n\t\t\t\taria-live="assertive"\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-notice-content">\r\n\r\n\t\t\t\t\t<div class="sui-notice-message">\r\n\r\n\t\t\t\t\t\t<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>\r\n\r\n\t\t\t\t\t\t<p>{{ Forminator.l10n.quiz.quiz_leads_desc }}</p>\r\n\r\n\t\t\t\t\t</div>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Export Schedule (on Submissions page) -->\r\n\t<script type="text/template" id="forminator-exports-schedule-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body">\r\n\r\n\t\t\t<div class="sui-box-settings-row">\r\n\r\n\t\t\t\t<div class="sui-box-settings-col-2">\r\n\r\n\t\t\t\t\t<span class="sui-settings-label sui-dark">{{ Forminator.l10n.popup.manual_exports }}</span>\r\n\t\t\t\t\t<span class="sui-description">{{ Forminator.l10n.popup.manual_description }}</span>\r\n\r\n\t\t\t\t\t<form method="post" style="margin-top: 10px;">\r\n\r\n\t\t\t\t\t\t<input type="hidden" name="forminator_export" value="1" />\r\n\t\t\t\t\t\t<input type="hidden" name="form_id" value="{{ Forminator.l10n.exporter.form_id }}" />\r\n\t\t\t\t\t\t<input type="hidden" name="form_type" value="{{ Forminator.l10n.exporter.form_type }}" />\r\n\t\t\t\t\t\t<input type="hidden" name="_forminator_nonce" value="{{ Forminator.l10n.exporter.export_nonce }}" />\r\n\r\n\t\t\t\t\t\t<button class="sui-button sui-button-ghost">{{ Forminator.l10n.popup.download_csv }}</button>\r\n\r\n\t\t\t\t\t\t{[ if( \'cform\' === Forminator.l10n.exporter.form_type || \'quiz\' === Forminator.l10n.exporter.form_type ) { ]}\r\n\t\t\t\t\t\t\t<label for="apply-submission-filter" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-top: 20px;">\r\n\t\t\t\t\t\t\t\t<input type="checkbox" name="submission-filter" id="apply-submission-filter" value="yes" />\r\n\t\t\t\t\t\t\t\t<span aria-hidden="true"></span>\r\n\t\t\t\t\t\t\t\t<span>{{ Forminator.l10n.popup.apply_submission_filter }}</span>\r\n\t\t\t\t\t\t\t</label>\r\n\t\t\t\t\t\t{[ } ]}\r\n\r\n\t\t\t\t\t</form>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t\t<form method="post" class="sui-box-settings-row schedule-action">\r\n\r\n\t\t\t\t<div class="sui-box-settings-col-2">\r\n\r\n\t\t\t\t\t<span class="sui-settings-label sui-dark">{{ Forminator.l10n.popup.scheduled_exports }}</span>\r\n\t\t\t\t\t<span class="sui-description">{{ Forminator.l10n.popup.scheduled_description }}</span>\r\n\r\n\t\t\t\t\t<div class="sui-side-tabs" style="margin-top: 10px;">\r\n\r\n\t\t\t\t\t\t<div class="sui-tabs-menu tab-labels">\r\n\r\n\t\t\t\t\t\t\t<label for="forminator-disable-scheduled-exports" class="sui-tab-item tab-label-disable">\r\n\t\t\t\t\t\t\t\t<input\r\n\t\t\t\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\t\t\t\tname="enabled"\r\n\t\t\t\t\t\t\t\t\tvalue="false"\r\n\t\t\t\t\t\t\t\t\tid="forminator-disable-scheduled-exports"\r\n\t\t\t\t\t\t\t\t/>\r\n\t\t\t\t\t\t\t\t{{ Forminator.l10n.popup.disable }}\r\n\t\t\t\t\t\t\t</label>\r\n\r\n\t\t\t\t\t\t\t<label for="forminator-enable-scheduled-exports" class="sui-tab-item tab-label-enable">\r\n\t\t\t\t\t\t\t\t<input\r\n\t\t\t\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\t\t\t\tname="enabled"\r\n\t\t\t\t\t\t\t\t\tvalue="true"\r\n\t\t\t\t\t\t\t\t\tid="forminator-enable-scheduled-exports"\r\n\t\t\t\t\t\t\t\t/>\r\n\t\t\t\t\t\t\t\t{{ Forminator.l10n.popup.enable }}\r\n\t\t\t\t\t\t\t</label>\r\n\r\n\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t<div class="sui-tabs-content schedule-enabled">\r\n\r\n\t\t\t\t\t\t\t<div id="forminator-export-schedule-timeframe" class="sui-tab-content sui-tab-boxed active">\r\n\r\n\t\t\t\t\t\t\t\t<div class="sui-row">\r\n\r\n\t\t\t\t\t\t\t\t\t<div class="sui-col-md-6">\r\n\r\n\t\t\t\t\t\t\t\t\t\t<div class="sui-form-field">\r\n\r\n\t\t\t\t\t\t\t\t\t\t\t<label class="sui-label">{{ Forminator.l10n.popup.frequency }}</label>\r\n\r\n\t\t\t\t\t\t\t\t\t\t\t<select name="interval" class="sui-select">\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="daily">{{ Forminator.l10n.popup.daily }}</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="weekly">{{ Forminator.l10n.popup.weekly }}</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="monthly">{{ Forminator.l10n.popup.monthly }}</option>\r\n\t\t\t\t\t\t\t\t\t\t\t</select>\r\n\r\n\t\t\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t\t<div class="sui-col-md-6">\r\n\r\n\t\t\t\t\t\t\t\t\t\t<div class="sui-form-field">\r\n\r\n\t\t\t\t\t\t\t\t\t\t\t<label class="sui-label">{{ Forminator.l10n.popup.day_time }}</label>\r\n\r\n\t\t\t\t\t\t\t\t\t\t\t<select name="hour" class="sui-select">\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="00:00">12:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="01:00">01:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="02:00">02:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="03:00">03:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="04:00">04:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="05:00">05:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="06:00">06:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="07:00">07:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="08:00">08:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="09:00">09:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="10:00">10:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="11:00">11:00 AM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="12:00">12:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="13:00">01:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="14:00">02:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="15:00">03:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="16:00">04:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="17:00">05:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="18:00">06:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="19:00">07:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="20:00">08:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="21:00">09:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="22:00">10:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<option value="23:00">11:00 PM</option>\r\n\t\t\t\t\t\t\t\t\t\t\t</select>\r\n\r\n\t\t\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t<div class="sui-form-field">\r\n\r\n\t\t\t\t\t\t\t\t\t<label class="sui-label">{{ Forminator.l10n.popup.week_day }}</label>\r\n\r\n\t\t\t\t\t\t\t\t\t<select name="day" class="sui-select">\r\n\t\t\t\t\t\t\t\t\t\t<option value="mon">{{ Forminator.l10n.popup.monday }}</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="tue">{{ Forminator.l10n.popup.tuesday }}</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="wed">{{ Forminator.l10n.popup.wednesday }}</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="thu">{{ Forminator.l10n.popup.thursday }}</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="fri">{{ Forminator.l10n.popup.friday }}</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="sat">{{ Forminator.l10n.popup.saturday }}</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="sun">{{ Forminator.l10n.popup.sunday }}</option>\r\n\t\t\t\t\t\t\t\t\t</select>\r\n\r\n\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t<div class="sui-form-field">\r\n\r\n\t\t\t\t\t\t\t\t\t<label class="sui-label">{{ Forminator.l10n.popup.day_month }}</label>\r\n\r\n\t\t\t\t\t\t\t\t\t<select name="month_day" class="sui-select">\r\n\t\t\t\t\t\t\t\t\t\t<option value="1">1</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="2">2</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="3">3</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="4">4</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="5">5</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="6">6</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="7">7</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="8">8</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="9">9</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="10">10</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="11">11</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="12">12</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="13">13</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="14">14</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="15">15</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="16">16</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="17">17</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="18">18</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="19">19</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="20">20</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="21">21</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="22">22</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="23">23</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="24">24</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="25">25</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="26">26</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="27">27</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="28">28</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="29">29</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="30">30</option>\r\n\t\t\t\t\t\t\t\t\t\t<option value="31">31</option>\r\n\t\t\t\t\t\t\t\t\t</select>\r\n\r\n\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t<div id="forminator-export-schedule-email" class="sui-form-field" style="margin-bottom: 20px;">\r\n\r\n\t\t\t\t\t\t\t\t\t<label class="sui-label">{{ Forminator.l10n.popup.email_to }}</label>\r\n\r\n\t\t\t\t\t\t\t\t\t<select name="email[]" class="sui-select fui-multi-select wpmudev-select" multiple="multiple">\r\n\t\t\t\t\t\t\t\t\t\t{[ _.each( Forminator.l10n.exporter.email, function ( email ) { ]}\r\n\t\t\t\t\t\t\t\t\t\t<option value="{{ email }}" selected="selected">{{ email }}</option>\r\n\t\t\t\t\t\t\t\t\t\t{[ }) ]}\r\n\t\t\t\t\t\t\t\t\t</select>\r\n\r\n\t\t\t\t\t\t\t\t\t<input type="hidden" name="form_id" value="{{ Forminator.l10n.exporter.form_id }}"/>\r\n\t\t\t\t\t\t\t\t\t<input type="hidden" name="form_type" value="{{ Forminator.l10n.exporter.form_type }}"/>\r\n\t\t\t\t\t\t\t\t\t<input type="hidden" name="action" value="forminator_export_entries"/>\r\n\t\t\t\t\t\t\t\t\t<input type="hidden" name="_forminator_nonce" value="{{ Forminator.l10n.exporter.export_nonce }}"/>\r\n\r\n\t\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t\t\t<label for="forminator-send-if-new-exports" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked">\r\n\t\t\t\t\t\t\t\t\t<input\r\n\t\t\t\t\t\t\t\t\t\ttype="checkbox"\r\n\t\t\t\t\t\t\t\t\t\tname="if_new"\r\n\t\t\t\t\t\t\t\t\t\tvalue="true"\r\n\t\t\t\t\t\t\t\t\t\tid="forminator-send-if-new-exports"\r\n\t\t\t\t\t\t\t\t\t\tclass="forminator-field-singular"\r\n\t\t\t\t\t\t\t\t\t/>\r\n\t\t\t\t\t\t\t\t\t<span aria-hidden="true"></span>\r\n\t\t\t\t\t\t\t\t\t<span class="sui-description">{{ Forminator.l10n.popup.scheduled_export_if_new }}</span>\r\n\t\t\t\t\t\t\t\t</label>\r\n\r\n\t\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t\t</div>\r\n\r\n\t\t\t\t\t</div>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t</form>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer">\r\n\r\n\t\t\t<button class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide="forminator-popup">{{ Forminator.l10n.popup.cancel }}</button>\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button type="submit" class="sui-button wpmudev-action-done">{{ Forminator.l10n.popup.save }}</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Reset Plugin (on Settings page) -->\r\n\t<script type="text/template" id="forminator-reset-plugin-settings-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--10">\r\n\t\t\t<p class="sui-description" style="text-align: center;">{{ content }}</p>\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-center">\r\n\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\r\n\t\t\t<form method="post" class="delete-action">\r\n\t\t\t\t<input type="hidden" name="forminator_action" value="reset_plugin_settings">\r\n\t\t\t\t<input type="hidden" id="forminatorNonce" name="forminatorNonce" value="{{ nonce }}">\r\n\t\t\t\t<input type="hidden" name="_wp_http_referer" value="{{ referrer }}&forminator_notice=settings_reset">\r\n\t\t\t\t<button type="submit" class="sui-button sui-button-ghost sui-button-red popup-confirmation-confirm">\r\n\t\t\t\t\t<span class="sui-loading-text" aria-hidden="true">\r\n\t\t\t\t\t\t<i class="sui-icon-refresh" aria-hidden="true"></i>\r\n\t\t\t\t\t\t{{ Forminator.l10n.popup.reset }}\r\n\t\t\t\t\t</span>\r\n\t\t\t\t\t<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</button>\r\n\t\t\t</form>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Disconnect PayPal (on Settings page) -->\r\n\t<script type="text/template" id="forminator-disconnect-paypal-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--10">\r\n\t\t\t<p class="sui-description" style="text-align: center;">{{ content }}</p>\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-center">\r\n\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\r\n\t\t\t<button type="submit" class="disconnect_paypal sui-button sui-button-ghost sui-button-red popup-confirmation-confirm" data-nonce="{{ nonce }}" data-action="disconnect_paypal">\r\n\t\t\t\t{{ Forminator.l10n.popup.disconnect }}\r\n\t\t\t</button>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- TEMPLATE: Disconnect Stripe (on Settings page) -->\r\n\t<script type="text/template" id="forminator-disconnect-stripe-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--10">\r\n\t\t\t<p class="sui-description" style="text-align: center;">{{ content }}</p>\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-center">\r\n\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\r\n\t\t\t<button type="submit" class="disconnect_stripe sui-button sui-button-ghost sui-button-red popup-confirmation-confirm" data-nonce="{{ nonce }}" data-action="disconnect_stripe">\r\n\t\t\t\t<i class="sui-icon-refresh" aria-hidden="true"></i>\r\n\t\t\t\t{{ Forminator.l10n.popup.disconnect }}\r\n\t\t\t</button>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n    <script type="text/template" id="forminator-login-popup-tpl">\r\n\r\n        <div class="wpmudev-row">\r\n\r\n            <div class="wpmudev-col col-12 col-sm-6">\r\n\r\n                <a class="wpmudev-popup--logreg" href="{{ loginUrl }}">\r\n\r\n                    <div class="wpmudev-logreg--header">\r\n\r\n                        <span class="wpdui-icon wpdui-icon-key"></span>\r\n\r\n                        <h3 class="wpmudev-logreg--title">{{ Forminator.l10n.login.login_label }}</h3>\r\n\r\n                    </div>\r\n\r\n                    <div class="wpmudev-logreg--section">\r\n\r\n                        <p class="wpmudev-logreg--description">{{ Forminator.l10n.login.login_description }}</p>\r\n\r\n                        <div class="wpmudev-logreg--image">\r\n                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"\r\n                                 width="110" height="140" viewBox="0 0 110 140" preserveAspectRatio="none"\r\n                                 class="wpmudev-svg-login">\r\n                                <defs>\r\n                                    <path id="b" d="M0 40h100v94H0z"/>\r\n                                    <filter id="a" width="116%" height="117%" x="-8%" y="-7.4%"\r\n                                            filterUnits="objectBoundingBox">\r\n                                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"/>\r\n                                        <feGaussianBlur in="shadowOffsetOuter1" result="shadowBlurOuter1"\r\n                                                        stdDeviation="2.5"/>\r\n                                        <feColorMatrix in="shadowBlurOuter1"\r\n                                                       values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"/>\r\n                                    </filter>\r\n                                    <path id="c" d="M10 112h6v6h-6z"/>\r\n                                    <path id="d" d="M10 90h80v15H10z"/>\r\n                                    <path id="e" d="M10 60h80v15H10z"/>\r\n                                </defs>\r\n                                <g fill="none" fill-rule="evenodd" transform="translate(5)">\r\n                                    <use fill="#000" filter="url(#a)" xlink:href="#b"/>\r\n                                    <use fill="#FFF" xlink:href="#b"/>\r\n                                    <rect width="22" height="12" x="68" y="112" fill="#0472AC" rx="3"/>\r\n                                    <path fill="#808285" d="M19 114h26v2H19z"/>\r\n                                    <use fill="#FBFBFB" xlink:href="#c"/>\r\n                                    <path stroke="#E1E1E1" d="M10.5 112.5h5v5h-5z"/>\r\n                                    <use fill="#FBFBFB" xlink:href="#d"/>\r\n                                    <path stroke="#E1E1E1" d="M10.5 90.5h79v14h-79z"/>\r\n                                    <path fill="#808285" d="M10 85h26v2H10z"/>\r\n                                    <use fill="#FBFBFB" xlink:href="#e"/>\r\n                                    <path stroke="#E1E1E1" d="M10.5 60.5h79v14h-79z"/>\r\n                                    <path fill="#808285" d="M10 55h50v2H10z"/>\r\n                                    <circle cx="50" cy="15" r="15" fill="#0272AC"/>\r\n                                </g>\r\n                            </svg>\r\n                        </div>\r\n\r\n                    </div>\r\n\r\n                </a>\r\n\r\n            </div>\r\n\r\n            <div class="wpmudev-col col-12 col-sm-6">\r\n\r\n                <a class="wpmudev-popup--logreg" href="{{ registerUrl }}">\r\n\r\n                    <div class="wpmudev-logreg--header">\r\n\r\n                        <span class="wpdui-icon wpdui-icon-profile-female"></span>\r\n\r\n                        <h3 class="wpmudev-logreg--title">{{ Forminator.l10n.login.registration_label }}</h3>\r\n\r\n                    </div>\r\n\r\n                    <div class="wpmudev-logreg--section">\r\n\r\n                        <p class="wpmudev-logreg--description">{{ Forminator.l10n.login.registration_description }}</p>\r\n\r\n                        <div class="wpmudev-logreg--image">\r\n                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"\r\n                                 width="183" height="97" viewBox="0 0 183 97" preserveAspectRatio="none"\r\n                                 class="wpmudev-svg-registration">\r\n                                <defs>\r\n                                    <path id="a" d="M0 58h183v15H0z"/>\r\n                                    <path id="b" d="M0 23h183v15H0z"/>\r\n                                </defs>\r\n                                <g fill="none" fill-rule="evenodd">\r\n                                    <rect width="50" height="14" y="83" fill="#353535" rx="3"/>\r\n                                    <path fill="#FBFBFB" stroke="#E1E1E1" d="M.5 58.5h182v14H.5z"/>\r\n                                    <path fill="#808285" d="M0 51h40v2H0z"/>\r\n                                    <path fill="#FBFBFB" stroke="#E1E1E1" d="M.5 23.5h182v14H.5z"/>\r\n                                    <path fill="#808285" d="M0 16h40v2H0z"/>\r\n                                    <path fill="#353535" d="M0 0h120v6H0z"/>\r\n                                </g>\r\n                            </svg>\r\n                        </div>\r\n\r\n                    </div>\r\n\r\n                </a>\r\n\r\n            </div>\r\n\r\n        </div>\r\n    </script>\r\n\r\n\t<script type="text/template" id="forminator-form-popup-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close">\r\n\t\t\t\t<span class="sui-icon-close" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg">{{ Forminator.l10n.form.form_template_title }}</h3>\r\n\r\n\t\t\t<p id="forminator-popup__description" class="sui-description">{{ Forminator.l10n.form.form_template_description }}</p>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-selectors sui-box-selectors-col-2">\r\n\r\n\t\t\t<ul>\r\n\r\n\t\t\t\t{[ _.each(templates, function(template){ ]}\r\n\r\n\t\t\t\t\t{[ if( template.options.id !== \'leads\' ) { ]}\r\n\r\n\t\t\t\t\t\t<li>\r\n\r\n\t\t\t\t\t\t\t<label for="forminator-new-quiz--{{ template.options.id }}" class="sui-box-selector">\r\n\r\n\t\t\t\t\t\t\t\t<input\r\n\t\t\t\t\t\t\t\t\t\ttype="radio"\r\n\t\t\t\t\t\t\t\t\t\tname="forminator-form-template"\r\n\t\t\t\t\t\t\t\t\t\tid="forminator-new-quiz--{{ template.options.id }}"\r\n\t\t\t\t\t\t\t\t\t\tclass="forminator-new-form-type"\r\n\t\t\t\t\t\t\t\t\t\tvalue="{{ template.options.id }}"\r\n\t\t\t\t\t\t\t\t\t\t{[ if( template.options.id === \'blank\' ) { ]}\r\n\t\t\t\t\t\t\t\t\t\tchecked="checked"\r\n\t\t\t\t\t\t\t\t\t\t{[ } ]}\r\n\t\t\t\t\t\t\t\t/>\r\n\r\n\t\t\t\t\t\t\t\t<span>\r\n\t\t\t\t\t\t\t\t\t<i class="sui-icon-{{template.options.icon}}" aria-hidden="true"></i>\r\n\t\t\t\t\t\t\t\t\t{{ template.options.name }}\r\n\t\t\t\t\t\t\t\t</span>\r\n\r\n\t\t\t\t\t\t\t</label>\r\n\r\n\t\t\t\t\t\t</li>\r\n\r\n\t\t\t\t\t{[ } ]}\r\n\r\n\t\t\t\t{[ }); ]}\r\n\r\n\t\t\t</ul>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten">\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button class="sui-button select-quiz-template">\r\n\t\t\t\t\t<span class="sui-loading-text">{{ Forminator.l10n.quiz.continue_button }}</span>\r\n\t\t\t\t\t<i class="sui-icon-load sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t{[ if( Forminator.Data.showBranding ) { ]}\r\n\t\t<img src="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png"\r\n\t\t     srcset="{{ Forminator.Data.imagesUrl }}/forminator-create-modal.png 1x, {{ Forminator.Data.imagesUrl }}/forminator-create-modal@2x.png 2x"\r\n\t\t     class="sui-image sui-image-center"\r\n\t\t     aria-hidden="true"\r\n\t\t     alt="Forminator">\r\n\t\t{[ } ]}\r\n\r\n\t</script>\r\n\r\n    <script type="text/template" id="forminator-new-form-popup-tpl">\r\n\r\n        <div class="wpmudev-box--congrats">\r\n\r\n            <div class="wpmudev-congrats--image"></div>\r\n\r\n            <div class="wpmudev-congrats--message">\r\n\r\n                <p><strong>{{ title }}</strong> {{ Forminator.l10n.popup.is_ready }}<br/>\r\n\t\t\t\t\t{{ Forminator.l10n.popup.new_form_desc }}</p>\r\n\r\n            </div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n    <script type="text/template" id="forminator-confirmation-popup-tpl">\r\n\r\n\t    <div class="sui-box-body sui-content-center">\r\n\t\t    <p>{{ confirmation_message }}</p>\r\n\t    </div>\r\n\r\n        <div class="sui-box-footer sui-flatten sui-content-center">\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost popup-confirmation-cancel">{{ Forminator.l10n.popup.cancel }}</button>\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost sui-button-red popup-confirmation-confirm">\r\n\t\t\t\t<span class="sui-loading-text">{{ Forminator.l10n.popup.delete }}</span>\r\n\t\t\t\t<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>\r\n\t\t\t</button>\r\n        </div>\r\n\r\n    </script>\r\n\r\n\t<script type="text/template" id="forminator-delete-poll-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body">\r\n\t\t\t<span class="sui-description">{{ content }}</span>\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer">\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\t\t\t<button type="submit" class="delete-poll-submission sui-button sui-button-ghost sui-button-red popup-confirmation-confirm" data-nonce="{{ nonce }}" data-id="{{ id }}" data-action="delete_poll_submissions">\r\n\t\t\t\t{{ Forminator.l10n.popup.delete }}\r\n\t\t\t</button>\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="forminator-approve-user-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body">\r\n\t\t\t<span class="sui-description">{{ content }}</span>\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer">\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\t\t\t<form method="post" class="form-approve-user">\r\n\t\t\t\t<input type="hidden" name="forminator_action" value="approve_user">\r\n\t\t\t\t<input type="hidden" name="id" value="{{ id }}">\r\n\t\t\t\t<input type="hidden" name="activationKey" value="{{ activationKey }}">\r\n\t\t\t\t<input type="hidden" id="forminatorNonce" name="forminatorNonce" value="{{ nonce }}">\r\n\t\t\t\t<input type="hidden" id="forminatorEntryNonce" name="forminatorEntryNonce" value="{{ nonce }}">\r\n\t\t\t\t<input type="hidden" name="_wp_http_referer" value="{{ referrer }}">\r\n\t\t\t\t<button type="submit" class="sui-button approve-user popup-confirmation-confirm">\r\n\t\t\t\t\t{{ Forminator.l10n.popup.approve_user }}\r\n\t\t\t\t</button>\r\n\t\t\t</form>\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="forminator-delete-unconfirmed-user-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body">\r\n\t\t\t<span class="sui-description">{{ content }}</span>\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer">\r\n\t\t\t<button type="button" class="sui-button sui-button-ghost forminator-popup-cancel" data-a11y-dialog-hide>{{ Forminator.l10n.popup.cancel }}</button>\r\n\t\t\t<form method="post" class="form-delete-unconfirmed-user">\r\n\t\t\t\t<input type="hidden" name="forminatorAction" value="delete-unconfirmed-user">\r\n\t\t\t\t<input type="hidden" name="formId" value="{{ formId }}">\r\n\t\t\t\t<input type="hidden" name="entryId" value="{{ entryId }}">\r\n\t\t\t\t<input type="hidden" name="activationKey" value="{{ activationKey }}">\r\n\t\t\t\t<input type="hidden" id="forminatorNonceDeleteUnconfirmedUser" name="forminatorNonceDeleteUnconfirmedUser" value="{{ nonce }}">\r\n\t\t\t\t<input type="hidden" name="_wp_http_referer" value="{{ referrer }}">\r\n\t\t\t\t<button type="submit" class="sui-button sui-button-ghost sui-button-red delete-unconfirmed-user popup-confirmation-confirm">\r\n\t\t\t\t\t<i class="sui-icon-trash" aria-hidden="true"></i>\r\n\t\t\t\t\t{{ Forminator.l10n.popup.delete }}\r\n\t\t\t\t</button>\r\n\t\t\t</form>\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="forminator-addons-action-popup-tpl">\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--0">\r\n\r\n\t\t\t{[ if( forms.length <= 0 ) { ]}\r\n\r\n\t\t\t\t<p id="forminator-popup__description" class="sui-description" style="text-align: center;">{{ Forminator.l10n.popup.deactivateContent }}</p>\r\n\r\n\t\t\t{[ } else { ]}\r\n\r\n\t\t\t\t<p id="forminator-popup__description" class="sui-description" style="text-align: center;">{{ content }}</p>\r\n\r\n\t\t\t\t<div class="form-list">\r\n\r\n\t\t\t\t\t<h4 class="sui-table-title" style="margin: 0 0 5px;">{{ Forminator.l10n.popup.forms }}</h4>\r\n\r\n\t\t\t\t\t<table class="sui-table" style="margin: 0;">\r\n\t\t\t\t\t\t<tbody>\r\n\t\t\t\t\t\t{[ _.each( forms, function( value, key ){ ]}\r\n\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t<td class="sui-table-item-title">\r\n\t\t\t\t\t\t\t\t\t<span class="sui-icon-clipboard-notes" aria-hidden="true"></span>\r\n\t\t\t\t\t\t\t\t\t{{ value }}\r\n\t\t\t\t\t\t\t\t</td>\r\n\t\t\t\t\t\t\t\t<td width="73" style="text-align: right;">\r\n\t\t\t\t\t\t\t\t\t<a href="{{ forminatorData.formEditUrl + \'&id=\' + key }}" title="{{ value }}" class="sui-button-icon">\r\n\t\t\t\t\t\t\t\t\t\t<span class="sui-icon-pencil" aria-hidden="true"></span>\r\n\t\t\t\t\t\t\t\t\t</a>\r\n\t\t\t\t\t\t\t\t</td>\r\n\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t{[ }) ]}\r\n\t\t\t\t\t\t</tbody>\r\n\t\t\t\t\t</table>\r\n\r\n\t\t\t\t</div>\r\n\r\n\t\t\t{[ } ]}\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-separated">\r\n\r\n\t\t\t<button type="button" class="sui-button forminator-popup-close" data-a11y-dialog-hide>\r\n\t\t\t\t{{ Forminator.l10n.popup.cancel }}\r\n\t\t\t</button>\r\n\r\n\t\t\t{[ if( forms.length <= 0 ) { ]}\r\n\t\t\t\t<button class="sui-button addons-actions" data-addon="{{ id }}" data-nonce="{{ nonce }}" data-popup="true" data-action="addons-deactivate">\r\n\t\t\t\t\t{{ Forminator.l10n.popup.deactivate }}\r\n\t\t\t\t</button>\r\n\t\t\t{[ } else { ]}\r\n\t\t\t\t<button class="sui-button sui-button-ghost sui-button-red addons-actions" data-addon="{{ id }}" data-nonce="{{ nonce }}" data-popup="true" data-action="addons-deactivate">\r\n\t\t\t\t\t{{ Forminator.l10n.popup.deactivateAnyway }}\r\n\t\t\t\t</button>\r\n\t\t\t{[ } ]}\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="forminator-apply-appearance-preset-tpl">\r\n\r\n\t\t<div class="sui-box-body">\r\n\t\t\t<span class="sui-description" style="text-align: center; margin: -15px 0 30px;">{{ description }}</span>\r\n\r\n\t\t\t<div class="sui-form-field" style="margin-bottom: 10px;">\r\n\t\t\t\t{{ selectbox }}\r\n\t\t\t</div>\r\n\r\n\t\t\t<div class="sui-notice" style="margin-top: 10px;">\r\n\t\t\t\t<div class="sui-notice-content">\r\n\t\t\t\t\t<div class="sui-notice-message">\r\n\t\t\t\t\t\t<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>\r\n\t\t\t\t\t\t<p>{{ notice }}</p>\r\n\t\t\t\t\t</div>\r\n\t\t\t\t</div>\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-center">\r\n\t\t\t<button id="forminator-apply-preset" class="sui-button sui-button-blue">\r\n\t\t\t\t<span class="sui-button-text-default">\r\n\t\t\t\t\t<i class="sui-icon-check" aria-hidden="true"></i> {{ button }}\r\n\t\t\t\t</span>\r\n\t\t\t\t<span class="sui-button-text-onload">\r\n\t\t\t\t\t<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t</span>\r\n\t\t\t</button>\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="forminator-create-appearance-preset-tpl">\r\n\r\n\t\t<div class="sui-box-body sui-content-center">\r\n\t\t\t<span class="sui-description" style="margin: -15px 0 30px">{{ content }}</span>\r\n\r\n\t\t\t<div class="sui-form-field">\r\n\t\t\t\t<label for="forminator-preset-name" class="sui-label">{{ nameLabel }}</label>\r\n\t\t\t\t<input type="text"\r\n\t\t\t\t\t   id="forminator-preset-name"\r\n\t\t\t\t\t   class="sui-form-control fui-required"\r\n\t\t\t\t\t   placeholder="{{ namePlaceholder }}">\r\n\t\t\t</div>\r\n\r\n\t\t\t<div class="sui-form-field">\r\n\t\t\t\t<label class="sui-label">{{ formLabel }}</label>\r\n\t\t\t\t{{ forminatorData.forms_select }}\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-center">\r\n\t\t\t<button id="forminator-create-preset" class="sui-button sui-button-blue" disabled="disabled">\r\n\t\t\t\t<span class="sui-button-text-default">\r\n\t\t\t\t\t<i class="sui-icon-plus" aria-hidden="true"></i> {{ title }}\r\n\t\t\t\t</span>\r\n\t\t\t\t<span class="sui-button-text-onload">\r\n\t\t\t\t\t<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>\r\n\t\t\t\t\t{{ loadingText }}\r\n\t\t\t\t</span>\r\n\t\t\t</button>\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n</div>\r\n';});

(function ($) {
	formintorjs.define('admin/popup/templates',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'forminator-popup-create--cform',

			step: '1',

			template: 'blank',

			events: {
				"click .select-quiz-template": "selectTemplate",
				"click .forminator-popup-close": "close",
				"change .forminator-new-form-type": "clickTemplate",
				"click #forminator-build-your-form": "handleMouseClick",
				"keyup": "handleKeyClick"
			},

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-form-popup-tpl' ).html()),

			newFormTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-tpl' ).html()),

			newFormContent: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-content-tpl' ).html() ),

			render: function() {
				var $popup = jQuery( '#forminator-popup');

				if( this.step === '1' ) {
					this.$el.html( this.popupTpl({
						templates: Forminator.Data.modules.custom_form.templates
					}) );

					this.$el.find( '.select-quiz-template' ).prop( "disabled", false );

					$popup.closest( '.sui-modal' ).removeClass( "sui-modal-sm" );
				}

				if( this.step === '2' ) {
					// Add name field
					this.$el.html( this.newFormTpl() );
					this.$el.find('.sui-box-body').html( this.newFormContent() );
					if( this.template === 'registration' ) {
						this.$el.find('#forminator-template-register-notice').show();
						this.$el.find('#forminator-form-name').val( Forminator.l10n.popup.registration_name );
					}
					if( this.template === 'login' ) {
						this.$el.find('#forminator-template-login-notice').show();
						this.$el.find('#forminator-form-name').val( Forminator.l10n.popup.login_name );
					}

					$popup.closest( '.sui-modal' ).addClass( 'sui-modal-sm' );
				}
			},

			close: function( e ) {
				e.preventDefault();

				Forminator.Popup.close();
			},

			clickTemplate: function( e ) {
				this.$el.find( '.select-quiz-template' ).prop( "disabled", false );
			},

			selectTemplate: function( e ) {
				e.preventDefault();

				var template = this.$el.find( 'input[name=forminator-form-template]:checked' ).val();

				this.template = template;

				this.step = '2';
				this.render();
			},

			handleMouseClick: function( e ) {
				this.createQuiz( e );
			},

			handleKeyClick: function( e ) {
				e.preventDefault();

				// If enter create form
				if( e.which === 13 ) {
					this.createQuiz( e );
				}
			},

			createQuiz: function( e ) {
				var $form_name = $( e.target ).closest( '.sui-box' ).find( '#forminator-form-name' );

				if( $form_name.val().trim() === "" ) {
					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).show();
				}  else {
					var url = Forminator.Data.modules.custom_form.new_form_url

					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).hide();

					form_url = url + '&name=' + $form_name.val();

					form_url = form_url + '&template=' + this.template;

					window.location.href = form_url;
				}
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/login',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-login-popup-tpl' ).html()),

			render: function() {
				this.$el.html( this.popupTpl({
					loginUrl: Forminator.Data.modules.login.login_url,
					registerUrl: Forminator.Data.modules.login.register_url
				}));
			},
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/quizzes',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'forminator-popup-create--quiz',

			step: 1,
			pagination: 1,

			type: 'knowledge',

			events: {
				"click .select-quiz-template": "selectTemplate",
				"click .select-quiz-pagination": "selectPagination",
				"click .forminator-popup-back": "goBack",
				"click .forminator-popup-close": "close",
				"change .forminator-new-quiz-type": "clickTemplate",
				"click #forminator-build-your-form": "handleMouseClick",
				"click #forminator-new-quiz-leads": "handleToggle",
				"keyup": "handleKeyClick"
			},

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-quizzes-popup-tpl' ).html()),

			newFormTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-quiz-tpl' ).html()),

			paginationTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-quiz-pagination-tpl' ).html()),

			newFormContent: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-quiz-content-tpl' ).html() ),

			render: function() {
				var $popup = jQuery( '#forminator-popup');
				$popup.removeClass( "sui-dialog-sm forminator-create-quiz-second-step forminator-create-quiz-pagination-step" );

				if( this.step === 1 ) {
					this.$el.html( this.popupTpl() );

					if ( this.name ) {
						this.$el.find( '#forminator-form-name' ).val( this.name );
						this.$el.find( '#forminator-new-quiz--' + this.type ).prop('checked',true);
					}

					this.$el.find( '.select-quiz-template' ).prop( "disabled", false );

				}

				if( this.step === 2 ) {
					this.$el.html( this.paginationTpl() );
					if ( ! this.pagination ) {
						this.$el.find( '[name="forminator-quiz-pagination"]' ).eq(1).prop('checked',true);
					}
					$popup.addClass( "forminator-create-quiz-pagination-step" );
				}

				if( this.step === 3 ) {
					// Add name field
					this.$el.html( this.newFormTpl() );
					this.$el.find('.sui-box-body').html( this.newFormContent() );

					$popup.addClass( "sui-dialog-sm forminator-create-quiz-second-step" );
				}
			},

			close: function( e ) {
				e.preventDefault();

				Forminator.Popup.close();
			},

			clickTemplate: function( e ) {
				this.$el.find( '.select-quiz-template' ).prop( "disabled", false );
			},

			selectTemplate: function( e ) {
				e.preventDefault();

				var type = this.$el.find( 'input[name=forminator-new-quiz]:checked' ).val();
				var form_name = this.$el.find( '#forminator-form-name' ).val();

				if( form_name.trim() === "" ) {
					$( e.target ).closest( '.sui-box' ).find( '#sui-quiz-name-error' ).show();
				} else {
					this.type = type;
					this.name = form_name;

					this.step = 2;
					this.render();
				}
			},

			goBack: function( e ) {
				e.preventDefault();

				this.step--;
				this.render();
			},

			selectPagination: function( e ) {
				e.preventDefault();

				var pagination = this.$el.find( 'input[name="forminator-quiz-pagination"]:checked' ).val();

				this.pagination = pagination;

				this.step = 3;
				this.render();
			},

			handleMouseClick: function( e ) {
				this.createQuiz( e );
			},

			handleKeyClick: function( e ) {
				e.preventDefault();

				// If enter create form
				if( e.which === 13 ) {
					if( this.step === 1 ) {
						this.selectTemplate( e );
					} else {
						this.createQuiz( e );
					}
				}
			},

			handleToggle: function( e ) {
				var leads = $( e.target ).is(':checked');
				var $notice = $( e.target ).closest( '.sui-box' ).find( '#sui-quiz-leads-description' );

				if ( leads ) {
					$notice.show();
				} else {
					$notice.hide();
				}
			},

			createQuiz: function( e ) {
				var leads = $( e.target ).closest( '.sui-box' ).find( '#forminator-new-quiz-leads' ).is(':checked');

				var url = Forminator.Data.modules.quizzes.knowledge_url;

				if( this.type === "nowrong" ) {
					url = Forminator.Data.modules.quizzes.nowrong_url;
				}

				form_url = url + '&name=' + this.name;

				if ( this.pagination ) {
					form_url = form_url + '&pagination=true';
				}

				if ( leads ) {
					form_url = form_url + '&leads=true';
				}

				window.location.href = form_url;
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/schedule',[
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-exports-schedule-popup-tpl').html()),

			events: {
				'change select[name="interval"]': "on_change_interval",
				// 'change #forminator-enable-scheduled-exports': 'on_change_enabled',
				'click .sui-toggle-label': 'click_label',
				'click .tab-labels .sui-tab-item': 'click_tab_label',
				'click .wpmudev-action-done': 'submit_schedule',
			},

			render: function () {

				this.$el.html(this.popupTpl({}));

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();

				var data = forminatorl10n.exporter;

				this.$el.find('input[name="if_new"]').prop('checked', data.if_new);

				this.set_enabled(data.enabled);

				this.$el.find('select[name="interval"]').change();
				if (data.email === null) {
					return;
				}
				this.$el.find('select[name="interval"]').val(data.interval);
				this.$el.find('select[name="day"]').val(data.day);
				this.$el.find('select[name="month_day"]').val( (data.month_day ? data.month_day : 1) );
				this.$el.find('select[name="hour"]').val(data.hour);

				if(data.interval === 'weekly') {
					this.$el.find('select[name="day"]').closest('.sui-form-field').show();
				} else if(data.interval === 'monthly') {
					this.$el.find('select[name="month_day"]').closest('.sui-form-field').show();
				}

			},

			set_enabled: function(enabled) {
				if (enabled) {
					this.$el.find('input[name="enabled"][value="true"]').prop('checked', true);
					this.$el.find('input[name="enabled"][value="false"]').prop('checked', false);


					this.$el.find('.tab-label-disable').removeClass('active');
					this.$el.find('.tab-label-enable').addClass('active');

					this.$el.find('.schedule-enabled').show();
					this.$el.find('input[name="email"]').prop('required', true);
				} else {
					this.$el.find('input[name="enabled"][value="false"]').prop('checked', true);
					this.$el.find('input[name="enabled"][value="true"]').prop('checked', false);

					this.$el.find('.tab-label-disable').addClass('active');
					this.$el.find('.tab-label-enable').removeClass('active');

					this.$el.find('.schedule-enabled').hide();
				}

			},

			load_select: function() {
				var data = forminatorl10n.exporter,
					options = {
						tags: true,
						tokenSeparators: [ ',', ' ' ],
						language: {
							searching: function() {
								return data.searching;
							},
							noResults: function() {
								return data.noResults;
							},
						},
						ajax: {
							url: forminatorData.ajaxUrl,
							type: 'POST',
							delay: 350,
							data: function( params ) {
								return {
									action: 'forminator_builder_search_emails',
									_wpnonce: forminatorData.searchNonce,
									q: params.term,
								};
							},
							processResults: function( data ) {
								return {
									results: data.data,
								};
							},
							cache: true,
						},
						createTag: function( params ) {
							const term = params.term.trim();
							if ( ! Forminator.Utils.is_email_wp( term ) ) {
								return null;
							}
							return {
								id: term,
								text: term,
							};
						},
						insertTag: function( data, tag ) {
							// Insert the tag at the end of the results
							data.push( tag );
						},

					};

				Forminator.Utils.forminator_select2_tags( this.$el, options );
			},

			on_change_interval: function(e) {
				//hide column
				this.$el.find('select[name="day"]').closest('.sui-form-field').hide();
				this.$el.find('select[name="month_day"]').closest('.sui-form-field').hide();
				if(e.target.value === 'weekly') {
					this.$el.find('select[name="month-day"]').closest('.sui-form-field').hide();
					this.$el.find('select[name="day"]').closest('.sui-form-field').show();
				} else if(e.target.value === 'monthly') {
					this.$el.find('select[name="month_day"]').closest('.sui-form-field').show();
					this.$el.find('select[name="day"]').closest('.sui-form-field').hide();
				}

			},

			click_label: function(e){
				e.preventDefault();

				// Simulate label click
				this.$el.closest('.sui-form-field').find( '.sui-toggle input' ).click();
			},

			click_tab_label: function (e) {
				var $target = $(e.target);

				if ($target.closest('.sui-tab-item').hasClass('tab-label-disable')) {
					this.set_enabled(false);
				} else if ($target.closest('.sui-tab-item').hasClass('tab-label-enable')) {
					this.load_select();
					this.set_enabled(true);
				}
			},

			submit_schedule: function (e) {
				this.$el.find('form.schedule-action').trigger('submit');
			},

		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/new-form',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-popup-tpl' ).html()),

			initialize: function ( options ) {
				this.title = options.title;
				this.title = Forminator.Utils.sanitize_uri_string( this.title );
			},

			render: function() {
				this.$el.html( this.popupTpl({
					title: this.title
				}));
			},
		});
	});
})(jQuery);

( function( $ ) {
	formintorjs.define('admin/popup/polls',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {

		return Backbone.View.extend({

			className: 'wpmudev-popup-templates',

			newFormTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-form-tpl' ).html()),
			newPollContent: Forminator.Utils.template( $( popupTpl ).find( '#forminator-new-poll-content-tpl' ).html() ),

			events: {
				'click #forminator-build-your-form': 'handleMouseClick',
				'keyup': 'handleKeyClick'
			},

			initialize: function( options ) {
				this.options = options;
			},

			render: function() {
				this.$el.html( this.newFormTpl() );
				this.$el.find('.sui-box-body').html( this.newPollContent() );
			},

			handleMouseClick: function( e ) {
				this.create_poll( e );
			},

			handleKeyClick: function( e ) {
				e.preventDefault();

				// If enter create form
				if( e.which === 13 ) {
					this.create_poll( e );
				}
			},

			create_poll: function( e ) {
				e.preventDefault();

				var $form_name = $( e.target ).closest( '.sui-box' ).find( '#forminator-form-name' );

				if( $form_name.val().trim() === "" ) {
					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).show();
				}  else {
					var form_url = Forminator.Data.modules.polls.new_form_url;

					$( e.target ).closest( '.sui-box' ).find( '.sui-error-message' ).hide();

					form_url = form_url + '&name=' + $form_name.val();
					window.location.href = form_url;
				}
			},

		});
	});
}( jQuery ) );

(function ($) {
	formintorjs.define('admin/popup/ajax',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'sui-box-body',

			events: {
				"click .wpmudev-action-done": "save",
				"click .wpmudev-action-ajax-done": "ajax_save",
				"click .wpmudev-action-ajax-cf7-import": "ajax_cf7_import",
				"click .wpmudev-button-clear-exports": "clear_exports",
				// Add poll funcitonality so the custom answer input shows up on preview
				"click .forminator-radio--field": "show_poll_custom_input",
				"click .forminator-popup-close": "close_popup",
				"click .forminator-retry-import": "ajax_cf7_import",
				"change #forminator-choose-import-form": "import_form_action",
				"change .forminator-import-forms": "import_form_action",
			},

			initialize: function( options ) {
				options            = _.extend({
					action       : '',
					nonce        : '',
					data         : '',
					id           : '',
					enable_loader: true
				}, options);

				this.action        = options.action;
				this.nonce         = options.nonce;
				this.data          = options.data;
				this.id            = options.id;
				this.enable_loader = options.enable_loader;

				return this.render();
			},

			render: function() {
				var self = this,
					tpl = false,
					data = {}
				;

				data.action = 'forminator_load_' + this.action + '_popup';
				data._ajax_nonce = this.nonce;
				data.data = this.data;

				if( this.id ) {
					data.id = this.id;
				}

				if (this.enable_loader) {
					var div_preloader = '';
					if ('sui-box-body' !== this.className) {
						div_preloader += '<div class="sui-box-body">';
					}
					div_preloader +=
						'<p class="fui-loading-dialog" aria-label="Loading content">' +
							'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' +
						'</p>'
					;
					if ('sui-box-body' !== this.className) {
						div_preloader += '</div>';
					}

					self.$el.html(div_preloader);
				}

				// make slightly bigger

				var ajax = $.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: data
				})
				.done(function (result) {
					if (result && result.success) {
						// Append & Show content
						self.$el.html(result.data);
						self.$el.find('.wpmudev-hidden-popup').show(400);

						// Delegate SUI events
						Forminator.Utils.sui_delegate_events();

						// Init Pagination on custom form if exist
						var custom_form = self.$el.find('.forminator-custom-form');

						// Delegate events
						self.delegateEvents();
					}
				});

				//remove the preloader
				ajax.always(function () {
					self.$el.find(".fui-loading-dialog").remove();
				});
			},

			save: function ( e ) {
				e.preventDefault();
				var data = {},
					nonce = $( e.target ).data( "nonce" )
				;

				data.action = 'forminator_save_' + this.action + '_popup';
				data._ajax_nonce = nonce;

				// Retieve fields
				$('.wpmudev-popup-form input, .wpmudev-popup-form select').each( function () {
					var field = $( this );
					data[ field.attr('name') ] = field.val();
				});

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function( result ) {
						Forminator.Popup.close( false, function() {
							window.location.reload();
						});
					}
				});
			},
			ajax_save: function ( e ) {
				var self = this;
				// display error response if avail
				// redirect to url on response if avail
				e.preventDefault();
				var data = {},
				    nonce = $( e.target ).data( "nonce" )
				;

				data.action = 'forminator_save_' + this.action + '_popup';
				data._ajax_nonce = nonce;

				// Retieve fields
				$('.wpmudev-popup-form input, .wpmudev-popup-form select, .wpmudev-popup-form textarea').each( function () {
					var field = $( this );
					data[ field.attr('name') ] = field.val();
				});

				this.$el.find(".sui-button:not(.disable-loader)").addClass("sui-button-onload");

				var ajax = $.ajax({
					url    : Forminator.Data.ajaxUrl,
					type   : "POST",
					data   : data,
					success: function (result) {
						if (true === result.success) {
							var redirect = false;
							if (!_.isUndefined(result.data.url)) {
								redirect = result.data.url;
							}
							Forminator.Popup.close(false, function () {
								if (redirect) {
									location.href = redirect;
								}
							});
						} else {
							const noticeId = 'wpmudev-ajax-error-placeholder';
							const noticeMessage = '<p>' + result.data + '</p>';
							const noticeOption = {
								type: 'error',
								autoclose: {
									timeout: 8000
								}
							};

							if (!_.isUndefined(result.data)) {
								SUI.openNotice( noticeId, noticeMessage, noticeOption );
							}
						}
					}
				});
				ajax.always(function () {
					self.$el.find(".sui-button:not(.disable-loader)").removeClass("sui-button-onload");
				})
			},

			clear_exports: function ( e ) {
				e.preventDefault();
				var data = {},
					self = this,
					nonce = $( e.target ).data( "nonce" ),
					form_id = $( e.target ).data( "form-id" )
				;

				data.action = 'forminator_clear_' + this.action + '_popup';
				data._ajax_nonce = nonce;
				data.id = form_id;

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function() {
						self.render();
					}
				});
			},
			show_poll_custom_input: function (e) {
				var self = this,
					$input = this.$el.find('.forminator-input'),
					checked = e.target.checked,
					$id = $(e.target).attr('id');

				$input.hide();
				if (self.$el.find('.forminator-input#' + $id + '-extra').length) {
					var $extra = self.$el.find('.forminator-input#' + $id + '-extra');
					if (checked) {
						$extra.show();
					} else {
						$extra.hide();
					}
				}
			},
			ajax_cf7_import: function ( e ) {
				var self = this,
					data = self.$el.find('form').serializeArray();
				// display error response if avail
				// redirect to url on response if avail
				e.preventDefault();

				this.$el.find(".sui-button:not(.disable-loader)").addClass("sui-button-onload");
				this.$el.find('.wpmudev-ajax-error-placeholder').addClass('sui-hidden');
				this.$el.find(".forminator-cf7-imported-fail").addClass("sui-hidden");

				var ajax = $.ajax({
					url    : Forminator.Data.ajaxUrl,
					type   : "POST",
					data   : data,
					xhr: function () {
						var xhr = new window.XMLHttpRequest();
						xhr.upload.addEventListener("progress", function (evt) {
							if ( evt.lengthComputable ) {
								var percentComplete = evt.loaded / evt.total;
								percentComplete = parseInt(percentComplete * 100);
								self.$el.find(".forminator-cf7-importing .sui-progress-text").html( percentComplete + '%');
								self.$el.find(".forminator-cf7-importing .sui-progress-bar span").css( 'width', percentComplete + '%');
							}
						}, false);
						return xhr;
					},
					success: function (result) {
						if (true === result.success) {
							setTimeout(function(){
								self.$el.find(".forminator-cf7-importing").addClass("sui-hidden");
								self.$el.find(".forminator-cf7-imported").removeClass("sui-hidden");
							}, 1000);
						} else {
							if (!_.isUndefined(result.data)) {
								setTimeout(function(){
										self.$el.find(".forminator-cf7-importing").addClass("sui-hidden");
										self.$el.find(".forminator-cf7-imported-fail").removeClass("sui-hidden");
									}, 1000);
								self.$el.find('.wpmudev-ajax-error-placeholder').removeClass('sui-hidden').find('p').text(result.data);
							}
						}
					}
				});
				ajax.always(function (e) {
					self.$el.find(".sui-button:not(.disable-loader)").removeClass("sui-button-onload");
					self.$el.find(".forminator-cf7-import").addClass("sui-hidden");
					self.$el.find(".forminator-cf7-importing").removeClass("sui-hidden");
				});
			},
			close_popup: function() {
				Forminator.Popup.close();
			},

			import_form_action: function(e) {
				e.preventDefault();
				var target = $(e.target),
					value = target.val(),
					btn_action = false;
				if( 'specific' === value ) {
					btn_action = true;
				}
				if( value == null || ( Array.isArray( value ) && value.length < 1 ) ) {
					btn_action = true;
				}
				this.$el.find('.wpmudev-action-ajax-cf7-import').prop( "disabled", btn_action );
			},
		});
	});
})(jQuery);

(function ($) {
    formintorjs.define('admin/popup/delete',[
        'text!tpl/dashboard.html',
    ], function (popupTpl) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup',

            popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-delete-popup-tpl').html()),
            popupPollTpl: Forminator.Utils.template($(popupTpl).find('#forminator-delete-poll-popup-tpl').html()),

			initialize: function( options ) {
				this.module = options.module;
				this.nonce = options.nonce;
				this.id = options.id;
				this.action = options.action;
				this.referrer = options.referrer;
				this.button = options.button || Forminator.l10n.popup.delete;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
			},

            render: function () {
            	if( 'poll' === this.module ) {
					this.$el.html(this.popupPollTpl({
						nonce: this.nonce,
						id: this.id,
						referrer: this.referrer,
						content: this.content,
					}));
				} else {
					this.$el.html(this.popupTpl({
						nonce: this.nonce,
						id: this.id,
						action: this.action,
						referrer: this.referrer,
						button: this.button,
						content: this.content,
					}));
				}
            },
        });
    });
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/preview',[
		'text!tpl/dashboard.html',
	], function( popupTpl ) {
		return Backbone.View.extend({
			className: 'sui-box-body',

			initialize: function( options ) {
				var self = this;
				var args = {
					action       : '',
					type         : '',
					id           : '',
					preview_data : {},
					enable_loader: true
				};
				if ( 'forminator_quizzes' === options.type ) {
					args.has_lead = options.has_lead;
					args.leads_id  = options.leads_id;
				}
				options            = _.extend( args, options );

				this.action        = options.action;
				this.type          = options.type;
				this.nonce         = options.nonce;
				this.id            = options.id;
				this.render_id     = 0;
				this.preview_data  = options.preview_data;
				this.enable_loader = options.enable_loader;

				if ( 'forminator_quizzes' === options.type ) {
					this.has_lead = options.has_lead;
					this.leads_id  = options.leads_id;
				}

				$(document).off('after.load.forminator');
				$(document).on('after.load.forminator', function(e){
					self.after_load();
				});

				return this.render();
			},

			render: function () {
				var self = this,
				    tpl  = false,
				    data = {}
				;

				data.action           = this.action;
				data.type             = this.type;
				data.id               = this.id;
				data.render_id        = this.render_id;
				data.nonce				 = this.nonce;
				data.is_preview       = 1;
				data.preview_data     = this.preview_data;
				data.last_submit_data = {};

				if ( 'forminator_quizzes' === this.type ) {
					data.has_lead  = this.has_lead;
					data.leads_id  = this.leads_id;
				}

				if (this.enable_loader) {
					var div_preloader = '';
					if ('sui-box-body' !== this.className) {
						div_preloader += '<div class="sui-box-body">';
					}
					div_preloader +=
						'<div class="fui-loading-dialog">' +
							'<p style="margin: 0; text-align: center;" aria-hidden="true"><span class="sui-icon-loader sui-md sui-loading"></span></p>' +
							'<p class="sui-screen-reader-text">Loading content...</p>' +
						'</div>';
					;
					if ('sui-box-body' !== this.className) {
						div_preloader += '</div>';
					}

					self.$el.html(div_preloader);
				}

				var dummyForm = $('<form id="forminator-module-' + this.id + '" data-forminator-render="' + this.render_id+ '" style="display:none"></form>');
				self.$el.append(dummyForm);

				$(self.$el.find('#forminator-module-' + this.id +'[data-forminator-render="' + this.render_id+ '"]').get(0)).forminatorLoader(data);

			},

			after_load: function() {
				var self = this;
				self.$el.find('div[data-form="forminator-module-' + this.id + '"]').remove();
				self.$el.find(".fui-loading-dialog").remove();
			}
		});
	});
})(jQuery);

(function ($) {
    formintorjs.define('admin/popup/reset-plugin-settings',[
        'text!tpl/dashboard.html',
    ], function (popupTpl) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup',

            popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-reset-plugin-settings-popup-tpl').html()),

			events: {
				"click .popup-confirmation-confirm": "confirm_action",
			},

			initialize: function( options ) {
				this.nonce = options.nonce;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
			},

            render: function () {
                this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
	                content: this.content,
				}));
            },

			confirm_action: function(e) {
				$( e.currentTarget ).addClass( 'sui-button-onload' );
			},

        });
    });
})(jQuery);

(function ($) {
    formintorjs.define('admin/popup/disconnect-stripe',[
        'text!tpl/dashboard.html',
    ], function (popupTpl) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup delete-stripe--popup',

            popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-disconnect-stripe-popup-tpl').html()),

			initialize: function( options ) {
				this.nonce = options.nonce;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
			},

            render: function () {
                this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
	                content: this.content,
				}));
            },
        });
    });
})(jQuery);

(function ($) {
    formintorjs.define('admin/popup/disconnect-paypal',[
        'text!tpl/dashboard.html',
    ], function (popupTpl) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup',

            popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-disconnect-paypal-popup-tpl').html()),

			initialize: function( options ) {
				this.nonce = options.nonce;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
			},

            render: function () {
                this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
	                content: this.content,
				}));
            },
        });
    });
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/approve-user',[
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-approve-user-popup-tpl').html()),
			events: {
				"click .approve-user.popup-confirmation-confirm" : 'approveUser',
			},
			initialize: function( options ) {
				this.nonce = options.nonce;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted;
				this.activationKey = options.activationKey;
			},

			render: function () {
				this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
					content: this.content,
					activationKey: this.activationKey,
				}));
			},

			submitForm: function( $form, nonce, activationKey ) {
				var data = {},
					self = this
				;

				data.action = 'forminator_approve_user_popup';
				data._ajax_nonce = nonce;
				data.activation_key = activationKey;

				var ajaxData = $form.serialize() + '&' + $.param(data);

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: ajaxData,
					beforeSend: function() {
						$form.find('.sui-button').addClass('sui-button-onload');
					},
					success: function( result ) {
						if (result && result.success) {
							Forminator.Notification.open('success', Forminator.l10n.commons.approve_user_successfull, 4000);
							window.location.reload();
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', Forminator.l10n.commons.approve_user_unsuccessfull, 4000 );
					}
				}).always(function(){
					$form.find('.sui-button').removeClass('sui-button-onload');
				});
			},

			approveUser: function(e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var popup   = this.$el.find('.form-approve-user');
				var form    = popup.find('form');

				this.submitForm( form, this.nonce, this.activationKey );

				return false;
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/delete-unconfirmed-user',[
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-delete-unconfirmed-user-popup-tpl').html()),
			events: {
				"click .delete-unconfirmed-user.popup-confirmation-confirm" : 'deleteUnconfirmedUser',
			},
			initialize: function( options ) {
				this.nonce = options.nonce;
				this.formId = options.formId;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted ;
				this.activationKey = options.activationKey;
				this.entryId = options.entryId;
			},

			render: function () {
				this.$el.html(this.popupTpl({
					nonce: this.nonce,
					formId: this.formId,
					referrer: this.referrer,
					content: this.content,
					activationKey: this.activationKey,
					entryId: this.entryId,
				}));
			},

			submitForm: function( $form, nonce, activationKey, formId, entryId ) {
				var data = {
					action: 'forminator_delete_unconfirmed_user_popup',
					_ajax_nonce: nonce,
					activation_key: activationKey,
					form_id: formId,
					entry_id: entryId,
				};

				var ajaxData = $form.serialize() + '&' + $.param(data);

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: ajaxData,
					beforeSend: function() {
						$form.find('.sui-button').addClass('sui-button-onload');
					},
					success: function( result ) {
						if (result && result.success) {
							window.location.reload();
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', error.data, 4000 );
					}
				}).always(function(){
					$form.find('.sui-button').removeClass('sui-button-onload');
				});
			},

			deleteUnconfirmedUser: function(e) {
				e.preventDefault();

				var $target = $(e.target);
				$target.addClass('sui-button-onload');

				var popup   = this.$el.find('.form-delete-unconfirmed-user');
				var form    = popup.find('form');

				this.submitForm( form, this.nonce, this.activationKey, this.formId, this.entryId );

				return false;
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/create-appearance-preset',[
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-create-appearance-preset-tpl').html()),
			events: {
				"click #forminator-create-preset" : 'createPreset',
				"keydown #forminator-preset-name" : 'toggleButton',
			},
			initialize: function( options ) {
				this.nonce = options.nonce;
				this.title = options.title;
				this.content = options.content;
				this.$target = options.$target;
			},

			render: function () {
				var	formLabel= this.$target.data('modal-preset-form-label'),
					loadingText= this.$target.data('modal-preset-loading-text'),
					nameLabel= this.$target.data('modal-preset-name-label'),
					namePlaceholder= this.$target.data('modal-preset-name-placeholder');

				this.$el.html(this.popupTpl({
					title: this.title,
					content: this.content,
					formLabel: formLabel,
					loadingText: loadingText,
					nameLabel: nameLabel,
					namePlaceholder: namePlaceholder,
				}));
			},

			toggleButton: function(e) {
				setTimeout( function(){
					var val = $(e.currentTarget).val().trim();
					$('#forminator-create-preset').prop( 'disabled', !val );
				}, 300 );
			},

			createPreset: function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();

				var $target = $(e.target);
				$target.addClass('sui-button-onload-text');

				var formId = this.$el.find('select[name="form_id"]').val();
				var name   = this.$el.find('#forminator-preset-name').val();

				var data = {
					action: 'forminator_create_appearance_preset',
					_ajax_nonce: this.nonce,
					form_id: formId,
					name: name,
				};

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function( result ) {
						if (result && result.success) {
							Forminator.openPreset( result.data );
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
							$target.removeClass('sui-button-onload-text');
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', error.data, 4000 );
						$target.removeClass('sui-button-onload-text');
					}
				});

				return false;
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/apply-appearance-preset',[
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-apply-appearance-preset-tpl').html()),
			events: {
				"click #forminator-apply-preset" : 'applyPreset',
			},
			initialize: function( options ) {
				this.$target = options.$target;
			},

			render: function () {
				this.$el.html(this.popupTpl({
					description: Forminator.Data.modules.ApplyPreset.description,
					notice: Forminator.Data.modules.ApplyPreset.notice,
					button: Forminator.Data.modules.ApplyPreset.button,
					selectbox: Forminator.Data.modules.ApplyPreset.selectbox,
				}));
			},

			applyPreset: function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();

				var $target = $(e.target);
				$target.addClass('sui-button-onload-text');

				var	id = this.$target.data('form-id'),
					ids = [],
					presetId = this.$el.find('select[name="appearance_preset"]').val();

				if ( id ) {
					ids = [ id ];
				} else {
					ids = $('#forminator_bulk_ids').val().split(',');
				}

				var data = {
					action: 'forminator_apply_appearance_preset',
					_ajax_nonce: Forminator.Data.modules.ApplyPreset.nonce,
					preset_id: presetId,
					ids: ids,
				};

				$.ajax({
					url: Forminator.Data.ajaxUrl,
					type: "POST",
					data: data,
					success: function( result ) {
						if (result && result.success) {
							Forminator.Notification.open( 'success', result.data, 4000 );
							Forminator.Popup.close();
						} else {
							Forminator.Notification.open( 'error', result.data, 4000 );
							$target.removeClass('sui-button-onload-text');
						}
					},
					error: function ( error ) {
						Forminator.Notification.open( 'error', error.data, 4000 );
						$target.removeClass('sui-button-onload-text');
					}
				});

				return false;
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/popup/confirm',[
		'text!tpl/dashboard.html',
	], function (popupTpl) {
		return Backbone.View.extend({
			className: 'wpmudev-section--popup',

			popupTpl: Forminator.Utils.template($(popupTpl).find('#forminator-confirmation-popup-tpl').html()),

			default_options: {
				confirmation_message: Forminator.l10n.popup.confirm_action,
				confirmation_title: Forminator.l10n.popup.confirm_title,
				confirm_callback: function () {
					this.close();
				},
				cancel_callback: function () {
					this.close();
				}
			},
			confirm_options: {},
			events: {
				"click .popup-confirmation-confirm": "confirm_action",
				"click .popup-confirmation-cancel": "cancel_action"
			},

			initialize: function (options) {
				this.confirm_options = _.defaults(options, this.default_options);
			},

			render: function () {
				this.$el.html(this.popupTpl(this.confirm_options));
				return this;
			},

			confirm_action: function(){
				this.confirm_options.confirm_callback.apply(this, []);
			},

			cancel_action: function(){
				this.confirm_options.cancel_callback.apply(this, []);
			},

			close: function () {
				Forminator.Popup.close();
			}
		});
	});
})(jQuery);

(function ($) {
    formintorjs.define('admin/popup/addons-actions',[
        'text!tpl/dashboard.html',
    ], function( popupTpl ) {
        return Backbone.View.extend({
            className: 'wpmudev-section--popup',

            popupTpl: Forminator.Utils.template( $( popupTpl ).find( '#forminator-addons-action-popup-tpl' ).html() ),

			initialize: function( options ) {
				this.nonce = options.nonce;
				this.id = options.id;
				this.referrer = options.referrer;
				this.content = options.content || Forminator.l10n.popup.cannot_be_reverted;
				this.forms = options.forms || [];
			},

            render: function () {
                this.$el.html(this.popupTpl({
					nonce: this.nonce,
					id: this.id,
					referrer: this.referrer,
	                content: this.content,
	                forms: this.forms,
				}));
            },
        });
    });
})(jQuery);

(function ($) {
	formintorjs.define('admin/popups',[
		'admin/popup/templates',
		'admin/popup/login',
		'admin/popup/quizzes',
		'admin/popup/schedule',
		'admin/popup/new-form',
		'admin/popup/polls',
		'admin/popup/ajax',
		'admin/popup/delete',
		'admin/popup/preview',
		'admin/popup/reset-plugin-settings',
		'admin/popup/disconnect-stripe',
		'admin/popup/disconnect-paypal',
		'admin/popup/approve-user',
		'admin/popup/delete-unconfirmed-user',
		'admin/popup/create-appearance-preset',
		'admin/popup/apply-appearance-preset',
		'admin/popup/confirm',
		'admin/popup/addons-actions'
	], function(
		TemplatesPopup,
		LoginPopup,
		QuizzesPopup,
		SchedulePopup,
		NewFormPopup,
		PollsPopup,
		AjaxPopup,
		DeletePopup,
		PreviewPopup,
		ResetPluginSettingsPopup,
		DisconnectStripePopup,
		DisconnectPaypalPopup,
		ApproveUserPopup,
		DeleteUnconfirmedPopup,
		CreateAppearancePresetPopup,
		ApplyAppearancePresetPopup,
		confirmationPopup,
		AddonsActions
	) {
		var Popups = Backbone.View.extend({
			el: 'main.sui-wrap',

			events: {
				"click .wpmudev-open-modal": "open_modal",
				"click .wpmudev-button-open-modal": "open_modal"
			},

			initialize: function () {
				var new_form = Forminator.Utils.get_url_param( 'new' ),
					form_title = Forminator.Utils.get_url_param( 'title' )
				;

				if( new_form ) {
					var newForm = new NewFormPopup({
						title: form_title
					});
					newForm.render();

					this.open_popup( newForm, Forminator.l10n.popup.congratulations );
				}

				this.open_export();
				this.open_delete();

				this.maybeShowNotice();

				return this.render();
			},

			render: function() {
				return this;
			},

			maybeShowNotice: function() {
				var notices = Forminator.l10n.notices;
				if ( notices ) {
					$.each(notices, function(i, message) {
						var delay = 4000;
						if ( 'custom_notice' === i ) {
							delay = undefined;
						}
						Forminator.Notification.open( 'success', message, delay );
					});
				}
			},

			open_delete: function() {
				var has_delete = Forminator.Utils.get_url_param( 'delete' ),
					id = Forminator.Utils.get_url_param( 'module_id' ),
					nonce = Forminator.Utils.get_url_param( 'nonce' ),
					type = Forminator.Utils.get_url_param( 'module_type'),
					title = Forminator.l10n.popup.delete_form,
					desc = Forminator.l10n.popup.are_you_sure_form,
					self = this
				;

				if ( type === 'poll' ) {
					title = Forminator.l10n.popup.delete_poll;
					desc = Forminator.l10n.popup.are_you_sure_poll;
				}

				if ( type === 'quiz' ) {
					title = Forminator.l10n.popup.delete_quiz;
					desc = Forminator.l10n.popup.are_you_sure_quiz;
				}

				if ( has_delete ) {
					setTimeout( function() {
						self.open_delete_popup( '', id, nonce, title, desc );
					}, 100 );
				}
			},

			open_export: function() {
				var has_export = Forminator.Utils.get_url_param( 'export' ),
					id = Forminator.Utils.get_url_param( 'module_id' ),
					nonce = Forminator.Utils.get_url_param( 'exportnonce' ),
					type = Forminator.Utils.get_url_param( 'module_type'),
					self = this
				;

				if ( has_export ) {
					setTimeout( function() {
						self.open_export_module_modal( type, nonce, id, Forminator.l10n.popup.export_form, false, true, 'wpmudev-ajax-popup' );
					}, 100 );
				}
			},

			open_modal: function( e ) {
				e.preventDefault();

				var $target = $( e.target ),
					$container = $( e.target ).closest( '.wpmudev-split--item' );

				if( ! $target.hasClass( 'wpmudev-open-modal' ) && ! $target.hasClass( 'wpmudev-button-open-modal' ) ) {
					$target = $target.closest( '.wpmudev-open-modal,.wpmudev-button-open-modal' );
				}

				var $module = $target.data( 'modal' ),
					nonce = $target.data( 'nonce' ),
					id = $target.data( 'form-id' ),
					action = $target.data( 'action' ),
					has_leads = $target.data( 'has-leads' ),
					leads_id = $target.data( 'leads-id' ),
					title = $target.data( 'modal-title' ),
				   content = $target.data('modal-content'),
					button = $target.data('button-text'),
					preview_nonce = $target.data('nonce-preview')
				;

				// Open appropriate popup
				switch ( $module ) {
					case 'custom_forms':
						this.open_cform_popup();
						break;
					case 'login_registration_forms':
						this.open_login_popup();
						break;
					case 'polls':
						this.open_polls_popup();
						break;
					case 'quizzes':
						this.open_quizzes_popup();
						break;
					case 'exports':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.your_exports );
						break;
					case 'exports-schedule':
						this.open_exports_schedule_popup();
						break;
					case 'delete-module':
						this.open_delete_popup( '', id, nonce, title, content, action, button );
						break;
					case 'delete-poll-submission':
						this.open_delete_popup( 'poll', id, nonce, title, content );
						break;
					case 'paypal':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.paypal_settings );
						break;
					case 'preview_cforms':
						if (_.isUndefined(title)) {
							title = Forminator.l10n.popup.preview_cforms
						}
						this.open_preview_popup( id, title, 'forminator_load_form', 'forminator_forms', preview_nonce );
						break;
					case 'preview_polls':
						if (_.isUndefined(title)) {
							title = Forminator.l10n.popup.preview_polls
						}
						this.open_preview_popup( id, title, 'forminator_load_poll', 'forminator_polls', preview_nonce );
						break;
					case 'preview_quizzes':
						if (_.isUndefined(title)) {
							title = Forminator.l10n.popup.preview_quizzes
						}
						this.open_quiz_preview_popup( id, title, 'forminator_load_quiz', 'forminator_quizzes', has_leads, leads_id, preview_nonce );
						break;
					case 'captcha':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.captcha_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'currency':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.currency_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'pagination_entries':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.pagination_entries, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'pagination_listings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.pagination_listings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'email_settings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.email_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'uninstall_settings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.uninstall_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'privacy_settings':
						this.open_settings_modal( $module, nonce, id, Forminator.l10n.popup.privacy_settings, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'create_preset':
						this.create_appearance_preset_modal( nonce, title, content, $target );
						break;
					case 'apply_preset':
						this.apply_appearance_preset_modal( $target );
						break;
					case 'delete_preset':
						this.delete_preset_modal( title, content );
						break;
					case 'export_form':
						this.open_export_module_modal( 'form', nonce, id, Forminator.l10n.popup.export_form, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'export_poll':
						this.open_export_module_modal( 'poll', nonce, id, Forminator.l10n.popup.export_poll, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'export_quiz':
						this.open_export_module_modal( 'quiz', nonce, id, Forminator.l10n.popup.export_quiz, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form':
						this.open_import_module_modal( 'form', nonce, id, Forminator.l10n.popup.import_form, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form_cf7':
						this.open_import_module_modal( 'form_cf7', nonce, id, Forminator.l10n.popup.import_form_cf7, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form_ninja':
						this.open_import_module_modal( 'form_ninja', nonce, id, Forminator.l10n.popup.import_form_ninja, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_form_gravity':
						this.open_import_module_modal( 'form_gravity', nonce, id, Forminator.l10n.popup.import_form_gravity, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_poll':
						this.open_import_module_modal( 'poll', nonce, id, Forminator.l10n.popup.import_poll, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'import_quiz':
						this.open_import_module_modal( 'quiz', nonce, id, Forminator.l10n.popup.import_quiz, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'reset-plugin-settings':
						this.open_reset_plugin_settings_popup( nonce, title, content );
						break;
					case 'disconnect-stripe':
						this.open_disconnect_stripe_popup( nonce, title, content );
						break;
					case 'disconnect-paypal':
						this.open_disconnect_paypal_popup( nonce, title, content );
						break;
					case 'approve-user-module':
						var activationKey = $target.data('activation-key');
						this.open_approve_user_popup( nonce, title, content, activationKey );
						break;
					case 'delete-unconfirmed-user-module':
						this.open_unconfirmed_user_popup( $target.data( 'form-id' ), nonce, title, content, $target.data('activation-key'), $target.data( 'entry-id' ) );
						break;
					case 'addons_page_details':
						this.open_addons_page_modal( $module, nonce, id, title, false, true, 'wpmudev-ajax-popup' );
						break;
					case 'addons-deactivate':
						this.open_addons_actions_popup( $module, $target.data( 'addon' ), nonce, title, content, $target.data( 'addon-slug' ) );
						break;
				}
			},

			open_popup: function ( view, title, has_custom_box, action_text, action_css_class, action_callback, rendered_call_back, modalSize, modalTitle ) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}

				var popup_options = {
					title: title
				};
				if (!_.isUndefined(has_custom_box)) {
					popup_options.has_custom_box = has_custom_box;
				}
				if (!_.isUndefined(action_text)) {
					popup_options.action_text = action_text;
				}
				if (!_.isUndefined(action_css_class)) {
					popup_options.action_css_class = action_css_class;
				}
				if (!_.isUndefined(action_callback)) {
					popup_options.action_callback = action_callback;
				}

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}

					if (typeof rendered_call_back === 'function') {
						rendered_call_back.apply(this);
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_ajax_popup: function( action, nonce, id, title, enable_loader, has_custom_box, ajax_div_class_name, modalSize, modalTitle) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}
				if( _.isUndefined( enable_loader ) ) {
					enable_loader = true;
				}
				if( _.isUndefined( has_custom_box ) ) {
					has_custom_box = false;
				}

				if( _.isUndefined( ajax_div_class_name ) ) {
					ajax_div_class_name = 'sui-box-body';
				}

				var view = new AjaxPopup({
					action: action,
					nonce: nonce,
					id: id,
					enable_loader: true,
					className: ajax_div_class_name,
				});

				var popup_options = {
					title         : title,
					has_custom_box: has_custom_box
				};

				Forminator.Popup.open(function () {
					$(this).append(view.el);
				}, popup_options, modalSize, modalTitle );
			},

			// MODAL: Delete.
			open_delete_popup: function ( module, id, nonce, title, content, action, button) {
				action = action || 'delete';
				var newForm = new DeletePopup({
					module: module,
					id: id,
					action: action,
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					button: button,
					content: content
				});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			// MODAL: Create Form.
			open_cform_popup: function () {
				var newForm = new TemplatesPopup({
					type: 'form'
				});
				newForm.render();

				var view = newForm;

				var modalSize = 'lg';

				var popup_options = {
					title: '',
					has_custom_box: true
				};

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize );

			},

			// MODAL: Create Poll.
			open_polls_popup: function() {
				var newForm = new PollsPopup();
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: '',
					has_custom_box: true
				};

				var modalSize = 'sm';

				Forminator.Popup.open( function() {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize );
			},

			// MODAL: Create Quiz.
			open_quizzes_popup: function () {

				var self = this,
					newForm = new QuizzesPopup();

				newForm.render();

				var view = newForm;

				var popup_options = {
					title: Forminator.l10n.quiz.choose_quiz_title,
					has_custom_box: true
				}

				var modalSize = 'lg';

				Forminator.Popup.open( function () {

					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize );

				//this.open_popup( newForm, Forminator.l10n.quiz.choose_quiz_type, true );
			},

			// MODAL: Import.
			open_import_module_modal: function(module, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				var action = '';
				switch(module){
					case 'form':
					case 'form_cf7':
					case 'form_ninja':
					case 'form_gravity':
					case 'poll':
					case 'quiz':
						action = 'import_' + module;
						break;
				}
				this.open_ajax_popup(
					action,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'sm',
					'center'
				);
			},

			// MODAL: Export (on Submissions page).
			open_exports_schedule_popup: function () {
				var newForm = new SchedulePopup();
				newForm.render();

				this.open_popup(
					newForm,
					Forminator.l10n.popup.edit_scheduled_export,
					true,
					undefined,
					undefined,
					undefined,
					undefined,
					'md',
					'inline'
				);
			},

			// MODAL: Reset Plugin (on Settings page).
			open_reset_plugin_settings_popup: function (nonce, title, content) {
				var self = this,
					newForm = new ResetPluginSettingsPopup({
						nonce: nonce,
						referrer: window.location.pathname + window.location.search,
						content: content
					});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			// MODAL: Disconnect PayPal (on Settings page).
			open_addons_actions_popup: function ( module, id, nonce, title, content, slug ) {
				var self = this,
					newForm = new AddonsActions({
						module: module,
						id: id,
						nonce: nonce,
						referrer: window.location.pathname + window.location.search,
						content: content,
						forms: 'stripe' === slug ? forminatorData.stripeForms : forminatorData.paypalForms
					});

				newForm.render();
				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_login_popup: function () {
				var newForm = new LoginPopup();
				newForm.render();

				this.open_popup(
					newForm,
					Forminator.l10n.popup.edit_login_form,
					undefined,
					undefined,
					undefined,
					undefined,
					undefined,
					'md',
					'inline'
				);
			},

			open_settings_modal: function ( type, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				this.open_ajax_popup(
					type,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'md',
					'inline'
				);
			},

			open_addons_page_modal: function ( type, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				this.open_ajax_popup(
					type,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'md',
					'inline'
				);
			},

			open_export_module_modal: function(module, nonce, id, label, enable_loader, has_custom_box, ajax_div_class_name ) {
				var action = '';
				switch(module){
					case 'form':
					case 'poll':
					case 'quiz':
						action = 'export_' + module;
						break;
				}
				this.open_ajax_popup(
					action,
					nonce,
					id,
					label,
					enable_loader,
					has_custom_box,
					ajax_div_class_name,
					'md',
					'inline'
				);
			},

			open_preview_popup: function( id, title, action, type, nonce ) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}

				var view = new PreviewPopup( {
					action: action,
					type: type,
					nonce: nonce,
					id: id,
					enable_loader: true,
					className: 'sui-box-body',
				} );

				var popup_options = {
					title         : title,
					has_custom_box: true
				};

				var modalSize = 'lg';

				var modalTitle = 'inline';

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, popup_options, modalSize, modalTitle );
			},

			open_quiz_preview_popup: function( id, title, action, type, has_leads, leads_id, nonce ) {
				if( _.isUndefined( title ) ) {
					title = Forminator.l10n.custom_form.popup_label;
				}

				var view = new PreviewPopup( {
					action: action,
					type: type,
					id: id,
					enable_loader: true,
					className: 'sui-box-body',
					has_lead: has_leads,
					leads_id: leads_id,
					nonce: nonce,
				} );

				var popup_options = {
					title         : title,
					has_custom_box: true
				};

				var modalSize = 'lg';

				var modalTitle = 'inline';

				Forminator.Popup.open(function () {
					$(this).append(view.el);

				}, popup_options, modalSize, modalTitle );
			},

			apply_appearance_preset_modal: function ($target) {
				var newForm = new ApplyAppearancePresetPopup({
						$target: $target
					});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, {
					title: Forminator.Data.modules.ApplyPreset.title,
					has_custom_box: true,
				}, 'sm', 'center' );
			},

			delete_preset_modal: function (title, content) {
				var newForm = new confirmationPopup({
						confirmation_message: content,
						confirm_callback: function () {
							var deletePreset = new Event('deletePreset');
							window.dispatchEvent( deletePreset );
						},
					});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, {
					title: title,
					has_custom_box: true,
				}, 'sm', 'center' );
			},

			create_appearance_preset_modal: function (nonce, title, content, $target) {
				var newForm = new CreateAppearancePresetPopup({
					nonce: nonce,
					$target: $target,
					title: title,
					content: content
				});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					$( this ).append( view.el );
				}, {
					title: title,
					has_custom_box: true,
				}, 'sm', 'center' );
			},

			open_disconnect_stripe_popup: function (nonce, title, content) {
				var self = this;
				var newForm = new DisconnectStripePopup({
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content
				});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_disconnect_paypal_popup: function (nonce, title, content) {
				var self = this;
				var newForm = new DisconnectPaypalPopup({
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content
				});
				newForm.render();

				var view = newForm;

				var popup_options = {
					title: title,
					has_custom_box: true
				}

				var modalSize = 'sm';

				var modalTitle = 'center';

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, popup_options, modalSize, modalTitle );
			},

			open_approve_user_popup: function (nonce, title, content, activationKey) {
				var self = this;
				var newForm = new ApproveUserPopup({
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content,
					activationKey: activationKey
				});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, {
					title: title,
					has_custom_box: true,
				}, 'md', 'inline' );
			},

			open_unconfirmed_user_popup: function ( formId, nonce, title, content, activationKey, entryId ) {
				var newForm = new DeleteUnconfirmedPopup({
					formId: formId,
					nonce: nonce,
					referrer: window.location.pathname + window.location.search,
					content: content,
					activationKey: activationKey,
					entryId: entryId
				});
				newForm.render();

				var view = newForm;

				Forminator.Popup.open( function () {
					// If not a view append directly
					if( ! _.isUndefined( view.el ) ) {
						$( this ).append( view.el );
					} else {
						$( this ).append( view );
					}
				}, {
					title: title,
					has_custom_box: true,
				}, 'md', 'inline');
			},
		});

		//init after jquery ready
		jQuery( function() {
			new Popups();
		});
	});
})(jQuery);


formintorjs.define('text!tpl/popups.html',[],function () { return '<div>\r\n\r\n\t<!-- Base Structure -->\r\n\t<script type="text/template" id="popup-tpl">\r\n\r\n\t\t<div class="sui-modal">\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="dialog"\r\n\t\t\t\tid="forminator-popup"\r\n\t\t\t\tclass="sui-modal-content"\r\n\t\t\t\taria-modal="true"\r\n\t\t\t\taria-labelledby="forminator-popup__title"\r\n\t\t\t\taria-describedby="forminator-popup__description"\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-box"></div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- Modal Header: Center-aligned title with floating close button -->\r\n\t<script type="text/template" id="popup-header-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close" data-modal-close>\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title sui-lg">{{ title }}</h3>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<!-- Modal Header: Inline title and close button -->\r\n\t<script type="text/template" id="popup-header-inline-tpl">\r\n\r\n\t\t<div class="sui-box-header">\r\n\r\n\t\t\t<h3 id="forminator-popup__title" class="sui-box-title">{{ title }}</h3>\r\n\r\n\t\t\t<div class="sui-actions-right">\r\n\r\n\t\t\t\t<button class="sui-button-icon forminator-popup-close" data-modal-close>\r\n\t\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t\t</button>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-integration-tpl">\r\n\r\n\t\t<div class="sui-modal sui-modal-sm">\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="dialog"\r\n\t\t\t\tid="forminator-integration-popup"\r\n\t\t\t\tclass="sui-modal-content"\r\n\t\t\t\taria-modal="true"\r\n\t\t\t\taria-labelledby="forminator-integration-popup__title"\r\n\t\t\t\taria-describedby="forminator-integration-popup__description"\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-box"></div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-integration-content-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<figure class="sui-box-logo" aria-hidden="true">\r\n\r\n\t\t\t\t<img\r\n\t\t\t\t\tsrc="{{ image }}"\r\n\t\t\t\t\tsrcset="{{ image }} 1x, {{ image_x2 }} 2x"\r\n\t\t\t\t\talt="{{ title }}"\r\n\t\t\t\t/>\r\n\r\n\t\t\t</figure>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--left forminator-addon-back" style="display: none;">\r\n\t\t\t\t<span class="sui-icon-chevron-left sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Back</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-integration-close" data-modal-close>\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">Close</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<div class="forminator-integration-popup__header"></div>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="forminator-integration-popup__body sui-box-body"></div>\r\n\r\n\t\t<div class="forminator-integration-popup__footer sui-box-footer sui-flatten sui-content-separated"></div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-loader-tpl">\r\n\r\n\t\t<p style="margin: 0; text-align: center;" aria-hidden="true"><span class="sui-icon-loader sui-md sui-loading"></span></p>\r\n\t\t<p class="sui-screen-reader-text">Loading content...</p>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-stripe-tpl">\r\n\r\n\t\t<div class="sui-modal sui-modal-sm">\r\n\r\n\t\t\t<div\r\n\t\t\t\trole="dialog"\r\n\t\t\t\tid="forminator-stripe-popup"\r\n\t\t\t\tclass="sui-modal-content"\r\n\t\t\t\taria-modal="true"\r\n\t\t\t\taria-labelledby="forminator-stripe-popup__title"\r\n\t\t\t\taria-describedby=""\r\n\t\t\t>\r\n\r\n\t\t\t\t<div class="sui-box"></div>\r\n\r\n\t\t\t</div>\r\n\r\n\t\t</div>\r\n\r\n\t</script>\r\n\r\n\t<script type="text/template" id="popup-stripe-content-tpl">\r\n\r\n\t\t<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">\r\n\r\n\t\t\t<figure class="sui-box-logo" aria-hidden="true">\r\n\t\t\t\t<img src="{{ image }}" srcset="{{ image }} 1x, {{ image_x2 }} 2x" alt="{{ title }}" />\r\n\t\t\t</figure>\r\n\r\n\t\t\t<button class="sui-button-icon sui-button-float--right forminator-popup-close">\r\n\t\t\t\t<span class="sui-icon-close sui-md" aria-hidden="true"></span>\r\n\t\t\t\t<span class="sui-screen-reader-text">{{ Forminator.l10n.popup.close_label }}</span>\r\n\t\t\t</button>\r\n\r\n\t\t\t<h3 id="forminator-stripe-popup__title" class="sui-box-title sui-lg" style="overflow: initial; display: none; white-space: normal; text-overflow: initial;">{{ title }}</h3>\r\n\r\n\t\t</div>\r\n\r\n\t\t<div class="sui-box-body sui-spacing-top--10"></div>\r\n\r\n\t\t<div class="sui-box-footer sui-flatten sui-content-right"></div>\r\n\r\n\t</script>\r\n\r\n</div>\r\n';});

(function ($) {
	formintorjs.define('admin/addons/view',[
		'text!tpl/popups.html'
	], function( popupsTpl ) {
		return Backbone.View.extend({
			className: 'wpmudev-section--integrations',

			loaderTpl: Forminator.Utils.template( $( popupsTpl ).find( '#popup-loader-tpl' ).html() ),

			model: {},

			events: {
				"click .forminator-addon-connect": "connect_addon",
				"click .forminator-addon-disconnect" : "disconnect_addon",
				"click .forminator-addon-form-disconnect" : "form_disconnect_addon",
				"click .forminator-addon-next" : "submit_next_step",
				"click .forminator-addon-back" : "go_prev_step",
				"click .forminator-addon-finish" : "finish_steps"
			},

			initialize: function( options ) {
				this.slug      = options.slug;
				this.nonce     = options.nonce;
				this.action    = options.action;
				this.form_id   = options.form_id;
				this.multi_id  = options.multi_id;
				this.global_id = options.global_id;
				this.step      = 0;
				this.next_step = false;
				this.prev_step = false;
				this.scrollbar_width = this.get_scrollbar_width();

				var self = this;

				// Add closing event
				this.$el.find( ".forminator-integration-close, .forminator-addon-close" ).on("click", function () {
					self.close(self);
				});

				return this.render();
			},

			render: function() {
				var data = {};

				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = {};
				data.data.slug = this.slug;
				data.data.step = this.step;
				data.data.current_step = this.step;
				data.data.global_id = this.global_id;
				if (this.form_id) {
					data.data.form_id = this.form_id;
				}
				if (this.multi_id) {
					data.data.multi_id = this.multi_id;
				}

				this.request( data, false, true );
			},

			request: function ( data, close, loader ) {
				var self            = this,
				    function_params = {
					    data  : data,
					    close : close,
					    loader: loader,
				    };

				if ( loader ) {
					this.$el.find(".forminator-integration-popup__header").html( '' );
					this.$el.find(".forminator-integration-popup__body").html( this.loaderTpl() );
					this.$el.find(".forminator-integration-popup__footer").html( '' );
				}

				this.$el.find(".sui-button:not(.disable-loader)").addClass("sui-button-onload");

				this.ajax = $.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: data
				})
				.done(function (result) {
					if (result && result.success) {
						// Reset hidden elements.
						self.render_reset();

						// Render popup body
						self.render_body( result );

						// Render popup footer
						self.render_footer( result );

						// Hide elements when empty
						self.hide_elements();

						// Shorten result data
						var result_data = result.data.data;

						self.on_render( result_data );

						self.$el.find(".sui-button").removeClass("sui-button-onload");

						// Handle close modal
						if( close || ( !_.isUndefined( result_data.is_close ) && result_data.is_close ) ) {
							self.close( self );
						}

						// Add closing event
						self.$el.find( ".forminator-addon-close" ).on("click", function () {
							self.close(self);
						});

						// Handle notifications
						if( !_.isUndefined( result_data.notification ) &&
							!_.isUndefined( result_data.notification.type ) &&
							!_.isUndefined( result_data.notification.text ) ) {

							Forminator.Notification.open( result_data.notification.type, result_data.notification.text, 4000 );
						}

						// Handle back button
						if( !_.isUndefined( result_data.has_back ) ) {
							if( result_data.has_back ) {
								self.$el.find('.forminator-addon-back').show();
							} else {
								self.$el.find('.forminator-addon-back').hide();
							}
						} else {
							self.$el.find('.forminator-addon-back').hide();
						}

						if (result_data.is_poll) {
							setTimeout(self.request(function_params.data, function_params.close, function_params.loader), 5000);
						}

						//check the height
						var $popup_box = $( "#forminator-integration-popup .sui-box" ),
						    $popup_box_height = $popup_box.height(),
						    $window_height = $(window).height();

						// scrollbar appear
						if ($popup_box_height > $window_height) {
							// make scrollbar clickable
							$("#forminator-integration-popup .sui-dialog-overlay").css('right', self.scrollbar_width + 'px');
						} else {
							$("#forminator-integration-popup .sui-dialog-overlay").css('right', 0);
						}
					}
				});

				//remove the preloader
				this.ajax.always(function () {
					self.$el.find(".fui-loading-dialog").remove();
				});
			},

			render_reset: function() {
				var integration_body = $( '.forminator-integration-popup__body' ),
					integration_footer = $( '.forminator-integration-popup__footer' );

				// Show hidden body.
				if ( integration_body.is( ':hidden' ) ) {
					integration_body.css( 'display', '' );
				}

				// Show empty footer.
				if ( integration_footer.is( ':hidden' ) ) {
					integration_footer.css( 'display', '' );
				}
			},

			render_body: function ( result ) {
				// Render content inside `body`.
				this.$el.find(".forminator-integration-popup__body").html( result.data.data.html );

				// Append header elements to `sui-box-header`.
				var integration_header = this.$el.find( '.forminator-integration-popup__body .forminator-integration-popup__header' ).remove();
				if ( integration_header.length > 0 ) {
					this.$el.find( '.forminator-integration-popup__header' ).html( integration_header.html() );
				}
			},

			render_footer: function ( result ) {
				var self = this,
					buttons  = result.data.data.buttons
				;

				// Clear footer from previous buttons
				self.$el.find(".sui-box-footer").html('');

				// Append footer elements from `body`.
				var integration_footer = this.$el.find( '.forminator-integration-popup__body .forminator-integration-popup__footer-temp' ).remove();
				if ( integration_footer.length > 0 ) {
					this.$el.find( '.forminator-integration-popup__footer' ).html( integration_footer.html() );
				}

				// Append buttons from php template.
				_.each( buttons, function (button) {
					self.$el.find( '.sui-box-footer' ).append( button.markup );
				});

				// Align buttons.
				self.$el.find( '.sui-box-footer' )
					.removeClass( 'sui-content-center' )
					.addClass( 'sui-content-separated' );

				if ( self.$el.find( '.sui-box-footer' ).children( '.forminator-integration-popup__close' ).length > 0 ) {
					self.$el.find( '.sui-box-footer' )
						.removeClass( 'sui-content-separated' )
						.addClass( 'sui-content-center' );
				}
			},

			hide_elements: function() {
				var integration_body = $( '.forminator-integration-popup__body' ),
					integration_footer = $( '.forminator-integration-popup__footer' ),
					integration_content = integration_body.html(),
					integration_footer_html = integration_footer.html();

				// Hide empty body.
				if ( ! integration_content.trim().length ) {
					integration_body.hide();
				}

				// Hide empty footer.
				if ( ! integration_footer_html.trim().length ) {
					integration_footer.hide();
				}
			},

			on_render: function ( result ) {
				this.delegateEvents();

				// Delegate SUI events
				Forminator.Utils.sui_delegate_events();
				// multi select (Tags)
				Forminator.Utils.forminator_select2_tags( this.$el, {} );

				// Update current step
				if( !_.isUndefined( result.forminator_addon_current_step ) ) {
					this.step = +result.forminator_addon_current_step;
				}

				// Update has next step
				if( !_.isUndefined( result.forminator_addon_has_next_step ) ) {
					this.next_step = result.forminator_addon_has_next_step;
				}

				// Update has prev step
				if( !_.isUndefined( result.forminator_addon_has_prev_step ) ) {
					this.prev_step = result.forminator_addon_has_prev_step;
				}
			},

			get_step: function () {
				if( this.next_step ) {
					return this.step + 1;
				}

				return this.step;
			},

			get_prev_step: function () {
				if( this.prev_step ) {
					return this.step - 1;
				}

				return this.step;
			},

			connect_addon: function ( e ) {
				var data     = {},
					form     = this.$el.find( 'form' ),
					params   = {
						'slug' : this.slug,
						'step' : this.get_step(),
						'global_id' : this.global_id,
						'current_step' : this.step,
					},
					formData = form.serialize()
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}
				if (this.multi_id) {
					params.multi_id = this.multi_id;
				}

				formData = formData + '&' + $.param( params );
				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = formData;

				this.request( data, false, false );
			},

			submit_next_step: function(e) {
				var data     = {},
				    form     = this.$el.find( 'form' ),
				    params   = {
					    'slug' : this.slug,
					    'step' : this.get_step(),
						'global_id' : this.global_id,
					    'current_step' : this.step,
				    },
				    formData = form.serialize()
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}

				formData = formData + '&' + $.param( params );
				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = formData;

				this.request( data, false, false );
			},

			go_prev_step: function(e) {
				var data     = {},
				    params   = {
					    'slug' : this.slug,
					    'step' : this.get_prev_step(),
						'global_id' : this.global_id,
					    'current_step' : this.step,
				    }
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}
				if (this.multi_id) {
					params.multi_id = this.multi_id;
				}

				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = params;

				this.request( data, false, false );
			},

			finish_steps: function ( e ) {
				var data     = {},
					form     = this.$el.find( 'form' ),
					params   = {
						'slug' : this.slug,
						'step' : this.get_step(),
						'global_id' : this.global_id,
						'current_step' : this.step,
					},
					formData = form.serialize()
				;

				if (this.form_id) {
					params.form_id = this.form_id;
				}
				if (this.multi_id) {
					params.multi_id = this.multi_id;
				}

				formData = formData + '&' + $.param( params );
				data.action = this.action;
				data._ajax_nonce = this.nonce;
				data.data = formData;

				this.request( data, false, false );
			},

			disconnect_addon: function ( e ) {
				var data = {};
				data.action = 'forminator_addon_deactivate';
				data._ajax_nonce = this.nonce;
				data.data = {};
				data.data.slug = this.slug;
				data.data.global_id = this.global_id;

				this.request( data, true, false );
			},

			form_disconnect_addon: function ( e ) {
				var data = {};
				data.action = 'forminator_addon_deactivate_for_module';
				data._ajax_nonce = this.nonce;
				data.data = {};
				data.data.slug = this.slug;
				data.data.form_id = this.form_id;
				data.data.form_type = 'form';
				if (this.multi_id) {
					data.data.multi_id = this.multi_id;
				}

				this.request( data, true, false );
			},

			close: function( self ) {
				// Kill AJAX hearbeat
				self.ajax.abort();

				// Remove the view
				self.remove();

				// Close the modal
				Forminator.Integrations_Popup.close();

				// Refrest add-on list
				Forminator.Events.trigger( "forminator:addons:reload" );
			},

			get_scrollbar_width: function () {
				//https://github.com/brandonaaron/jquery-getscrollbarwidth/
				var scrollbar_width = 0;
				if ( navigator.userAgent.match( "MSIE" ) ) {
					var $textarea1 = $('<textarea cols="10" rows="2"></textarea>')
						    .css({ position: 'absolute', top: -1000, left: -1000 }).appendTo('body'),
					    $textarea2 = $('<textarea cols="10" rows="2" style="overflow: hidden;"></textarea>')
						    .css({ position: 'absolute', top: -1000, left: -1000 }).appendTo('body');
					scrollbar_width = $textarea1.width() - $textarea2.width();
					$textarea1.add($textarea2).remove();
				} else {
					var $div = $('<div />')
						.css({ width: 100, height: 100, overflow: 'auto', position: 'absolute', top: -1000, left: -1000 })
						.prependTo('body').append('<div />').find('div')
						.css({ width: '100%', height: 200 });
					scrollbar_width = 100 - $div.width();
					$div.parent().remove();
				}
				return scrollbar_width;
			}
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/addons/addons',[
		'admin/addons/view'
	], function (SettingsView) {
		var Addons = Backbone.View.extend({
			el: '.sui-wrap.wpmudev-forminator-forminator-integrations',

			currentTab: 'forminator-integrations',

			events: {
				"change .forminator-addon-toggle-enabled" : "toggle_state",
				"click .connect-integration" : "connect_integration",
				"click .forminator-integrations-wrapper .sui-vertical-tab a" : "go_to_tab",
				"change .forminator-integrations-wrapper .sui-sidenav-hide-lg select" : "go_to_tab",
				"change .forminator-integrations-wrapper .sui-sidenav-hide-lg.integration-nav" : "go_to_tab",
				"keyup input.sui-form-control": "required_settings"
			},

			initialize: function( options ) {
				if( $( this.el ).length > 0 ) {
					this.listenTo( Forminator.Events, "forminator:addons:reload", this.render_addons_page );
					return this.render();
				}
			},

			render: function () {
				// Check if addons wrapper exist
				this.render_addons_page();

				this.update_tab();
			},

			render_addons_page: function () {
				var self = this,
					data = {}
				;

				this.$el.find( '#forminator-integrations-display' ).html(
					'<div role="alert" id="forminator-addons-preloader" class="sui-notice sui-active" style="display: block;" aria-live="assertive">' +
						'<div class="sui-notice-content">' +
							'<div class="sui-notice-message">' +
								'<span class="sui-notice-icon sui-icon-loader sui-loading" aria-hidden="true"></span>' +
								'<p>Fetching integration list…</p>' +
							'</div>' +
						'</div>' +
					'</div>'
				);

				data.action      = 'forminator_addon_get_addons';
				data._ajax_nonce = Forminator.Data.addonNonce;
				data.data = {};

				var ajax = $.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: data
				})
				.done(function (result) {
					if (result && result.success) {
						self.$el.find( '#forminator-integrations-page' ).html( result.data.data );
					}
				});

				//remove the preloader
				ajax.always(function () {
					self.$el.find("#forminator-addons-preloader").remove();
				});
			},

			connect_integration: function (e) {
				e.preventDefault();

				var $target = $(e.target);

				if (!$target.hasClass('connect-integration')) {
					$target = $target.closest('.connect-integration');
				}

				var nonce    = $target.data('nonce'),
				    slug     = $target.data('slug'),
				    global_id= $target.data('multi-global-id'),
				    title    = $target.data('title'),
				    image    = $target.data('image'),
				    image_x2 = $target.data('imagex2'),
				    action   = $target.data('action'),
				    form_id  = $target.data('form-id'),
				    multi_id = $target.data('multi-id')
				;

				Forminator.Integrations_Popup.open(function () {
					var view = new SettingsView({
						slug    : slug,
						nonce   : nonce,
						action  : action,
						form_id : form_id,
						multi_id : multi_id,
						global_id : global_id,
						el      : $(this)
					});
				}, {
					title   : title,
					image   : image,
					image_x2: image_x2,
				});
			},

			go_to_tab: function (e) {
				e.preventDefault();
				var target = $(e.target),
					href   = target.attr('href'),
					tab_id = '';
				if (!_.isUndefined(href)) {
					tab_id = href.replace('#', '', href);
				} else {
					var val = target.val();
					tab_id  = val;
				}

				if (!_.isEmpty(tab_id)) {
					this.currentTab = tab_id;
				}

				this.update_tab();

				e.stopPropagation();
			},

			update_tab_select: function() {

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-integrations' ) ) {
					this.$el.find('.sui-sidenav-hide-lg select').val(this.currentTab);
					this.$el.find('.sui-sidenav-hide-lg select').trigger('sui:change');
				}
			},

			update_tab: function () {

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-integrations' ) ) {

					this.clear_tabs();

					this.$el.find( '[data-tab-id=' + this.currentTab + ']' ).addClass( 'current' );
					this.$el.find( '.wpmudev-settings--box#' + this.currentTab ).show();
				}
			},

			clear_tabs: function () {

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-integrations' ) ) {
					this.$el.find( '.sui-vertical-tab ').removeClass( 'current' );
					this.$el.find( '.wpmudev-settings--box' ).hide();
				}
			},

			required_settings: function( e ) {

				var input = $( e.target ),
					field = input.parent(),
					error = field.find( '.sui-error-message' )
					;

				var tabWrapper = input.closest( 'div[data-nav]' ),
					tabFooter  = tabWrapper.find( '.sui-box-footer' ),
					saveButton = tabFooter.find( '.wpmudev-action-done' )
					;

				if ( this.$el.hasClass( 'wpmudev-forminator-forminator-settings' ) ) {

					if ( input.hasClass( 'forminator-required' ) && ! input.val() ) {

						if ( field.hasClass( 'sui-form-field' ) ) {
							field.addClass( 'sui-form-field-error' );
							error.show();
						}
					}

					if ( input.hasClass( 'forminator-required' ) && input.val() ) {

						if ( field.hasClass( 'sui-form-field' ) ) {
							field.removeClass( 'sui-form-field-error' );
							error.hide();
						}
					}

					if ( tabWrapper.find( 'input.sui-form-control' ).hasClass( 'forminator-required' ) ) {

						if ( tabWrapper.find( 'div.sui-form-field-error' ).length === 0 ) {
							saveButton.prop( 'disabled', false );
						} else {
							saveButton.prop( 'disabled', true );
						}
					}
				}

				e.stopPropagation();

			},
		});

		//init after jquery ready
		jQuery(function () {
			new Addons();
		});
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/addons-page',[
	], function() {
		var AddonsPage = Backbone.View.extend({
			el: '.wpmudev-forminator-forminator-addons',
			events: {
				"click button.addons-actions": "addons_actions",
				"click a.addons-actions": "addons_actions",
				"click .sui-dialog-close": "close",
				"click .addons-modal-close": "close",
				"click .addons-page-details": "open_addons_detail",
			},
			initialize: function () {
				var self = this;
				// only trigger on settings page
				if (!$('.wpmudev-forminator-forminator-addons').length) {
					return;
				}
			},

			addons_actions: function ( e ) {
				var self = this,
					$target = $( e.target ),
					request_data = {},
					nonce = $target.data('nonce'),
					actions = $target.data('action'),
					popup = $target.data('popup'),
					pid = $target.data('addon');

				request_data.action = 'forminator_' + actions;
				request_data.pid = pid;
				request_data._ajax_nonce = nonce;

				$target.addClass("sui-button-onload");
				self.$el.find(".sui-button.addons-actions:not(.disable-loader)")
					.attr( 'disabled', true );

				$.post({
					url: Forminator.Data.ajaxUrl,
					type: 'post',
					data: request_data
				}).done(function ( result ) {

					if ( 'undefined' !== typeof result.data.error ) {
						self.show_notification( result.data.error.message, 'error' );
						return false;
					}

					if ( 'addons-install' === actions ) {
						setTimeout(function () {
							self.active_popup( pid, 'show', 'forminator-activate-popup' );
							self.$el.find( '.sui-tab-content .addons-' + pid )
								.not( this )
								.replaceWith( result.data.html );
							self.loader_remove();
						}, 1000);

					} else {
						self.show_notification( result.data.message, 'success' );
						self.$el.find( '.sui-tab-content .addons-' + pid )
							.not( this )
							.replaceWith( result.data.html );

						if ( 'addons-update' === actions ) {

							var detailPopup = self.$el.find( '#forminator-modal-addons-details-' + pid );
							self.$el.find( '#updates-addons-content .addons-' + pid ).remove();

							var updateCounter = self.$el.find('#updates-addons-content .sui-col-md-6').length;
							if ( updateCounter < 1 ) {
								self.$el.find( '#updates-addons span.sui-tag').removeClass('sui-tag-yellow');
							}
							self.$el.find( '#updates-addons span.sui-tag').html( updateCounter );

							detailPopup.find( '.forminator-details-header--tags span.addons-update-tag').remove();

							var version = $target.data('version');
							detailPopup.find( '.forminator-details-header--tags span.addons-version').html( version );

							detailPopup.find( '.forminator-details-header button.addons-actions').remove();
							$target.remove();
						}
						if ( popup ) {
							location.reload();
						}
                    }
				}).fail( function () {
					self.show_notification( Forminator.l10n.commons.error_message, 'error' );
				});

				return false;
			},

			close: function( e ) {
				e.preventDefault();

				var $target = $( e.target ),
					pid = $target.data('addon'),
					element = $target.data('element');
				this.active_popup( pid, 'hide', element );
			},

			loader_remove: function () {
				this.$el.find(".sui-button.addons-actions:not(.disable-loader)")
					.removeClass("sui-button-onload")
					.attr( 'disabled', false );
			},

			show_notification: function ( message, status ) {
				var error_message = 'undefined' !== typeof message
					? message :
					Forminator.l10n.commons.error_message;
				Forminator.Notification.open( status, error_message, 4000 );
				this.loader_remove();
			},

			active_popup: function ( pid, status, element ) {
				var modalId = element + '-' + pid,
					focusAfterClosed = 'forminator-addon-' + pid + '__card';

				if ( 'show' === status ) {
					SUI.openModal(
						modalId,
						focusAfterClosed
					);
				} else {
					SUI.closeModal();
				}
			},

			open_addons_detail: function ( e ) {
				var self = this,
					$target = $( e.target ),
					pid = $target.data('form-id');
				self.active_popup( pid, 'show', 'forminator-modal-addons-details' );
			}
		});

		var AddonsPage = new AddonsPage();

		return AddonsPage;
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/views',[
		'admin/dashboard',
		'admin/settings-page',
		'admin/popups',
		'admin/addons/addons',
		'admin/addons-page',
	], function( Dashboard, SettingsPage, Popups, Addons, AddonsPage ) {
		return {
			"Views": {
				"Dashboard": Dashboard,
				"SettingsPage": SettingsPage,
				"Popups": Popups,
				"AddonsPage": AddonsPage,
			}
		}
	});
})(jQuery);

(function ($) {
	formintorjs.define('admin/application',[
		'admin/views',
	], function ( Views )  {
		_.extend(Forminator, Views);

		var Application = new ( Backbone.Router.extend({
			app: false,
			data: false,
			layout: false,
			module_id: null,

			routes: {
				""              : "run",
				"*path"         : "run"
			},

			events: {},

			init: function () {
				// Load Forminator Data only first time
				if( ! this.data ) {
					this.app = Forminator.Data.application || false;

					// Retrieve current data
					this.data = {};

					return false;
				}
			},

			run: function (id) {

				this.init();

				this.module_id = id;
			},
		}));

		return Application;
	});

})(jQuery);

formintorjs.define( 'jquery', [], function () {
	return jQuery;
});

formintorjs.define( 'forminator_global_data', function() {
   var data = forminatorData;
	return data;
});

formintorjs.define( 'forminator_language', function() {
   var l10n = forminatorl10n;
	return l10n;
});

var Forminator = window.Forminator || {};
Forminator.Events = {};
Forminator.Data = {};
Forminator.l10n = {};
Forminator.openPreset = function( presetId, notice ) {
	// replace preset param to the new one.
	var regEx = /([?&]preset)=([^&]*)/g,
		newUrl = window.location.href.replace( regEx, '$1=' + presetId );

	// if it didn't have preset param - add it.
	if ( newUrl === window.location.href ) {
		newUrl += '&preset=' + presetId;
	}

	if ( notice ) {
		newUrl += '&forminator_notice=' + notice;
	}

	window.location.href = newUrl;
};

formintorjs.require.config({
	baseUrl: ".",
	paths: {
		"js": ".",
		"admin": "admin",
	},
	shim: {
		'backbone': {
			//These script dependencies should be loaded before loading
			//backbone.js
			deps: [ 'underscore', 'jquery', 'forminator_global_data', 'forminator_language' ],
			//Once loaded, use the global 'Backbone' as the
			//module value.
			exports: 'Backbone'
		},
		'underscore': {
			exports: '_'
		}
	},
	"waitSeconds": 60,
});

formintorjs.require([  'admin/utils' ], function ( Utils ) {
	// Fix Underscore templating to Mustache style
	_.templateSettings = {
		evaluate : /\{\[([\s\S]+?)\]\}/g,
		interpolate : /\{\{([\s\S]+?)\}\}/g
	};

	_.extend( Forminator.Data, forminatorData );
	_.extend( Forminator.l10n, forminatorl10n );
	_.extend( Forminator, Utils );
	_.extend(Forminator.Events, Backbone.Events);

	formintorjs.require([ 'admin/application' ], function ( Application ) {
		jQuery( function() {
			_.extend(Forminator, Application);

			Forminator.Events.trigger("application:booted");
			Backbone.history.start();
		});
	});
});

formintorjs.define("admin/setup", function(){});


formintorjs.define("main", function(){});
