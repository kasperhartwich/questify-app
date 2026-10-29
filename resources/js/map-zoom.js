// Auto-zoom for the play screen map (QU-37): the closer the player gets to the
// checkpoint, the further the map zooms in. Pure functions, no Leaflet, so the
// table below is easy to tune on a walk and to test.

export const MAX_ZOOM = 19;
export const MIN_ZOOM = 3;
export const FALLBACK_ZOOM = 16;

// [distance in metres, zoom]. Linearly interpolated between rows; beyond the
// first row the zoom stays at its value.
export const ZOOM_TABLE = [
    [1500, 14],
    [1000, 15],
    [500, 16],
    [200, 17],
    [75, 18],
    [30, MAX_ZOOM],
];

export const NEAR_METERS = 30;
export const NEAR_RELEASE_METERS = 60;
export const PADDING_PX = 60;
export const HYSTERESIS_LEVELS = 0.5;
export const MIN_INTERVAL_MS = 3000;
export const PAUSE_MS = 15000;
export const ANIMATION_SECONDS = 0.8;

const EARTH_METERS_PER_PIXEL_AT_ZOOM_0 = 156543.03392;

export function haversineMeters(lat1, lng1, lat2, lng2) {
    const rad = Math.PI / 180;
    const dLat = (lat2 - lat1) * rad;
    const dLng = (lng2 - lng1) * rad;
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLng / 2) ** 2;

    return 2 * 6371000 * Math.asin(Math.min(1, Math.sqrt(a)));
}

/** Zoom at which `meters` span `pixels` on screen at the given latitude. */
export function zoomToFit(meters, pixels, lat = 0) {
    if (meters <= 0 || pixels <= 0) {
        return MAX_ZOOM;
    }

    const metersPerPixel = meters / pixels;

    return Math.log2((EARTH_METERS_PER_PIXEL_AT_ZOOM_0 * Math.cos(lat * Math.PI / 180)) / metersPerPixel);
}

/** The table zoom for a distance, interpolated between rows. */
export function tableZoom(distance) {
    if (distance >= ZOOM_TABLE[0][0]) {
        return ZOOM_TABLE[0][1];
    }

    for (let i = 1; i < ZOOM_TABLE.length; i++) {
        const [farMeters, farZoom] = ZOOM_TABLE[i - 1];
        const [nearMeters, nearZoom] = ZOOM_TABLE[i];

        if (distance >= nearMeters) {
            const t = (farMeters - distance) / (farMeters - nearMeters);

            return farZoom + t * (nearZoom - farZoom);
        }
    }

    return MAX_ZOOM;
}

/**
 * Target zoom for a player `distance` metres from the checkpoint.
 *
 * Inside the arrival radius + 5 m, or within 30 m, it is the maximum. Otherwise
 * it is the table zoom, lowered so that the checkpoint stays on screen with
 * padding, and (when the GPS accuracy circle is larger than the distance) so
 * that the accuracy circle still fits: an uncertain 25 m fix must not look like
 * a precise 5 m one.
 *
 * @param {number} distance metres to the checkpoint
 * @param {number|null} accuracy GPS accuracy in metres, if known
 * @param {number} viewportPx the map's shorter side in pixels
 * @param {number} radius the checkpoint's arrival radius in metres
 * @param {number} lat latitude, for the metres-per-pixel scale
 */
export function zoomForDistance(distance, accuracy, viewportPx, radius = 10, lat = 0) {
    const room = Math.max(viewportPx - 2 * PADDING_PX, 1);

    let zoom = (distance <= NEAR_METERS || distance <= radius + 5)
        ? MAX_ZOOM
        : tableZoom(distance);

    // Player and checkpoint both stay in view.
    zoom = Math.min(zoom, zoomToFit(distance, room, lat));

    if (accuracy && accuracy > distance) {
        zoom = Math.min(zoom, zoomToFit(accuracy * 2, room, lat));
    }

    return Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, zoom));
}

/**
 * The state around zoomForDistance: hysteresis, a rate limit, the near-lock and
 * the pause after the player moves the map by hand. `now` is in milliseconds.
 */
export class AutoZoom {
    constructor(initialZoom = FALLBACK_ZOOM) {
        this.zoom = initialZoom;
        this.lastChangeAt = -Infinity;
        this.pausedUntil = -Infinity;
        this.nearLocked = false;
    }

    isPaused(now) {
        return now < this.pausedUntil;
    }

    /** The player panned or pinched: leave the map alone for 15 s. */
    pause(now) {
        this.pausedUntil = now + PAUSE_MS;
    }

    /** The recenter button: resume at once, and let the next fix re-zoom immediately. */
    resume() {
        this.pausedUntil = -Infinity;
        this.lastChangeAt = -Infinity;
    }

    /**
     * Feed one GPS fix. Returns the new zoom when the map should re-zoom, or
     * null to keep the current one. The caller applies it and recentres.
     */
    update({ distance, accuracy = null, viewportPx, radius = 10, lat = 0, now }) {
        if (this.isPaused(now)) {
            return null;
        }

        if (distance <= NEAR_METERS) {
            this.nearLocked = true;
        } else if (distance > NEAR_RELEASE_METERS) {
            this.nearLocked = false;
        }

        let target = zoomForDistance(distance, accuracy, viewportPx, radius, lat);

        if (this.nearLocked) {
            target = Math.max(target, this.zoom);
        }

        if (Math.abs(target - this.zoom) < HYSTERESIS_LEVELS) {
            return null;
        }

        if (now - this.lastChangeAt < MIN_INTERVAL_MS) {
            return null;
        }

        this.zoom = target;
        this.lastChangeAt = now;

        return target;
    }
}
