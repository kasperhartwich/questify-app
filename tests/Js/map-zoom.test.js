import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
    AutoZoom, FALLBACK_ZOOM, MAX_ZOOM, PAUSE_MS, haversineMeters, tableZoom, zoomForDistance,
} from '../../resources/js/map-zoom.js';

const VIEWPORT = 1000;

test('table rows give their zoom', () => {
    for (const [d, z] of [[2000, 14], [1500, 14], [1000, 15], [500, 16], [200, 17], [75, 18], [30, 19]]) {
        assert.equal(tableZoom(d), z, `${d} m`);
    }
});

test('zoom is interpolated between rows', () => {
    assert.equal(tableZoom(1250), 14.5);
    assert.equal(tableZoom(750), 15.5);
    assert.equal(tableZoom(350), 16.5);
    assert.ok(Math.abs(tableZoom(137.5) - 17.5) < 1e-9);
    assert.ok(tableZoom(52.5) > 18 && tableZoom(52.5) < 19);
});

test('zoom never decreases as the player gets closer', () => {
    let last = 0;
    for (let d = 2000; d >= 0; d -= 5) {
        const z = zoomForDistance(d, null, VIEWPORT);
        assert.ok(z >= last, `${d} m`);
        last = z;
    }
});

test('inside 30 m or the arrival radius plus 5 m is the maximum zoom', () => {
    assert.equal(zoomForDistance(30, null, VIEWPORT), MAX_ZOOM);
    assert.equal(zoomForDistance(3, null, VIEWPORT), MAX_ZOOM);
    assert.equal(zoomForDistance(52, null, VIEWPORT, 50), MAX_ZOOM);
    assert.ok(zoomForDistance(56, null, VIEWPORT, 50) < MAX_ZOOM);
});

test('the checkpoint stays on screen in a small viewport', () => {
    assert.ok(zoomForDistance(500, null, 256, 10, 55) < 16);
});

test('a GPS accuracy circle larger than the distance caps the zoom', () => {
    const precise = zoomForDistance(20, 5, 256, 10, 55);
    const vague = zoomForDistance(20, 80, 256, 10, 55);
    assert.ok(vague < precise);
    assert.equal(zoomForDistance(20, 10, 256, 10, 55), precise);
});

test('haversine measures metres', () => {
    assert.ok(Math.abs(haversineMeters(55, 12, 55.001, 12) - 111.2) < 1);
});

const fix = (distance, now, extra = {}) => ({ distance, viewportPx: VIEWPORT, now, ...extra });

test('small changes do not re-zoom (hysteresis)', () => {
    const az = new AutoZoom(16);
    assert.equal(az.update(fix(500, 0)), null);
    assert.equal(az.update(fix(480, 10000)), null);
});

test('re-zooms at most once every 3 s', () => {
    const az = new AutoZoom(14);
    assert.equal(az.update(fix(500, 10000)), 16);
    assert.equal(az.update(fix(200, 11000)), null);
    assert.equal(az.update(fix(200, 13001)), 17);
});

test('does not zoom out inside 30 m until 60 m is passed', () => {
    const az = new AutoZoom(16);
    assert.equal(az.update(fix(20, 0)), MAX_ZOOM);
    assert.equal(az.update(fix(45, 10000)), null);
    assert.equal(az.update(fix(60, 20000)), null);
    assert.ok(az.update(fix(61, 30000)) < MAX_ZOOM);
});

test('a manual pan pauses auto-zoom for 15 s, recenter resumes at once', () => {
    const az = new AutoZoom(14);
    az.pause(0);
    assert.ok(az.isPaused(PAUSE_MS - 1));
    assert.equal(az.update(fix(100, 1000)), null);
    assert.equal(az.update(fix(100, PAUSE_MS - 1)), null);
    assert.ok(az.update(fix(100, PAUSE_MS)) > 14);

    const again = new AutoZoom(14);
    again.pause(0);
    again.resume();
    assert.ok(!again.isPaused(1));
    assert.ok(again.update(fix(100, 1)) > 14);
});

test('without a fix the map stays at the fallback zoom', () => {
    assert.equal(new AutoZoom().zoom, FALLBACK_ZOOM);
});
