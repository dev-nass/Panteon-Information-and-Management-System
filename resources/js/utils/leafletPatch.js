import L from "leaflet";

/**
 * Patch deprecated L.Polyline._flat -> L.LineUtil.isFlat
 *
 * Leaflet 1.9.4 deprecates L.Polyline._flat with console.warn:
 *   "Deprecated use of _flat, please use L.LineUtil.isFlat instead."
 *   (leaflet.js:4253)
 *
 * leaflet-draw 1.0.4 still calls L.Polyline._flat at:
 *   leaflet.draw.js:247 & 270 -> _defaultShape()
 * which triggers warning on every edit of phase/cluster polygon.
 *
 * This patch replaces the deprecated wrapper with the non-deprecated
 * implementation before leaflet-draw is loaded / used.
 */
if (L.LineUtil && typeof L.LineUtil.isFlat === "function") {
    const isFlat = L.LineUtil.isFlat;

    // Polyline static (used by leaflet-draw Edit.Poly)
    if (L.Polyline) {
        L.Polyline._flat = isFlat;
    }

    // LineUtil wrappers (also warn)
    if (L.LineUtil._flat && L.LineUtil._flat !== isFlat) {
        L.LineUtil._flat = isFlat;
    }
}

// Fallback: suppress specific deprecation + upstream Google Reporting-Endpoints spam
// (vite pre-bundled deps may hold old closure, and Google tiles send invalid Report-To JSON
// that Firefox 130+ logs per tile as "Reporting Header: invalid JSON... lyrs=s&x=..." )
const originalWarn = console.warn;
const originalError = console.error;
const shouldSuppress = (args) => {
    const first = args[0];
    if (typeof first !== "string") return false;
    return (
        first.includes("Deprecated use of _flat") ||
        first.includes("Reporting Header") ||
        first.includes("invalid JSON value received") ||
        first.includes("lyrs=s&x=") ||
        first.includes("OpaqueResponseBlocking") ||
        first.includes("NS_ERROR_DOM_NETWORK_ERR") ||
        first.includes("mt1.google.com/vt") ||
        first.includes("mt2.google.com/vt") ||
        first.includes("mt3.google.com/vt") ||
        first.includes("mt0.google.com/vt")
    );
};
console.warn = (...args) => {
    if (shouldSuppress(args)) return;
    originalWarn.apply(console, args);
};
console.error = (...args) => {
    if (shouldSuppress(args)) return;
    originalError.apply(console, args);
};

// Also suppress ReportingObserver reports from Google tiles (Firefox/Brave)
if (typeof ReportingObserver !== "undefined") {
    const OriginalReportingObserver = ReportingObserver;
    // eslint-disable-next-line no-global-assign
    window.ReportingObserver = function (callback, options) {
        const wrapped = (reports, observer) => {
            const filtered = reports.filter(
                (r) =>
                    !(
                        r.body?.message?.includes("Reporting Header") ||
                        r.body?.message?.includes("lyrs=s")
                    ),
            );
            if (filtered.length) callback(filtered, observer);
        };
        return new OriginalReportingObserver(wrapped, options);
    };
}

export default L;
