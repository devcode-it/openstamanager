<material-drawer @mobile type="modal" @elsenotmobile type="dismissible" open @endmobile>
    <x-drawer.entries></x-drawer.entries>

    <div slot="appContent">
        <main>
            @inertia
        </main>
    </div>
</material-drawer>
