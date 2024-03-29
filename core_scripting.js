/**
 * Utility Functions!
 * This JavaScript file contains a collection of useful functions for various tasks.
 * Feel free to use this file in your projects, but please be aware that it comes with no warranties or guarantees. You are responsible for testing and using these functions at your own risk.
 * @author Cyril Neveu
 * @link https://github.com/ripley2459/r
 * @version 1
 */

/****************************************************
 * Event
 */

class REventsDispatcher {
    constructor() {
        this.events = {};
    }

    dispatchEvent(eventName, data) {
        const event = this.events[eventName];
        if (event) {
            event.fire(data);
        }
    }

    addEventListener(eventName, callback) {
        let event = this.events[eventName];
        if (!event) {
            event = new REvent(eventName);
            this.events[eventName] = event;
        }

        event.registerCallback(callback);
    }

    removeEventListener(eventName, callback) {
        const event = this.events[eventName];
        if (event && event.callbacks.indexOf(callback) > -1) {
            event.unregisterCallback(callback);
            if (event.callbacks.length === 0)
                delete this.events[eventName];
        }
    }
}

class REvent {
    constructor(eventName) {
        this.eventName = eventName;
        this.callbacks = [];
    }

    registerCallback(callback) {
        this.callbacks.push(callback);
    }

    unregisterCallback(callback) {
        const index = this.callbacks.indexOf(callback);
        if (index > -1)
            this.callbacks.splice(index, 1);
    }

    fire(data) {
        const callbacks = this.callbacks.slice(0);
        callbacks.forEach((callback) => callback(data));
    }
}

const REvents = new REventsDispatcher();

/****************************************************
 * URL manipulation
 */

/**
 * Switch between valueA and valueB for the given parameter name from the current URL.
 * @param name The name used for the parameter.
 * @param valueA The value A to set.
 * @param valueB The value B to set.
 */
function toggleBetweenParams(name, valueA, valueB) {
    let sP = new URL(document.URL).searchParams;
    if (sP.get(name) === valueA)
        setParam(name, valueB);
    else setParam(name, valueA);
}

/**
 * Set or remove a parameter from the current URL.
 * @param name The name used for the parameter.
 * @param value The value to set.
 */
function toggleParam(name, value) {
    let sP = new URL(document.URL).searchParams;
    if (sP.get(name) == value)
        removeParam(name);
    else setParam(name, value);
}

/**
 * Set (or replace) a parameter to the current URL. If the value is null, remove the eventually existing one.
 * @param {string} name The name used for the parameter.
 * @param value The value to set (or replace).
 */
function setParam(name, value) {
    if (!value) {
        removeParam(name);
        return;
    }

    let newURL = new URL(document.URL);
    newURL.searchParams.set(name, value);
    window.history.replaceState({id: "100"}, name, newURL);
    REvents.dispatchEvent("onURLModified");
}

/**
 * Add an array as parameter to the current URL.
 * @param {string} name The name used for the parameter.
 * @param {array} array The array to encode inside the URL.
 */
function setParams(name, array) {
    if (array.length <= 0) {
        setParam(name, null);
        return;
    }

    let value = encodeURIComponent(JSON.stringify(array));
    setParam(name, value);
}

/**
 * Remove a parameter from the current URL.
 * @param {string} name The parameter name to remove.
 */
function removeParam(name) {
    let newURL = new URL(document.URL);
    if (newURL.searchParams.get(name))
        newURL.searchParams.delete(name);
    window.history.replaceState({id: "100"}, name, newURL);
    REvents.dispatchEvent("onURLModified");
}