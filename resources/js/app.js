

import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import QRCode from 'qrcode';

window.Alpine = Alpine;
window.L = L;
window.QRCode = QRCode;

// Render QRIS: elemen <img data-qr="..."> digambar jadi QR Code setelah
// app.js (ES module) termuat — tidak boleh IIFE inline di blade karena
// window.QRCode baru tersedia setelah module ini dieksekusi.
function renderQrCodes(root = document) {
    root.querySelectorAll('img[data-qr]').forEach((el) => {
        const code = el.getAttribute('data-qr');
        if (!code) { return; }
        try {
            QRCode.toDataURL(code, {
                errorCorrectionLevel: 'M',
                margin: 1,
                width: 256,
                color: { dark: '#000000', light: '#ffffff' },
            }, (err, url) => {
                if (!err) { el.src = url; }
            });
        } catch (e) { /* biarkan kosong bila gagal */ }
    });
}
document.addEventListener('DOMContentLoaded', () => renderQrCodes());
// bfcache: saat kembali ke halaman (back navigation), pastikan QR tetap ter-render.
window.addEventListener('pageshow', () => renderQrCodes());

// Ikon marker default Leaflet: perbaiki path gambar (Vite).
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// Komponen Alpine: pilih titik lokasi via peta OpenStreetMap (tanpa Google).
// Dipakai di form booking & halaman lain yang butuh picker lokasi.
function readLocationLabel() {
    try {
        return window.localStorage.getItem('bookingboo.location-label');
    } catch (e) {
        return null;
    }
}

function saveLocationLabel(label) {
    try {
        window.localStorage.setItem('bookingboo.location-label', label);
    } catch (e) {
        // Ignore storage failures in private mode or restricted browsers.
    }
}

function formatLocationLabel(address = {}, displayName = '') {
    const city = [
        address.city,
        address.municipality,
        address.city_district,
        address.county,
        address.town,
        address.village,
        address.suburb,
    ].find(Boolean);

    const province = address.state || address.region;

    const cleanCity = typeof city === 'string'
        ? city
            .replace(/^Kota Administrasi\s+/i, '')
            .replace(/^Kota\s+/i, '')
            .replace(/^Kabupaten\s+/i, '')
            .trim()
        : '';

    const cleanProvince = typeof province === 'string'
        ? province.replace(/^Daerah Khusus Ibukota\s+/i, 'DKI ').trim()
        : '';

    if (cleanCity && cleanProvince && cleanCity.toLowerCase() !== cleanProvince.toLowerCase()) {
        return `${cleanCity}, ${cleanProvince}`;
    }

    if (cleanCity) {
        return cleanCity;
    }

    if (cleanProvince) {
        return cleanProvince;
    }

    return displayName ? displayName.replace(/, Indonesia$/, '') : 'Jabodetabek & Sekitarnya';
}

document.addEventListener('alpine:init', () => {
    Alpine.data('locationBanner', () => ({
        label: 'Jabodetabek & Sekitarnya',
        loading: false,

        init() {
            const cached = readLocationLabel();
            if (cached) {
                this.label = cached;
            }

            if (! navigator.geolocation) {
                return;
            }

            this.loading = true;
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        const { latitude, longitude } = position.coords;
                        const response = await fetch(
                            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&accept-language=id&addressdetails=1`
                        );

                        if (! response.ok) {
                            return;
                        }

                        const data = await response.json();
                        const label = formatLocationLabel(data?.address, data?.display_name);
                        if (label) {
                            this.label = label;
                            saveLocationLabel(label);
                        }
                    } catch (e) {
                        // Fallback tetap memakai label terakhir atau default.
                    } finally {
                        this.loading = false;
                    }
                },
                () => {
                    this.loading = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
            );
        },
    }));

    Alpine.data('locationPicker', (options = {}) => ({
        map: null,
        marker: null,
        lat: options.lat ?? null,
        lng: options.lng ?? null,
        address: options.address ?? '',
        searching: false,
        locating: false,
        errorMsg: '',

        init() {
            const el = this.$refs.mapEl;
            if (! el || typeof L === 'undefined') return;

            const defaultLat = this.lat ?? (options.defaultLat ?? -6.2088); // Jakarta
            const defaultLng = this.lng ?? (options.defaultLng ?? 106.8456);
            const zoom = this.lat ? 16 : 12;

            this.map = L.map(el).setView([defaultLat, defaultLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(this.map);

            if (this.lat && this.lng) {
                this.setMarker(this.lat, this.lng);
            }

            this.map.on('click', (e) => {
                this.setMarker(e.latlng.lat, e.latlng.lng);
                this.reverseGeocode(e.latlng.lat, e.latlng.lng);
            });
        },

        setMarker(lat, lng) {
            this.lat = lat;
            this.lng = lng;
            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.marker = L.marker([lat, lng]).addTo(this.map);
            }
        },

        async reverseGeocode(lat, lng) {
            this.searching = true;
            try {
                const resp = await fetch(
                    `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=id&addressdetails=1`
                );
                const data = await resp.json();
                if (data && data.display_name) {
                    // Pakai display_name lengkap dari Nominatim (jalan + RW +
                    // kelurahan + kecamatan + kota + provinsi + kodepos),
                    // hanya buang ", Indonesia" di akhir.
                    this.address = data.display_name.replace(/, Indonesia$/, '');
                    this.$dispatch('location-picked', { lat, lng, address: this.address });
                }
            } catch (e) {
                // reverse geocode gagal — biarkan alamat manual
            } finally {
                this.searching = false;
            }
        },

        async useMyLocation() {
            if (! navigator.geolocation) {
                this.errorMsg = 'Browser tidak mendukung GPS.';
                return;
            }
            this.locating = true;
            this.errorMsg = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const { latitude, longitude } = pos.coords;
                    this.setMarker(latitude, longitude);
                    this.map.setView([latitude, longitude], 16);
                    this.reverseGeocode(latitude, longitude);
                    this.locating = false;
                },
                () => {
                    this.errorMsg = 'Tidak dapat mengakses lokasi. Periksa izin GPS.';
                    this.locating = false;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        clear() {
            this.lat = null;
            this.lng = null;
            this.address = '';
            if (this.marker) {
                this.map.removeLayer(this.marker);
                this.marker = null;
            }
            this.$dispatch('location-picked', { lat: null, lng: null, address: '' });
        },
    }));

    // Komponen Alpine: pilih kategori metode pembayaran dulu, lalu opsi muncul.
    Alpine.data('paymentMethodPicker', (categories = []) => ({
        categories,
        selected: null,
        init() {
            if (this.categories.length > 0) {
                this.selected = this.categories[0].category;
            }
        },
        get hasMethods() {
            return this.categories.length > 0;
        },
    }));
});

Alpine.start();
