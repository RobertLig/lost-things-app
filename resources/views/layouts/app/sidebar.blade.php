<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile" class="border-e border-border bg-background">
        <!-- border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 -->
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('home') }}" wire:navigate /> {{-- route('dashboard') --}}
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('home')" :current="request()->routeIs('home')"
                    {{-- dashboard --}} wire:navigate>
                    {{ __('Home') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        {{-- <flux:sidebar.nav>
            <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit"
                target="_blank">
                {{ __('Repository') }}
            </flux:sidebar.item>

            <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank">
                {{ __('Documentation') }}
            </flux:sidebar.item>
        </flux:sidebar.nav> --}}

        @auth
            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        @else
            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="{{ route('login') }}">
                    {{ __('Log in') }}
                </flux:sidebar.item>

                @if (Route::has('register'))
                    <flux:sidebar.item icon="book-open-text" href="{{ route('register') }}">
                        {{ __('Register') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>
        @endauth
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden bg-background! border-b! border-border!">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            @auth
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down"
                    :avatar="auth()->user()->photo_path ? \Illuminate\Support\Facades\Storage::url(auth()->user()->photo_path) : null" />

                <flux:menu class="bg-surface! border-border!">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()"
                                    color="emerald"
                                    :src="auth()->user()->photo_path ? \Illuminate\Support\Facades\Storage::url(auth()->user()->photo_path) : null" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="bg-border!" />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate
                            class="text-surface-foreground!">
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="bg-border!" />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer text-surface-foreground!" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            @else
                {{-- <flux:sidebar.nav>
                    <flux:sidebar.item icon="folder-git-2" href="{{ route('login') }}">
                        {{ __('Log in') }}
                    </flux:sidebar.item>

                    @if (Route::has('register'))
                        <flux:sidebar.item icon="book-open-text" href="{{ route('register') }}">
                            {{ __('Register') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.nav> --}}
            @endauth
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts

    @if (session('new_item'))
        <script>
            window.newItem = @json(session('new_item'));
        </script>
    @endif

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    {{-- <script>
        let map;
        let markers = {}; // store markers by location

        function initMap() {
            const el = document.getElementById('map');
            if (!el) return;

            // 🧹 cleanup previous instance
            if (map) {
                map.remove();
                map = null;
            }

            markers = {};

            // 👇 default center (fallback)
            let center = [50.2649, 19.0238];
            let zoom = 13;

            // 👇 if redirected from create
            if (window.newItem) {
                center = [window.newItem.lat, window.newItem.lng];
                zoom = 15;
            }

            map = L.map(el).setView(center, zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            loadMarkers(() => {
                highlightNewItem(); // optional effect
            });
        }


        function loadMarkers(callback = null) {
            fetch('/api/map-points')
                .then(res => res.json())
                .then(points => {

                    const baseDelay = window.newItem ? 300 : 100;

                    points.forEach((point, index) => {
                        setTimeout(() => {
                            addOrUpdateMarker(point.lat, point.lng, point.count);
                        }, baseDelay + index * 120);
                    });

                    setTimeout(() => {
                        if (callback) callback();
                    }, baseDelay + points.length * 120);
                });
        }

        function addOrUpdateMarker(lat, lng, count = 1) {
            const key = `${lat},${lng}`;

            // 🔁 If marker exists → update count
            if (markers[key]) {
                const badge = markers[key].getElement().querySelector('.marker-badge');
                badge.innerText = parseInt(badge.innerText) + 1;
                return;
            }

            // ➕ New marker
            const marker = L.marker([lat, lng], {
                icon: L.divIcon({
                    className: '',
                    html: `
                    <div class="marker">
                        <div class="marker-pin"></div>
                        <div class="marker-badge">${count}</div>
                    </div>
                `,
                    iconSize: [30, 42],
                    iconAnchor: [15, 42],
                })
            }).addTo(map);

            markers[key] = marker;

            // 🎯 optional: pan to new marker
            map.panTo([lat, lng]);
        }

        function highlightNewItem() {
            if (!window.newItem) return;

            const {
                lat,
                lng
            } = window.newItem;
            const key = `${lat},${lng}`;

            setTimeout(() => {
                if (markers[key]) {
                    const el = markers[key].getElement();

                    el.classList.add('marker-highlight');

                    setTimeout(() => {
                        el.classList.remove('marker-highlight');
                    }, 3500); //1500
                }
            }, 300);
        }

        // 🧠 LISTEN TO LIVEWIRE EVENT | not needed?
        /* document.addEventListener('livewire:init', () => {
            Livewire.on('item-created', (data) => {
                addOrUpdateMarker(data.lat, data.lng);
            });
        }); */

        document.addEventListener('livewire:navigated', initMap);
    </script> --}}

    {{-- <script>
        /* function initPickerMap() {
                const el = document.getElementById('picker-map');
                if (!el) return;

                // ✅ prevent re-initialization
                if (el._map) return;

                const componentEl = el.closest('[wire\\:id]');
                if (!componentEl) return;

                const component = Livewire.find(componentEl.getAttribute('wire:id'));

                component.$watch('lat', (lat) => {
                    const lng = component.get('lng');

                    if (!lat || !lng) return;

                    updateMarker(lat, lng);
                });

                component.$watch('lng', (lng) => {
                    const lat = component.get('lat');

                    if (!lat || !lng) return;

                    updateMarker(lat, lng);
                });

                console.log('INIT MAP');

                let map = L.map(el).setView([50.2649, 19.0238], 13);

                el._map = map; // store map instance
                el._marker = null; // store marker

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // 👇 restore marker if exists
                if (component.get('lat') && component.get('lng')) {
                    updateMarker(component.get('lat'), component.get('lng'));
                }

                // 🖱️ click handler
                map.on('click', function(e) {
                    const {
                        lat,
                        lng
                    } = e.latlng;

                    updateMarker(lat, lng);

                    component.set('lat', lat);
                    component.set('lng', lng);
                });

                function updateMarker(lat, lng) {
                    // remove old marker
                    if (el._marker) {
                        el._marker.remove();
                    }

                    // create new marker
                    el._marker = L.marker([lat, lng], {
                        draggable: true
                    }).addTo(map);

                    map.setView([lat, lng], 13);

                    // keep drag working
                    el._marker.on('dragend', function(e) {
                        const pos = e.target.getLatLng();
                        component.set('lat', pos.lat);
                        component.set('lng', pos.lng);
                    });
                }
            }

            // 🔥 RUN AFTER EVERYTHING IS READY
            document.addEventListener('livewire:load', initPickerMap);
            document.addEventListener('livewire:navigated', initPickerMap); */

        function useMyLocation() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    const el = document.getElementById('picker-map');
                    if (!el || !el._map) return;

                    const map = el._map;

                    // get Livewire component
                    const componentEl = el.closest('[wire\\:id]');
                    if (!componentEl) return;

                    const component = Livewire.find(componentEl.getAttribute('wire:id'));

                    // remove old marker
                    if (el._marker) {
                        el._marker.remove();
                    }

                    // add new marker
                    el._marker = L.marker([lat, lng], {
                        draggable: true
                    }).addTo(map);

                    // center map
                    map.setView([lat, lng], 15);

                    // update Livewire state
                    component.set('lat', lat);
                    component.set('lng', lng);

                    // keep drag working
                    el._marker.on('dragend', function(e) {
                        const pos = e.target.getLatLng();
                        component.set('lat', pos.lat);
                        component.set('lng', pos.lng);
                    });
                },
                (error) => {
                    if (error.code === 1) {
                        alert('Permission denied. Please allow location access.');
                    } else if (error.code === 2) {
                        alert('Location unavailable. Try again or use map.');
                    } else if (error.code === 3) {
                        alert('Location request timed out.');
                    } else {
                        alert('Unknown error retrieving location.');
                    }

                    console.error(error);
                }
            );
        }
    </script> --}}

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.1/Sortable.min.js"></script>
</body>

</html>
