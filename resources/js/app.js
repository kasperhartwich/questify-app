import './bootstrap';

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import mapboxWorker from 'mapbox-gl/dist/mapbox-gl-csp-worker?url';
import QRCode from 'qrcode';

mapboxgl.workerUrl = mapboxWorker;
window.mapboxgl = mapboxgl;
window.QRCode = QRCode;
