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

// Fallback: suppress the specific deprecation warning if any code still reaches the old wrapper
// (vite pre-bundled deps may hold old closure). This is safe and does not hide other warnings.
const originalWarn = console.warn;
console.warn = (...args) => {
    const first = args[0];
    if (typeof first === "string" && first.includes("Deprecated use of _flat")) {
        return;
    }
    originalWarn.apply(console, args);
};

export default L;
