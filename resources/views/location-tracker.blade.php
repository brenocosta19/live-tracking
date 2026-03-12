<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" integrity="sha512-Zcn6bjR/8RZbLEpLIeOwNtzREBAJnUKESxces60Mpoj+2okopSAcSUIUOseddDm0cxnGQzxIR7vJgsLZbdLE3w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>Location Tracker</title>
    <style>
        body,html{
            height:100%;
            margin:0;
            
        }
        #map{
            height: calc(100% - 60px);
        }
        header{
            padding:15px;
            background: #f0f0f0;
            font-size:1.2rem;
        }
        .info{
            position: absolute;
            top: 70px;
            right: 10px;
            background: #fff;
            padding:10px;
            border-radius: 6px;
        }
    </style>
  </head>
  <body>
      <header> Real Time Location Tracker </header>
      <div id="error">
          
      </div>
      <div id="map"></div>
      <div class="info">
          <strong>Active</strong><span id="active-users"> 0</span><br>
          <strong>You</strong><span id="your-location"> 0</span><br>
          <strong>Status</strong><span id="status"> 0</span><br>
      </div>
      
      <div class="loading">
          <div class="spinner"></div>
          <div>Initializing Map...</div>
      </div>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js" integrity="sha512-BwHfrr4c9kmRkLw6iXFdzcdWV/PGkVgiIyIWLLlTSXzWQzxuSg4DiQUCpauz/EWjgk5TYQqX/kvn9pG1NpYfqg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
      <script>
        window.REVERB_KEY = '{{ env('VITE_REVERB_APP_KEY', env('REVERB_APP_KEY')) }}';
        window.REVERB_HOST = '{{ env('VITE_REVERB_HOST', env('REVERB_HOST')) }}';
        window.REVERB_PORT = {{ env('VITE_REVERB_PORT', env('VITE_REVERB_PORT', 443))}};
        window.REVERB_SCHEME = '{{ env('VITE_REVERB_SCHEME', env('REVERB_SCHEME', 'https' ))}}';
        window.sessionId = '{{ session()->getId() }}';
      </script>
      
      @vite(['resources/js/app.js'])
      
      <script>
        document.addEventListener('DOMContentLoaded', () =>  {
            const map = L.map('map').setView([0, 0], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            
            const markers = {};
            
            const updateMarker = (id, lat, lng) => {
                const isMe = id === window.sessionId;
                if (!markers[id]) {
                    const icon = L.divIcon({
                        html: `<div style="background:${isMe? '#667eea' : '#10b981'}; 
                        width:18px;height:18px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px #000;"></div>`,
                        iconSize: [18, 18], iconAnchor: [9,9]
                    });
                    markers[id] = L.marker([lat, lng], {icon}).addTo(map)
                    .bindPopup(`${isMe? 'You': 'User'}<br>${lat.toFixed(4)}, ${lng.toFixed(4)}`);    
                } else {
                    markers[id].setLatLng([lat,lng]);
                }
                
                document.getElementById('active-users').textContent = Object.keys(markers).length;
                
                if (isMe) {
                    map.setView([lat,lng], 16);
                    document.getElementById('your-location').textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }
            };
            
            
            const showError = (msg) => {
                const e = document.getElementById('error');
                e.textContent = msg;
                e.classList.add('show');
                setTimeout(() => e.classList.remove('show'), 5000);
            };
            
            const sendLocation = (lat, lng) => {
                fetch('{{route('location.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({latitude: lat, longitude: lng})
                }).catch (() => showError('Send failed'));
            };
            
            if (navigator.geolocation) {
                document.getElementById('status').textContent = 'Getting Location...';
                navigator.geolocation.watchPosition(
                    p => {
                        sendLocation(p.coords.latitude, p.coords.longitude);
                        document.getElementById('status').textContent = 'Tracking...';
                    },
                    () => {
                        showError('Location denied');
                        document.getElementById('status').textContent = 'Denied';
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 10000}
                );
            } else {
                showError('Geolocation not supported')
            }
            
            if (window.Echo) {
                window.Echo.channel('location-tracking')
                .listen('.location-updated', e => updateMarker(e.userId, e.latitude, e.longitude));
            } else {
                showError('Realtime connection failed');
            }
            
            document.querySelector('.loading').style.display = 'none';
        })
        
      </script>
  </body>
</html>