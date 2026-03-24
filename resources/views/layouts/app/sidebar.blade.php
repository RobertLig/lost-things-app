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

    <script>
        let map;
        let markers = {}; // store markers by location

        function initMap() {
            const el = document.getElementById('map');

            if (!el || el._leaflet_id) return;

            map = L.map(el).setView([50.2649, 19.0238], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            loadMarkers(() => {
                handleNewItem();
            });
        }

        function loadMarkers(callback = null) {
            fetch('/api/map-points')
                .then(res => res.json())
                .then(points => {
                    points.forEach((point, index) => {
                        setTimeout(() => {
                            addOrUpdateMarker(point.lat, point.lng, point.count);
                        }, 100 + index * 120);
                    });

                    // wait until animation ends
                    setTimeout(() => {
                        if (callback) callback();
                    }, 100 + points.length * 120);
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

        function handleNewItem() {
            if (!window.newItem) return;

            const {
                lat,
                lng
            } = window.newItem;

            const key = `${lat},${lng}`;

            // pan to location
            map.flyTo([lat, lng], 15, {
                duration: 1.2
            });

            // 🔥 highlight marker (existing OR new)
            setTimeout(() => {
                if (markers[key]) {
                    const el = markers[key].getElement();

                    el.classList.add('marker-highlight');

                    setTimeout(() => {
                        el.classList.remove('marker-highlight');
                    }, 1500);
                }
            }, 800);
        }

        // 🧠 LISTEN TO LIVEWIRE EVENT
        document.addEventListener('livewire:init', () => {
            Livewire.on('item-created', (data) => {
                addOrUpdateMarker(data.lat, data.lng);
            });
        });

        document.addEventListener('DOMContentLoaded', initMap);
        document.addEventListener('livewire:navigated', initMap);
    </script>

</body>

</html>
